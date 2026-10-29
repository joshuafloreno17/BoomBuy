<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Basic browser protections on every page: no framing by other sites
 * (clickjacking), no guessing file types, and only our origin in the
 * Referer sent to other sites.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $response->headers->set('X-Content-Type-Options', 'nosniff', false);
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        // Added by PHP itself (expose_php), not by the response.
        header_remove('X-Powered-By');

        return $response;
    }
}
