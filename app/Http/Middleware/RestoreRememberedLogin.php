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

        return $next($request);
    }
}
