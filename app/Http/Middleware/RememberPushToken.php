<?php

namespace App\Http\Middleware;

use App\Support\MobileAccess;
use App\Support\PushNotifications;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Android app puts its push address (a Firebase token) in the
 * "bb_push_token" cookie. Tie it to whoever is signed in on that phone, so
 * their notifications reach it; once nobody is signed in, forget it.
 */
class RememberPushToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $token = (string) $request->cookie(PushNotifications::COOKIE);
        if (!MobileAccess::isApp($request) || !preg_match('/^[A-Za-z0-9:_\-]{20,255}$/', $token)) {
            return $response;
        }

        $user = session('user');
        if ($user && MobileAccess::allows($user['role'] ?? null)) {
            // Once per sign-in, not on every page.
            if (session('push_token') !== $token) {
                DB::table('device_tokens')->updateOrInsert(
                    ['token' => $token],
                    ['user_id' => $user['id'], 'updated_at' => now(), 'created_at' => now()],
                );
                session(['push_token' => $token]);
            }
        } else {
            DB::table('device_tokens')->where('token', $token)->delete();
        }

        return $response;
    }
}
