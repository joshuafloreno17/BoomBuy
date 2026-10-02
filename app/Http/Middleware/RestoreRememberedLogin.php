<?php

namespace App\Http\Middleware;

use App\Support\LoginGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestoreRememberedLogin
{
    /**
     * "Remember me": once the normal session has expired, log the browser
     * back in from its remember cookie — but only if the account would still
     * pass the login form's checks today.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookie = $request->cookie(LoginGate::REMEMBER_COOKIE);

        if ($cookie && !$request->session()->has('user') && !$request->session()->get('admin_logged_in')) {

            $user = LoginGate::rememberedUser($cookie);

            if ($user && LoginGate::blockReason($user) === null) {
                LoginGate::startSession($user);
            } else {
                // Stale, forged or no-longer-allowed — stop trying.
                LoginGate::forget($user->id ?? null);
            }
        }

        // Was logged in on this browser, but the session ran out (and no
        // "Remember me" brought it back): say so once, on whatever page this is.
        $expired = !$request->session()->has('user')
            && !$request->session()->get('admin_logged_in')
            && $request->cookie(LoginGate::SIGNED_IN_COOKIE);

        if ($expired) {
            $request->session()->flash('bb_notice', 'You were logged out after being inactive for a while. Please log in again.');
            LoginGate::signedOut();
        }

        $response = $next($request);

        // Shown on this page already — don't repeat it on the next one.
        if ($expired && !$response->isRedirection()) {
            $request->session()->forget('bb_notice');
        }

        return $response;
    }
}
