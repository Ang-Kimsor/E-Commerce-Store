<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->is_active || !$user->isAdmin()) {
            return response()->json([
                'message' => ($user && !$user->is_active)
                    ? 'Your account is inactive. Please contact a super administrator.'
                    : 'You do not have permission to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}
