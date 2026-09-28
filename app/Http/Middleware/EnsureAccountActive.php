<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    /**
     * Login only checks the account status once. Without this, a user who is
     * suspended/deactivated by an admin while already logged in keeps full
     * access until their session ends. Re-check the status on every request
     * and log them out as soon as it's no longer Active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionUser = $request->session()->get('user');

        if (!$sessionUser || empty($sessionUser['id'])) {
            return $next($request);
        }

        $status = DB::table('users')
            ->where('id', $sessionUser['id'])
            ->value('status');

        // A missing row (null) also falls through and gets logged out.
        if ($status === 'Active') {
            return $next($request);
        }

        $request->session()->forget('user');
        \App\Support\LoginGate::forget((int) $sessionUser['id']);

        $message = $status === 'Suspended'
            ? 'Your account has been suspended. Please contact BoomBuy support for assistance.'
            : 'Your account has been deactivated. Please contact BoomBuy support for assistance.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        return redirect()
            ->route('login')
            ->with('error', $message);
    }
}
