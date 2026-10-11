<?php

namespace App\Http\Middleware;

use App\Support\LoginGate;
use App\Support\MobileAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The BoomBuy Android app shows this website. Inside the app only buyers,
 * sellers and riders may be signed in: a logistics or admin account is
 * signed out again and sent back to the login page with a message to use a
 * computer. Browsers (desktop or phone) are never affected.
 */
class KeepAppToMobileRoles
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!MobileAccess::isApp($request)) {
            return $next($request);
        }

        if ($this->desktopOnly()) {
            return $this->signOut();
        }

        $response = $next($request);

        // Signed in during this request (the login form): stop it right away.
        return $this->desktopOnly() ? $this->signOut() : $response;
    }

    private function desktopOnly(): bool
    {
        $user = session('user');

        return (bool) session('admin_logged_in') || ($user && !MobileAccess::allows($user['role'] ?? null));
    }

    private function signOut(): Response
    {
        LoginGate::forget(session('user.id'));
        session()->forget(['user', 'admin_logged_in']);
        session()->regenerate(true);

        return redirect()->route('login')->with('error', MobileAccess::DESKTOP_ONLY_MESSAGE);
    }
}
