<?php

namespace App\Http\Middleware;

use App\Support\ApiToken;
use App\Support\LoginGate;
use App\Support\MobileAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The mobile app's sign-in: a Bearer token of an account that may still log
 * in today (Active, approved, a mobile role), with a role this route is for.
 * Usage: ->middleware('api.token') for any mobile role, or
 *        ->middleware('api.token:buyer') / ('api.token:seller,rider').
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $found = ApiToken::find($request->bearerToken());

        if (!$found) {
            return response()->json(['message' => 'Please log in again.'], 401);
        }

        $user = $found['user'];

        // Suspended, deactivated, or no longer approved since the token was issued.
        if ($reason = LoginGate::blockReason($user)) {
            ApiToken::revokeAll((int) $user->id);

            return response()->json(['message' => $reason], 401);
        }

        // The role was changed to one that belongs on the website.
        if (!MobileAccess::allows($user->role)) {
            ApiToken::revokeAll((int) $user->id);

            return response()->json(['message' => MobileAccess::DESKTOP_ONLY_MESSAGE, 'code' => 'desktop_only'], 403);
        }

        if ($roles && !in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'This part of the app is not for your account.'], 403);
        }

        $request->attributes->set('apiUser', $user);

        return $next($request);
    }
}
