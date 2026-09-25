<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Exports\CustomerDetailExport;
use App\Exports\CustomersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Customer\StoreCustomerRequest;
use App\Http\Requests\Admin\Customer\UpdateCustomerRequest;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {

        $perPage = max(1, min(100, $request->integer('per_page', 15)));
        $search = trim((string) $request->input('search', ''));

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDesc = $request->boolean('sort_desc', true);

        // Allowed sort columns
        $allowedSorts = ['name', 'email', 'phone', 'created_at', 'orders_count', 'total_spent'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $status = $request->input('status', 'active');

        $query = User::query()
            ->where('role', UserRole::Customer)
            ->when($status !== 'all', function (Builder $query) use ($status) {
                if ($status === 'deleted') {
                    $query->onlyTrashed();
                } elseif ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($status === 'all', function (Builder $query) {
                $query->withTrashed();
            });

        $query->when($search !== '', function (Builder $query) use ($search) {
            $query->where(function (Builder $searchQuery) use ($search) {
                $like = "%{$search}%";
                $searchQuery
                    ->where('name', 'like', $like);
            });
        })
            ->when($request->query('start_date'), function (Builder $query, $startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($request->query('end_date'), function (Builder $query, $endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            })
            ->withCount('orders')
            ->withSum(
                ['orders as total_spent' => function ($orderQuery) {
                    $orderQuery->where('payment_status', PaymentStatus::Paid->value);
                }],
                DB::raw('total - COALESCE(shipping_cost, 0)')
            );

        if ($sortBy === 'orders_count' || $sortBy === 'total_spent') {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        } else {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        }

        $customers = $query->paginate($perPage);

        $customers->getCollection()->transform(function (User $customer) {
            $customer->total_spent = (float) ($customer->total_spent ?? 0);
            return $customer;
        });

        return response()->json($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $avatarUrl = $path;
        }

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'telegram_id' => $validated['telegram_id'] ?? null,
            'avatar_url' => $avatarUrl,
            'password' => !empty($validated['password']) ? Hash::make($validated['password']) : Hash::make(\Illuminate\Support\Str::random(10) . 'Aa1'),
            'role' => UserRole::Customer->value,
            'is_active' => true,
        ]);

        if (!empty($validated['addresses'])) {
            $hasDefault = false;
            foreach ($validated['addresses'] as $addr) {
                // Check if at least one field is filled
                if (array_filter($addr, function ($val) {
                    return $val !== null && $val !== '';
                })) {
                    $isDefault = filter_var($addr['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    if ($isDefault) {
                        $hasDefault = true;
                    }

                    $user->addresses()->create([
                        'label' => $addr['label'] ?? 'Default',
                        'name' => $addr['name'] ?? null,
                        'phone' => $addr['phone'] ?? null,
                        'address_line_1' => $addr['address_line_1'] ?? null,
                        'address_line_2' => $addr['address_line_2'] ?? null,
                        'village' => $addr['village'] ?? null,
                        'commune' => $addr['commune'] ?? null,
                        'district' => $addr['district'] ?? null,
                        'province' => $addr['province'] ?? null,
                        'latitude' => $addr['latitude'] ?? null,
                        'longitude' => $addr['longitude'] ?? null,
                        'is_default' => $isDefault,
                        'notes' => $addr['notes'] ?? null,
                    ]);
                }
            }
            // Ensure at least one default if none was set but addresses exist
            if (!$hasDefault && $user->addresses()->count() > 0) {
                $user->addresses()->first()->update(['is_default' => true]);
            }
        }

        \App\Services\NotificationService::notifyAdmins(new \App\Notifications\NewCustomerNotification($user));

        return response()->json($user, 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        abort_unless($user->role === UserRole::Customer, 404);

        $ordersQuery = $user->orders()->with(['items.product', 'address', 'user'])->latest();
        $orders = $ordersQuery->get();
        $orders->each(function (Order $order) {
            $order->setAttribute('items_count', $order->items->count());
        });

        $addressesQuery = $user->addresses()->orderByDesc('is_default');
        if ($request->query('include_deleted')) {
            $addressesQuery->withTrashed();
        }
        $addresses = $addressesQuery->get();

        $totalSpent = (float) $user->orders()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->sum(DB::raw('total - COALESCE(shipping_cost, 0)'));

        $ordersCount = $orders->count();
        $averageOrderValue = $ordersCount > 0 ? round($totalSpent / $ordersCount, 2) : 0.0;
        $lastOrderAt = optional($orders->first())->created_at;

        $userArray = $user->toArray();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'telegram_id' => $user->telegram_id,
            'role' => $user->role->value,
            'avatar_url' => $userArray['avatar_url'] ?? null,
            'created_at' => optional($user->created_at)?->toIso8601String(),
            'orders_count' => $ordersCount,
            'total_spent' => $totalSpent,
            'average_order_value' => $averageOrderValue,
            'last_order_at' => $lastOrderAt?->toIso8601String(),
            'status' => $user->status,
            'addresses' => $addresses,
            'orders' => $orders,
        ]);
    }

    public function update(UpdateCustomerRequest $request, User $user): JsonResponse
    {
        abort_unless($user->role === UserRole::Customer, 404);

        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            $user->deleteAvatarFile();
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_url = $path;
        } elseif ($request->input('remove_avatar') === '1' || $request->boolean('remove_avatar')) {
            // Remove existing avatar from storage and DB
            $user->deleteAvatarFile();
            $user->avatar_url = null;
        }

        $user->fill([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'telegram_id' => array_key_exists('telegram_id', $validated) ? $validated['telegram_id'] : $user->telegram_id,
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if (isset($validated['addresses'])) {
            $incomingIds = [];
            $hasDefault = false;

            foreach ($validated['addresses'] as $addr) {
                // Skip completely empty address blocks
                if (!array_filter($addr, function ($val) {
                    return $val !== null && $val !== '';
                })) {
                    continue;
                }

                $isDefault = filter_var($addr['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if ($isDefault) {
                    $hasDefault = true;
                }

                $addressData = [
                    'label' => $addr['label'] ?? 'Default',
                    'name' => $addr['name'] ?? null,
                    'phone' => $addr['phone'] ?? null,
                    'address_line_1' => $addr['address_line_1'] ?? null,
                    'address_line_2' => $addr['address_line_2'] ?? null,
                    'village' => $addr['village'] ?? null,
                    'commune' => $addr['commune'] ?? null,
                    'district' => $addr['district'] ?? null,
                    'province' => $addr['province'] ?? null,
                    'latitude' => $addr['latitude'] ?? null,
                    'longitude' => $addr['longitude'] ?? null,
                    'is_default' => $isDefault,
                    'notes' => $addr['notes'] ?? null,
                ];

                if (!empty($addr['id'])) {
                    $address = $user->addresses()->find($addr['id']);
                    if ($address) {
                        $address->update($addressData);
                        $incomingIds[] = $address->id;
                    }
                } else {
                    $newAddress = $user->addresses()->create($addressData);
                    $incomingIds[] = $newAddress->id;
                }
            }

            // Soft delete addresses not in the payload
            $user->addresses()->whereNotIn('id', $incomingIds)->delete();

            // Ensure one default if needed
            if (!$hasDefault && $user->addresses()->count() > 0) {
                $user->addresses()->orderBy('id')->first()->update(['is_default' => true]);
            }
        } else if ($request->has('addresses') && empty($validated['addresses'])) {
            // User sent an empty array, meaning all addresses were removed
            $user->addresses()->delete();
        }

        return response()->json($user);
    }


    public function block(Request $request, $id): JsonResponse
    {
        $authUser = $request->user();
        abort_unless($authUser && $authUser->isAdmin(), 403);

        $user = User::findOrFail($id);
        abort_unless($user->role === UserRole::Customer, 404);

        $user->update(['is_active' => false]);

        return response()->json(['message' => 'Customer blocked successfully']);
    }

    public function unblock(Request $request, $id): JsonResponse
    {
        $authUser = $request->user();
        abort_unless($authUser && $authUser->isAdmin(), 403);

        $user = User::findOrFail($id);
        abort_unless($user->role === UserRole::Customer, 404);

        $user->update(['is_active' => true]);

        return response()->json(['message' => 'Customer unblocked successfully']);
    }

    public function restore(Request $request, $id): JsonResponse
    {
        $authUser = $request->user();
        abort_unless($authUser && $authUser->isAdmin(), 403);

        $user = User::withTrashed()->findOrFail($id);
        abort_unless($user->role === UserRole::Customer, 404);

        $user->restore();
        $user->addresses()->withTrashed()->restore();

        return response()->json(['message' => 'Customer restored successfully']);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $authUser = $request->user();
        abort_unless($authUser && $authUser->isAdmin(), 403);

        $user = User::findOrFail($id);
        abort_unless($user->role === UserRole::Customer, 404);

        $user->addresses()->delete();
        $user->delete();

        return response()->json(['message' => 'Customer deleted successfully']);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'active');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDesc = $request->boolean('sort_desc', true);

        $query = User::query()
            ->where('role', UserRole::Customer)
            ->when($status !== 'all', function (Builder $query) use ($status) {
                if ($status === 'deleted') {
                    $query->onlyTrashed();
                } elseif ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($status === 'all', function (Builder $query) {
                $query->withTrashed();
            })
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->query('start_date'), function (Builder $query, $startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($request->query('end_date'), function (Builder $query, $endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            })
            ->withCount('orders')
            ->withSum(
                ['orders as total_spent' => function ($orderQuery) {
                    $orderQuery->where('payment_status', PaymentStatus::Paid->value);
                }],
                'total'
            );

        $allowedSorts = ['name', 'email', 'phone', 'created_at', 'orders_count', 'total_spent'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $customers = $query->get();

        $user = $request->user();
        $generatedBy = $user ? $user->name : 'Admin';

        $exportData = $customers->map(function ($cust) {
            $statusStr = 'Active';
            if ($cust->trashed()) {
                $statusStr = 'Deleted';
            } elseif (!$cust->is_active) {
                $statusStr = 'Inactive';
            }

            return [
                'name' => $cust->name,
                'email' => $cust->email ?? 'N/A',
                'phone' => $cust->phone ?? 'N/A',
                'orders_count' => $cust->orders_count ?? 0,
                'total_spent' => (float) ($cust->total_spent ?? 0),
                'status' => $statusStr,
                'created_at' => $cust->created_at ? $cust->created_at->format('Y-m-d H:i') : 'N/A',
            ];
        })->toArray();

        $totalSpentAll = array_sum(array_column($exportData, 'total_spent'));
        $totalOrdersAll = array_sum(array_column($exportData, 'orders_count'));
        $activeCount = collect($exportData)->where('status', 'Active')->count();
        $inactiveCount = collect($exportData)->where('status', 'Inactive')->count();
        $deletedCount = collect($exportData)->where('status', 'Deleted')->count();

        $dateRangeFilter = 'All';
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $dateRangeFilter = Carbon::parse($request->input('start_date'))->format('d/m/Y') . ' - ' . Carbon::parse($request->input('end_date'))->format('d/m/Y');
        }

        $exportPayload = [
            'generated_at' => Carbon::now()->format('M j, Y h:i A'),
            'generated_by' => $generatedBy,
            'filters' => [
                'search' => $search ?: 'None',
                'status' => ucfirst($status),
                'date_range' => $dateRangeFilter,
            ],
            'customers' => $exportData,
            'summary' => [
                'total_customers' => count($exportData),
                'active_customers' => $activeCount,
                'inactive_customers' => $inactiveCount,
                'deleted_customers' => $deletedCount,
                'total_orders' => $totalOrdersAll,
                'total_spent' => '$' . number_format($totalSpentAll, 2),
            ]
        ];

        return Excel::download(
            new CustomersExport($exportPayload),
            'Customers_Report_' . Carbon::today()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportDetail(Request $request, User $user): BinaryFileResponse
    {
        abort_unless($user->role === UserRole::Customer, 404);

        $orders = $user->orders()->with('items')->latest()->get();
        $addresses = $user->addresses()->withTrashed()->orderByDesc('is_default')->get();

        $totalSpent = (float) $user->orders()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->sum(DB::raw('total - COALESCE(shipping_cost, 0)'));
        $ordersCount = $orders->count();
        $averageOrderValue = $ordersCount > 0 ? round($totalSpent / $ordersCount, 2) : 0.0;
        $lastOrderAt = optional($orders->first())->created_at;

        $userAuth = $request->user();
        $generatedBy = $userAuth ? $userAuth->name : 'Admin';

        $statusStr = 'Active';
        if ($user->trashed()) {
            $statusStr = 'Deleted';
        } elseif (!$user->is_active) {
            $statusStr = 'Inactive';
        }

        $addressesData = $addresses->map(function ($addr) {
            $parts = array_filter([
                $addr->address_line_1,
                $addr->address_line_2,
                $addr->village,
                $addr->commune,
                $addr->district,
                $addr->province,
            ]);
            return [
                'label' => $addr->label ?? 'Default',
                'name' => $addr->name ?? 'N/A',
                'phone' => $addr->phone ?? 'N/A',
                'full_address' => implode(', ', $parts) ?: 'N/A',
                'is_default' => (bool) $addr->is_default,
                'notes' => $addr->notes ?? 'None',
            ];
        })->toArray();

        $ordersData = $orders->map(function ($ord) {
            return [
                'order_number' => $ord->order_number,
                'date' => $ord->created_at ? $ord->created_at->format('M j, Y h:i A') : 'N/A',
                'status' => ucfirst(is_object($ord->status) ? $ord->status->value : (string) $ord->status),
                'payment_status' => ucfirst(is_object($ord->payment_status) ? $ord->payment_status->value : (string) $ord->payment_status),
                'items_count' => $ord->items->count(),
                'total' => '$' . number_format((float) $ord->total, 2),
            ];
        })->toArray();

        $exportPayload = [
            'generated_at' => Carbon::now()->format('M j, Y h:i A'),
            'generated_by' => $generatedBy,
            'customer' => [
                'name' => $user->name,
                'email' => $user->email ?? 'N/A',
                'phone' => $user->phone ?? 'N/A',
                'status' => $statusStr,
                'created_at' => $user->created_at ? $user->created_at->format('M j, Y h:i A') : 'N/A',
            ],
            'summary' => [
                'orders_count' => $ordersCount,
                'total_spent' => '$' . number_format($totalSpent, 2),
                'average_order_value' => '$' . number_format($averageOrderValue, 2),
                'last_order_at' => $lastOrderAt ? $lastOrderAt->format('M j, Y h:i A') : 'No orders',
            ],
            'addresses' => $addressesData,
            'orders' => $ordersData,
        ];

        return Excel::download(
            new CustomerDetailExport($exportPayload),
            'Customer_Detail_' . Str::slug($user->name) . '_' . Carbon::today()->format('Y-m-d') . '.xlsx'
        );
    }
}
