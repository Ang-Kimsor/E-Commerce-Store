<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddNgrokHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add ngrok bypass header to skip the browser warning
        $response->headers->set('ngrok-skip-browser-warning', 'true');
        
        // Allow Telegram to display this app in iframe
        $response->headers->set('X-Frame-Options', 'ALLOWALL');
        
        // Alternative CSP headers for iframe support
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' https://web.telegram.org https://telegram.org");

        return $response;
    }
}
