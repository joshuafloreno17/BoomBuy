<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| PREVENT BACK-BUTTON CACHE
|--------------------------------------------------------------------------
|
| Without this, the browser can restore a logged-in page (dashboard,
| orders, etc.) straight from its back/forward cache after the user has
| logged out — the session is really gone server-side, but pressing Back
| shows the stale, still-"logged in"-looking page instead of asking the
| server again. Telling the browser never to store these pages closes
| that gap.
|
*/

class PreventBackHistoryCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, private'
        );

        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
