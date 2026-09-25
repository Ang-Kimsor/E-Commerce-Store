<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            if (!$user->is_active || (method_exists($user, 'trashed') && $user->trashed())) {
                try {
                    $user->currentAccessToken()?->delete();
                } catch (\Throwable $e) {
                }

                return response()->json([
                    'message' => 'Your account is inactive. Please contact an administrator.'
                ], 403);
            }
        }

        return $next($request);
    }
}
