<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Admin\AdminUser\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    /**
     * Handle admin login with email/username and password.
     */
    public function loginAdmin(AdminLoginRequest $request)
    {
        $throttleKey = $request->ip();

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);
            throw ValidationException::withMessages([
                'login' => ["Too many login attempts. Please try again in {$minutes} minutes."],
            ]);
        }

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($loginType, $request->login)->first();

        $isInvalidRole = $user && !in_array($user->role, ['admin', 'superadmin', \App\Enums\UserRole::Admin, \App\Enums\UserRole::SuperAdmin], true);

        if (!$user || $isInvalidRole || !Hash::check($request->password, $user->password)) {
            \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 300); // 5 minutes block
            throw ValidationException::withMessages([
                'login' => ['Invalid credentials.'],
            ]);
        }

        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

        if (isset($user->is_active) && !$user->is_active) {
            return response()->json([
                'message' => 'Your account is inactive. Please contact an administrator.'
            ], 403);
        }

        $isSuperAdmin = $user->isSuperAdmin() || $user->role === 'superadmin' || $user->role === \App\Enums\UserRole::SuperAdmin;
        $tokenName = $isSuperAdmin ? 'superadmin_auth_token' : 'admin_auth_token';
        $token = $user->createToken($tokenName, ['*'], $this->getTokenExpiration($user))->plainTextToken;

        \App\Services\NotificationService::notifyAdmins(new \App\Notifications\UserLoginNotification($user));

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    private function getTokenExpiration(User $user)
    {
        $role = $user->role;

        if ($user->isSuperAdmin() || $role === 'superadmin' || $role === \App\Enums\UserRole::SuperAdmin) {
            return now()->addHours(12);
        }

        return now()->addHours(24);
    }
}
