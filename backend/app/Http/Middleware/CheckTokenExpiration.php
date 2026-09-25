<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($token = $user->currentAccessToken()) && $token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $role = $user->role;
            $expiresAt = null;

            if ($user->isSuperAdmin() || $role === 'superadmin' || $role === \App\Enums\UserRole::SuperAdmin) {
                $expiresAt = now()->addHours(12);
            } elseif ($user->isAdmin() || $role === 'admin' || $role === \App\Enums\UserRole::Admin) {
                $expiresAt = now()->addHours(24);
            } else {
                $expiresAt = now()->addDays(30);
            }

            // Do not extend token expiration for background polling requests (e.g. fetching notifications)
            $isPollingRequest = $request->is('api/admin/notifications');

            // Only update if not a polling request and the difference is more than a minute
            if (!$isPollingRequest && (!$token->expires_at || $expiresAt->diffInMinutes($token->expires_at) > 1)) {
                $token->update(['expires_at' => $expiresAt]);
            }
        }

        return $next($request);
    }
}
