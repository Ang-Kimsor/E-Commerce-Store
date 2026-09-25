<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Exports\AdminsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUser\StoreAdminUserRequest;
use App\Http\Requests\Admin\AdminUser\UpdateAdminUserRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminController extends Controller
{
    /**
     * List all admins (or superadmins if include_superadmin=1 for selection dropdowns).
     */
    public function index(Request $request): JsonResponse
    {
        $includeSuperAdmin = $request->boolean('include_superadmin') || $request->input('include_superadmin') === '1';

        if ($includeSuperAdmin) {
            $query = User::whereIn('role', [UserRole::Admin, UserRole::SuperAdmin]);
        } else {
            $query = User::where('role', UserRole::Admin);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('role') && $request->input('role') !== '') {
            $requestedRole = $request->input('role');
            if ($requestedRole === UserRole::SuperAdmin->value && !$includeSuperAdmin) {
                $query->where('role', UserRole::Admin);
            } else {
                $query->where('role', $requestedRole);
            }
        }
        $status = $request->input('status');

        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } else {
            $query->withTrashed();
        }

        $sort = $request->query('sort_by', 'created_at');
        $order = $request->boolean('sort_desc', true) ? 'desc' : 'asc';

        $allowedSorts = ['name', 'email', 'created_at'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $order);
        }

        $perPage = $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Prevent operations on SuperAdmin users via web API.
     */
    private function checkSuperAdminRestriction(User $admin): void
    {
        $roleValue = is_object($admin->role) ? $admin->role->value : (string) $admin->role;
        if ($roleValue === UserRole::SuperAdmin->value) {
            abort(403, 'Superadmin accounts cannot be managed through the web interface.');
        }
    }

    /**
     * Create a new admin user.
     */
    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['role']) && $validated['role'] === UserRole::SuperAdmin->value) {
            return response()->json([
                'message' => 'Superadmin users can only be created via Artisan command.'
            ], 403);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::Admin,
            'phone' => $validated['phone'] ?? null,
            'telegram_id' => $validated['telegram_id'] ?? null,
        ]);

        try {
            \App\Services\NotificationService::notifyAdmins(new \App\Notifications\NewAdminNotification($user));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send NewAdminNotification: ' . $e->getMessage());
        }

        return response()->json($user, 201);
    }

    /**
     * View an admin user.
     */
    public function show(User $admin): JsonResponse
    {
        if (!in_array($admin->role, [UserRole::Admin, UserRole::SuperAdmin])) {
            abort(404, 'Admin not found');
        }

        $this->checkSuperAdminRestriction($admin);

        return response()->json($admin);
    }

    /**
     * Update an admin user.
     */
    public function update(UpdateAdminUserRequest $request, User $admin): JsonResponse
    {
        if (!in_array($admin->role, [UserRole::Admin, UserRole::SuperAdmin])) {
            abort(404, 'Admin not found');
        }

        $this->checkSuperAdminRestriction($admin);

        $validated = $request->validated();

        if (isset($validated['role']) && $validated['role'] === UserRole::SuperAdmin->value) {
            return response()->json([
                'message' => 'Superadmin users can only be created via Artisan command.'
            ], 403);
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (isset($validated['role'])) {
            $validated['role'] = UserRole::from($validated['role']);
        }

        if (isset($validated['status'])) {
            $statusVal = $validated['status'];
            $validated['is_active'] = ($statusVal === 'active' || $statusVal === true || $statusVal === 1 || $statusVal === '1');
            unset($validated['status']);
        }

        // Handle avatar upload or removal — must be done before update()
        unset($validated['avatar'], $validated['remove_avatar']);

        if ($request->hasFile('avatar')) {
            $admin->deleteAvatarFile();
            $admin->avatar_url = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->boolean('remove_avatar') || $request->input('remove_avatar') === '1') {
            $admin->deleteAvatarFile();
            $admin->avatar_url = null;
            $admin->save();
        }

        $admin->update($validated);

        return response()->json($admin->fresh());
    }

    /**
     * Delete an admin user (Disabled).
     */
    public function destroy(): JsonResponse
    {
        abort(403, 'Deleting admin accounts is disabled.');
    }

    public function block(Request $request, $id): JsonResponse
    {
        $admin = User::findOrFail($id);

        if (!in_array($admin->role, [UserRole::Admin, UserRole::SuperAdmin])) {
            abort(404, 'Admin not found');
        }

        $this->checkSuperAdminRestriction($admin);

        // Prevent self-blocking
        if ($request->user()->id === $admin->id) {
            abort(400, 'You cannot block yourself.');
        }

        $admin->update(['is_active' => false]);

        return response()->json(['success' => true]);
    }

    public function unblock($id): JsonResponse
    {
        $admin = User::findOrFail($id);

        if (!in_array($admin->role, [UserRole::Admin, UserRole::SuperAdmin])) {
            abort(404, 'Admin not found');
        }

        $this->checkSuperAdminRestriction($admin);

        $admin->update(['is_active' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Restore a deleted admin user (Disabled).
     */
    public function restore(): JsonResponse
    {
        abort(403, 'Deleting or restoring admin accounts is disabled.');
    }


    public function export(Request $request): BinaryFileResponse
    {
        $includeSuperAdmin = $request->boolean('include_superadmin') || $request->input('include_superadmin') === '1';

        if ($includeSuperAdmin) {
            $query = User::whereIn('role', [UserRole::Admin, UserRole::SuperAdmin]);
        } else {
            $query = User::where('role', UserRole::Admin);
        }

        if ($request->has('search') && $search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('role') && $role = $request->input('role')) {
            if ($role === UserRole::SuperAdmin->value && !$includeSuperAdmin) {
                $query->where('role', UserRole::Admin);
            } else {
                $query->where('role', $role);
            }
        }

        $status = $request->input('status');
        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } else {
            $query->withTrashed();
        }

        $sort = $request->query('sort_by', 'created_at');
        $order = $request->boolean('sort_desc', true) ? 'desc' : 'asc';

        $allowedSorts = ['id', 'name', 'email', 'created_at', 'role'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $order);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $admins = $query->get();

        $user = $request->user();
        $generatedBy = $user ? $user->name : 'SuperAdmin';

        $exportData = $admins->map(function ($adm) {
            $statusStr = 'Active';
            if ($adm->trashed()) {
                $statusStr = 'Deleted';
            } elseif (!$adm->is_active) {
                $statusStr = 'Inactive';
            }

            $roleStr = is_object($adm->role) ? $adm->role->value : (string) $adm->role;

            return [
                'name' => $adm->name,
                'email' => $adm->email ?? 'N/A',
                'phone' => $adm->phone ?? 'N/A',
                'role' => ucfirst($roleStr),
                'status' => $statusStr,
                'created_at' => $adm->created_at ? $adm->created_at->format('Y-m-d H:i') : 'N/A',
            ];
        })->toArray();

        $superAdminsCount = 0;
        $adminsCount = 0;
        foreach ($exportData as $item) {
            if (strtolower($item['role']) === 'superadmin' || strtolower($item['role']) === 'super_admin') {
                $superAdminsCount++;
            } else {
                $adminsCount++;
            }
        }

        $exportPayload = [
            'generated_at' => Carbon::now()->format('M j, Y h:i A'),
            'generated_by' => $generatedBy,
            'filters' => [
                'search' => $request->input('search') ?: 'None',
                'role' => $request->input('role') ? ucfirst($request->input('role')) : 'All',
                'status' => $status ? ucfirst($status) : 'All',
            ],
            'admins' => $exportData,
            'summary' => [
                'total_admins' => count($exportData),
                'super_admins_count' => $superAdminsCount,
                'admins_count' => $adminsCount,
            ]
        ];

        return Excel::download(
            new \App\Exports\AdminsExport($exportPayload),
            'Admins_Report_' . Carbon::today()->format('Y-m-d') . '.xlsx'
        );
    }
}
