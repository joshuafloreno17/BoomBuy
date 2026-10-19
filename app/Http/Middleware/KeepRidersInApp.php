<?php

namespace App\Http\Middleware;

use App\Support\LoginGate;
use App\Support\MobileAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Riders work only in the BoomBuy Android app (which shows this website).
 * In a browser the rider pages and the rider application send people to the
 * app download page, and a rider who is still signed in is signed out.
 * The activation link a logistics company emails still opens anywhere.
 */
class KeepRidersInApp
{
    public function handle(Request $request, Closure $next): Response
    {
        MobileAccess::rememberApp($request);

        if (!MobileAccess::riderBlockedHere($request)) {
            return $next($request);
        }

        if (session('user.role') === 'rider') {
            LoginGate::forget(session('user.id'));
            session()->forget('user');
            session()->regenerate(true);

            return $this->toApp();
        }

        $route = (string) $request->route()?->getName();

        if (str_starts_with($route, 'rider.') && !str_starts_with($route, 'rider.activate')) {
            return $this->toApp();
        }

        return $next($request);
    }

    private function toApp(): Response
    {

        return redirect()->route('app.download')->with('error', MobileAccess::RIDER_APP_ONLY_MESSAGE);
    }
}
