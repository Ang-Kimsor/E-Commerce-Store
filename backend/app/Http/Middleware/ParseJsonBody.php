<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fix JSON body parsing for PHP built-in server (php -S).
 *
 * The PHP built-in development server does not populate $_POST or
 * php://input reliably for JSON requests in all Laravel versions.
 * This middleware reads the raw input stream and merges parsed JSON
 * data into the request, ensuring $request->all() / validate() work.
 */
class ParseJsonBody
{
    public function handle(Request $request, Closure $next): Response
    {
        $contentType = $request->header('Content-Type', '');

        if (str_contains($contentType, 'application/json') && $request->getContent()) {
            $data = json_decode($request->getContent(), true);

            if (is_array($data) && !empty($data)) {
                $request->merge($data);
            }
        }

        return $next($request);
    }
}
