<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exports\OrderDetailExport;
use App\Exports\SalesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\StoreAdminOrderRequest;
use App\Http\Requests\Admin\Order\UpdateAdminOrderRequest;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderStatusHistory;
use App\Models\PaymentStatusHistory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\SiteSetting;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

class OrderController extends Controller
{
    /**
     * Display a listing of the orders.
     */
    public function index(Request $request): JsonResponse|BinaryFileResponse
    {
        $query = Order::with(['items.product', 'address', 'user'])
            ->withCount('items');

        // Search by order number only
        if ($search = $request->query('search')) {
            $query->where('order_number', 'like', "%{$search}%");
        }

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filter by payment status
        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        // Filter by payment method
        if ($paymentMethod = $request->query('payment_method')) {
            if ($paymentMethod === 'blank') {
                $query->whereNull('payment_method');
            } else {
                $query->where('payment_method', $paymentMethod);
            }
        }

        // Filter by customer
        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        // Filter by province
        if ($province = $request->query('province')) {
            $query->whereHas('address', function ($q) use ($province) {
                $q->where('province', $province);
            });
        }

        // Filter by date
        if ($startDate = $request->query('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->query('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Sorting — whitelist to prevent SQL injection
        $allowedSorts = ['created_at', 'order_number', 'status', 'payment_status', 'total'];
        $sortByRaw = $request->query('sort_by', 'created_at');
        $sortDesc = $request->boolean('sort_desc', true);

        if ($sortByRaw === 'customer') {
            $query->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'orders.customer_id')
                    ->limit(1),
                $sortDesc ? 'desc' : 'asc'
            );
        } else {
            $sortBy = in_array($sortByRaw, $allowedSorts) ? $sortByRaw : 'created_at';
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        }

        if ($request->query('export') === 'excel') {
            return $this->export($request);
        }

        $paginator = $query->paginate($request->integer('per_page', 10));

        // Get status counts (respecting search and payment filter, but independent of order status)
        $statsQuery = Order::query();
        if ($search) {
            $statsQuery->where('order_number', 'like', "%{$search}%");
        }
        if ($paymentStatus) {
            $statsQuery->where('payment_status', $paymentStatus);
        }

        $statusCounts = $statsQuery->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Convert the paginator array and append stats
        $response = $paginator->toArray();
        $response['status_counts'] = $statusCounts;

        return response()->json($response);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load([
            'user',
            'creator',
            'items.product',
            'statusHistories.changedBy',
            'paymentHistories.changedBy'
        ]);
        // Load address with soft-deleted included (addresses can be deleted while order still references them)
        if ($order->address_id) {
            $order->setRelation('address', Address::withTrashed()->find($order->address_id));
        }

        $stockMovements = StockMovement::with(['product', 'user'])
            ->where('reference', 'like', '%' . $order->order_number . '%')
            ->orderBy('created_at', 'asc')
            ->get();

        $orderData = $order->toArray();
        unset($orderData['items'], $orderData['status_histories'], $orderData['payment_histories']);

        return response()->json([
            'order' => $orderData,
            'items' => $order->items,
            'status_history' => $order->statusHistories,
            'payment_history' => $order->paymentHistories,
            'stock_movements' => $stockMovements
        ]);
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(StoreAdminOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $shippingCost = (float) ($data['shipping_cost'] ?? 0);
        $customerId = $data['customer_id'];

        // Resolve Address
        $address = null;
        if (!empty($data['address_id'])) {
            $address = Address::where('customer_id', $customerId)->find($data['address_id']);
            if (!$address) {
                return response()->json(['message' => 'The selected address is invalid or does not belong to the customer.'], 422);
            }
        } elseif (!empty($data['address'])) {
            $address = Address::create([
                'customer_id' => $customerId,
                'label' => $data['address']['label'] ?? null,
                'name' => $data['address']['name'],
                'phone' => $data['address']['phone'] ?? null,
                'address_line_1' => $data['address']['address_line_1'] ?? null,
                'address_line_2' => $data['address']['address_line_2'] ?? null,
                'village' => $data['address']['village'] ?? null,
                'commune' => $data['address']['commune'] ?? null,
                'district' => $data['address']['district'] ?? null,
                'province' => $data['address']['province'],
                'latitude' => $data['address']['latitude'] ?? null,
                'longitude' => $data['address']['longitude'] ?? null,
                'notes' => $data['address']['notes'] ?? null,
                'is_default' => $data['address']['is_default'] ?? !Address::where('customer_id', $customerId)->exists(),
            ]);
        }

        $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $items = collect($data['items'])->map(function ($item) use ($products) {
            $product = $products->get($item['product_id']);
            $quantity = (int) $item['quantity'];
            $price = isset($item['unit_price']) ? (float) $item['unit_price'] : (float) $product->price;

            $originalPrice = (float) $product->price;
            $discount = max(0, ($originalPrice - $price) * $quantity);

            return [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $price,
                'total' => $price * $quantity,
                'discount' => $discount,
                'product_discount' => (float) ($product->discount_percent ?? 0),
            ];
        });

        $order = DB::transaction(function () use ($customerId, $address, $items, $shippingCost, $data) {
            // Verify stock
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->find($item['product']->id);
                if (!$product || $product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => [sprintf('Insufficient stock for %s. Only %d left.', $item['product']->name, $product ? $product->stock : 0)]
                    ]);
                }
            }

            $subtotal = $items->sum(function ($item) {
                return (float) $item['product']->price * $item['quantity'];
            });
            $totalDiscount = $items->sum('discount');

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $customerId,
                'address_id' => $address?->id,
                'status' => $data['status'] ?? OrderStatus::Pending->value,
                'payment_status' => $data['payment_status'] ?? PaymentStatus::Unpaid->value,
                'subtotal' => $subtotal,
                'discount' => $totalDiscount,
                'shipping_cost' => $shippingCost,
                'total' => $subtotal - $totalDiscount + $shippingCost,
                'payment_method' => $data['payment_method'] ?? null,
                'admin_note' => $data['admin_note'] ?? null,
                'customer_note' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? request()->user()?->id,
            ]);

            if (request()->hasFile('payment_receipt')) {
                $order->payment_receipt = request()->file('payment_receipt')->store('receipts', 'public');
                $order->save();
            }

            foreach ($items as $item) {
                OrderProduct::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'product_sku' => $item['product']->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'product_discount' => $item['product_discount'],
                    'total' => $item['total'],
                ]);
            }

            $adminId = request()->user()?->id;

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'old_status' => null,
                'new_status' => $order->status,
                'changed_by' => $adminId,
                'remarks' => $data['status_remark'] ?? null,
                'created_at' => now(),
            ]);

            PaymentStatusHistory::create([
                'order_id' => $order->id,
                'old_status' => null,
                'new_status' => $order->payment_status,
                'changed_by' => $adminId,
                'remarks' => $data['payment_status_remark'] ?? null,
                'created_at' => now(),
            ]);

            return $order;
        });

        $order = $order->fresh()->load(['items.product', 'address', 'user']);
        \App\Services\TelegramService::notifyAdminNewOrder($order, true);

        NotificationService::notifyAdmins(new NewOrderNotification($order));

        return response()->json($order, 201);
    }

    /**
     * Update the specified order in storage.
     */
    public function update(UpdateAdminOrderRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();

        $customerId = $data['customer_id'];
        $address = null;

        if (!empty($data['address_id'])) {
            $address = Address::where('customer_id', $customerId)->find($data['address_id']);
            if (!$address) {
                return response()->json(['message' => 'The selected address is invalid or does not belong to the customer.'], 422);
            }
        } elseif (!empty($data['address'])) {
            $address = Address::create([
                'customer_id' => $customerId,
                'label' => $data['address']['label'] ?? null,
                'name' => $data['address']['name'],
                'phone' => $data['address']['phone'] ?? null,
                'address_line_1' => $data['address']['address_line_1'] ?? null,
                'address_line_2' => $data['address']['address_line_2'] ?? null,
                'village' => $data['address']['village'] ?? null,
                'commune' => $data['address']['commune'] ?? null,
                'district' => $data['address']['district'] ?? null,
                'province' => $data['address']['province'],
                'latitude' => $data['address']['latitude'] ?? null,
                'longitude' => $data['address']['longitude'] ?? null,
                'notes' => $data['address']['notes'] ?? null,
                'is_default' => $data['address']['is_default'] ?? !Address::where('customer_id', $customerId)->exists(),
            ]);
        }

        $newStatusStr = $data['status'] instanceof UnitEnum ? $data['status']->value : $data['status'];
        $newPaymentStatusStr = $data['payment_status'] instanceof UnitEnum ? $data['payment_status']->value : $data['payment_status'];

        if ($errorMessage = $this->validateStatusCombination($newStatusStr, $newPaymentStatusStr)) {
            return response()->json(['message' => $errorMessage], 422);
        }

        $oldStatus = $order->status;
        $oldPaymentStatus = $order->payment_status;

        $oldStatusStr = $oldStatus instanceof UnitEnum ? $oldStatus->value : $oldStatus;
        $oldPaymentStatusStr = isset($oldPaymentStatus) ? ($oldPaymentStatus instanceof UnitEnum ? $oldPaymentStatus->value : $oldPaymentStatus) : null;

        if ($oldStatusStr !== $newStatusStr && empty($data['status_remark'])) {
            return response()->json(['message' => 'A remark is required when changing the order status.'], 422);
        }

        if ($oldPaymentStatusStr !== $newPaymentStatusStr && empty($data['payment_status_remark'])) {
            return response()->json(['message' => 'A remark is required when changing the payment status.'], 422);
        }

        if (isset($data['items'])) {
            if ($oldStatus !== OrderStatus::Pending || $oldPaymentStatus !== PaymentStatus::Unpaid) {
                return response()->json(['message' => 'Order items can only be edited when the order status is pending and payment is unpaid.'], 422);
            }

            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');
            $subtotal = 0;
            $totalDiscount = 0;
            $newItemsData = [];
            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);
                $price = isset($item['unit_price']) ? (float) $item['unit_price'] : (float) $product->price;
                $quantity = (int) $item['quantity'];

                if (!$product || $product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => [sprintf('Insufficient stock for %s. Only %d left.', $product ? $product->name : $item['product_id'], $product ? $product->stock : 0)]
                    ]);
                }

                $originalPrice = (float) $product->price;
                $discount = max(0, ($originalPrice - $price) * $quantity);

                $total = $price * $quantity;
                $subtotal += ($originalPrice * $quantity);
                $totalDiscount += $discount;
                $newItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'product_discount' => (float) ($product->discount_percent ?? 0),
                    'total' => $total,
                ];
            }

            DB::transaction(function () use ($order, $newItemsData, $subtotal, $totalDiscount) {
                $order->items()->delete();
                foreach ($newItemsData as $itemData) {
                    $itemData['order_id'] = $order->id;
                    OrderProduct::create($itemData);
                }
                $order->subtotal = $subtotal;
                $order->discount = $totalDiscount;
            });
        }

        $order->customer_id = $customerId;
        $order->address_id = $address?->id;
        $order->status = $data['status'];
        $order->payment_status = $data['payment_status'];
        $order->shipping_cost  = $data['shipping_cost'];
        $order->payment_method = $data['payment_method'] ?? null;
        $order->admin_note     = $data['admin_note'] ?? null;
        $order->status_remark  = $data['status_remark'] ?? null;
        $order->payment_status_remark = $data['payment_status_remark'] ?? null;

        if (array_key_exists('notes', $data)) {
            $order->customer_note = $data['notes'];
        }

        if (array_key_exists('created_by', $data)) {
            $order->created_by = $data['created_by'];
        }

        // Handle receipt image upload
        // Note: Order proofs/receipts are NEVER deleted from storage (audit trail preserved)
        if ($request->hasFile('payment_receipt')) {
            $order->payment_receipt = $request->file('payment_receipt')->store('receipts', 'public');
        } elseif ($request->input('remove_receipt')) {
            // Admin explicitly cleared the receipt from order, but keep file on disk as proof
            $order->payment_receipt = null;
        }

        // Recalculate total
        $order->total = $order->subtotal - $order->discount + $order->shipping_cost;

        $order->save();

        $changedByName = $request->user()?->name ?? 'Admin';
        $freshOrder = $order->fresh()->load(['items.product', 'address', 'user', 'creator']);

        \App\Services\TelegramService::notifyAdminOrderUpdated($freshOrder, $changedByName);
        NotificationService::notifyAdmins(new \App\Notifications\OrderUpdatedNotification($freshOrder, $changedByName));

        return response()->json($freshOrder);
    }

    /**
     * Export sales report to Excel.
     */
    public function export(Request $request)
    {
        $query = Order::with(['items.product', 'address', 'user'])
            ->withCount('items');

        if ($search = $request->query('search')) {
            $query->where('order_number', 'like', "%{$search}%");
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($paymentMethod = $request->query('payment_method')) {
            if ($paymentMethod === 'blank') {
                $query->whereNull('payment_method');
            } else {
                $query->where('payment_method', $paymentMethod);
            }
        }

        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($province = $request->query('province')) {
            $query->whereHas('address', function ($q) use ($province) {
                $q->where('province', $province);
            });
        }

        if ($startDate = $request->query('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->query('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $allowedSorts = ['created_at', 'order_number', 'status', 'payment_status', 'total'];
        $sortByRaw = $request->query('sort_by', 'created_at');
        $sortDesc = $request->boolean('sort_desc', true);

        if ($sortByRaw === 'customer') {
            $query->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'orders.customer_id')
                    ->limit(1),
                $sortDesc ? 'desc' : 'asc'
            );
        } else {
            $sortBy = in_array($sortByRaw, $allowedSorts) ? $sortByRaw : 'created_at';
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        }

        $orders = $query->get();

        $user = $request->user();
        $generatedBy = $user ? $user->name : 'Admin';

        $customerFilter = 'All Customers';
        if ($customerId = $request->query('customer_id')) {
            $cust = User::withTrashed()->find($customerId);
            if ($cust) {
                $cName = $cust->name;
                if ($cust->trashed()) $cName .= ' (Deleted)';
                elseif (!$cust->is_active) $cName .= ' (Inactive)';
                $customerFilter = $cName;
            }
        }

        $dateRangeFilter = 'All';
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $dateRangeFilter = Carbon::parse($request->input('start_date'))->format('d/m/Y') . ' - ' . Carbon::parse($request->input('end_date'))->format('d/m/Y');
        }

        $exportData = [];
        $orderDetailsData = [];

        foreach ($orders as $index => $ord) {
            $subtotal = (float) ($ord->subtotal ?? $ord->total);
            $shippingCost = (float) ($ord->shipping_cost ?? 0);
            $discount = (float) ($ord->discount ?? 0);
            $totalAmount = (float) $ord->total;

            $custName = 'Guest';
            if ($ord->user) {
                $custName = $ord->user->name;
                if ($ord->user->trashed()) {
                    $custName .= ' (Deleted)';
                } elseif (!$ord->user->is_active) {
                    $custName .= ' (Inactive)';
                }
            }

            $exportData[] = [
                'order_date' => $ord->created_at ? $ord->created_at->format('Y-m-d H:i') : '—',
                'order_number' => $ord->order_number,
                'customer' => $custName,
                'order_status' => ucfirst(is_object($ord->status) ? $ord->status->value : $ord->status),
                'payment_status' => ucfirst(is_object($ord->payment_status) ? $ord->payment_status->value : $ord->payment_status),
                'payment_method' => $ord->payment_method ? ucfirst(is_object($ord->payment_method) ? $ord->payment_method->value : $ord->payment_method) : '—',
                'total_items' => $ord->items_count ?? count($ord->items),
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'discount' => $discount,
                'total_amount' => $totalAmount,
            ];

            foreach ($ord->items as $item) {
                $fullPrice = (float) $item->unit_price;
                $disc = (float) ($item->product_discount ?? 0);
                $finalPrice = $disc > 0 ? $fullPrice * (1 - $disc / 100) : $fullPrice;
                $qty = (int) $item->quantity;
                $itemTotal = (float) $item->total ?: ($finalPrice * $qty);

                $prodName = $item->product_name ?? ($item->product ? $item->product->name : 'Unknown');
                if ($item->product) {
                    if ($item->product->trashed()) {
                        $prodName .= ' (Deleted)';
                    } elseif (!$item->product->is_active) {
                        $prodName .= ' (Inactive)';
                    }
                }

                $catName = 'Uncategorized';
                if ($item->product && $item->product->category) {
                    $catName = $item->product->category->name;
                    if ($item->product->category->trashed()) {
                        $catName .= ' (Deleted)';
                    } elseif (!$item->product->category->is_active) {
                        $catName .= ' (Inactive)';
                    }
                }

                $orderDetailsData[] = [
                    'order_number' => $ord->order_number,
                    'customer' => $custName,
                    'product' => $prodName,
                    'sku' => $item->product_sku ?? ($item->product ? ($item->product->sku ?: '—') : '—'),
                    'category' => $catName,
                    'full_price' => $fullPrice,
                    'discount' => $disc,
                    'final_price' => $finalPrice,
                    'quantity' => $qty,
                    'item_total' => $itemTotal,
                ];
            }
        }

        $totalSales = array_sum(array_column($exportData, 'total_amount'));
        $totalOrdersCount = count($exportData);
        $averageOrderValue = $totalOrdersCount > 0 ? round($totalSales / $totalOrdersCount, 2) : 0;
        $productsSold = array_sum(array_column($orderDetailsData, 'quantity'));

        $exportPayload = [
            'generated_at' => Carbon::now()->format('M j, Y h:i A'),
            'generated_by' => $generatedBy,
            'filters' => [
                'status' => $request->query('status') ? ucfirst($request->query('status')) : 'All',
                'payment_status' => $request->query('payment_status') ? ucfirst($request->query('payment_status')) : 'All',
                'payment_method' => $request->query('payment_method') ? ucfirst($request->query('payment_method')) : 'All',
                'customer' => $customerFilter,
                'province' => $request->query('province') ?: 'All',
                'date_range' => $dateRangeFilter,
                'order' => $request->query('search') ?: 'All Orders',
            ],
            'orders' => $exportData,
            'order_details' => $orderDetailsData,
            'summary' => [
                'revenue' => round($totalSales, 2),
                'orders' => $totalOrdersCount,
                'products_sold' => (int) $productsSold,
                'average_order_value' => $averageOrderValue,
            ]
        ];

        return Excel::download(
            new SalesExport($exportPayload),
            'Orders_Report_' . Carbon::today()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export order detail report to Excel.
     */
    public function exportDetail(Request $request, Order $order): BinaryFileResponse
    {
        $order->load([
            'user',
            'creator',
            'address',
            'items.product.category',
            'statusHistories.changedBy',
            'paymentHistories.changedBy',
        ]);

        $user = $request->user();
        $generatedBy = $user ? $user->name : 'Admin';

        $subtotal = (float) ($order->subtotal ?? $order->total);
        $shippingCost = (float) ($order->shipping_cost ?? 0);
        $discount = (float) ($order->discount ?? 0);
        $totalAmount = (float) $order->total;

        $orderDetailsData = [];
        foreach ($order->items as $item) {
            $fullPrice = (float) $item->unit_price;
            $disc = (float) ($item->product_discount ?? 0);
            $finalPrice = $disc > 0 ? $fullPrice * (1 - $disc / 100) : $fullPrice;
            $qty = (int) $item->quantity;

            $prodName = $item->product_name ?? ($item->product ? $item->product->name : 'Unknown');
            if ($item->product) {
                if ($item->product->trashed()) {
                    $prodName .= ' (Deleted)';
                } elseif (!$item->product->is_active) {
                    $prodName .= ' (Inactive)';
                }
            }

            $catName = 'Uncategorized';
            if ($item->product && $item->product->category) {
                $catName = $item->product->category->name;
                if ($item->product->category->trashed()) {
                    $catName .= ' (Deleted)';
                } elseif (!$item->product->category->is_active) {
                    $catName .= ' (Inactive)';
                }
            }

            $orderDetailsData[] = [
                'product' => $prodName,
                'sku' => $item->product_sku ?? ($item->product ? ($item->product->sku ?: '—') : '—'),
                'category' => $catName,
                'full_price' => $fullPrice,
                'discount' => $disc,
                'final_price' => $finalPrice,
                'quantity' => $qty,
                'item_total' => (float) $item->total,
            ];
        }

        $custName = $order->user?->name ?? 'Guest / Deleted User';
        if ($order->user) {
            if ($order->user->trashed()) {
                $custName .= ' (Deleted)';
            } elseif (!$order->user->is_active) {
                $custName .= ' (Inactive)';
            }
        }

        $exportPayload = [
            'generated_at' => Carbon::now()->format('M j, Y h:i A'),
            'generated_by' => $generatedBy,
            'order' => [
                'order_number' => $order->order_number,
                'status' => ucfirst(is_object($order->status) ? $order->status->value : (string) $order->status),
                'payment_status' => ucfirst(is_object($order->payment_status) ? $order->payment_status->value : (string) $order->payment_status),
                'payment_method' => $order->payment_method ? strtoupper(is_object($order->payment_method) ? $order->payment_method->value : (string) $order->payment_method) : 'N/A',
                'created_at' => $order->created_at ? $order->created_at->format('M j, Y h:i A') : 'N/A',
                'created_by' => $order->creator?->name ?? 'Self-Service',
                'customer_name' => $custName,
                'customer_email' => $order->user?->email ?? 'N/A',
                'customer_phone' => $order->address?->phone ?? $order->user?->phone ?? 'N/A',
                'shipping_address' => $order->address ? implode(', ', array_filter([
                    $order->address->address_line_1,
                    $order->address->address_line_2,
                    $order->address->village,
                    $order->address->commune,
                    $order->address->district,
                    $order->address->province
                ])) : 'No Shipping Address',
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'discount' => $discount,
                'total' => $totalAmount,
                'items_count' => count($orderDetailsData),
            ],
            'items' => $orderDetailsData,
        ];

        return Excel::download(
            new OrderDetailExport($exportPayload),
            'Order_Detail_' . $order->order_number . '_' . Carbon::today()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Generate a unique order number.
     */
    protected function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Validate status and payment status combination.
     */
    protected function validateStatusCombination(string $status, string $paymentStatus): ?string
    {
        if ($paymentStatus === 'unpaid') {
            $allowed = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'returned'];
            if (!in_array($status, $allowed)) {
                return "Cannot change order status to '$status' while payment is unpaid.";
            }
        } elseif ($paymentStatus === 'paid') {
            $allowed = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'returned'];
            if (!in_array($status, $allowed)) {
                return "Cannot change order status to '$status' while payment is paid.";
            }
        } elseif ($paymentStatus === 'refunded') {
            $allowed = ['pending', 'cancelled', 'returned'];
            if (!in_array($status, $allowed)) {
                return "Cannot change order status to '$status' because the payment was refunded.";
            }
        }
        return null;
    }

    /**
     * Download order PDF (A4 invoice or Thermal receipt).
     */
    public function downloadPdf(Request $request, Order $order)
    {
        $order->load(['items.product', 'user']);
        if ($order->address_id) {
            $order->setRelation('address', Address::withTrashed()->find($order->address_id));
        }

        $company = [
            'name' => SiteSetting::get('site_name', 'Unknown'),
            'email' => SiteSetting::get('contact_email', ''),
            'phone' => SiteSetting::get('contact_phone', ''),
            'address_line1' => SiteSetting::get('contact_address', ''),
            'address_line2' => null,
        ];

        $format = $request->query('format', 'a4');

        if ($format === 'receipt' || $format === 'thermal') {
            $itemCount = $order->items->count();
            $receiptHeight = 260 + ($itemCount * 22);

            $pdf = Pdf::loadView('invoices.receipt', [
                'order' => $order,
                'company' => array_filter($company),
            ])->setPaper([0, 0, 226.7, $receiptHeight]);

            return $pdf->download($order->order_number . '-receipt-thermal.pdf');
        } elseif ($format === 'receipt_html') {
            return view('invoices.receipt', [
                'order' => $order,
                'company' => array_filter($company),
            ]);
        } else {
            $pdf = Pdf::loadView('invoices.order', [
                'order' => $order,
                'company' => array_filter($company),
            ])->setPaper('A4');

            return $pdf->download($order->order_number . '-invoice-a4.pdf');
        }
    }
}
