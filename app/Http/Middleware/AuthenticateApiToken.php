<?php

namespace App\Http\Middleware;

use App\Support\ApiToken;
use App\Support\LoginGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The mobile app's sign-in: a Bearer token of an account that may still log
 * in today (Active, approved), with the role this route is for.
 * Usage: ->middleware('api.token:rider')
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next, string $role = 'rider'): Response
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

        if ($user->role !== $role) {
            return response()->json(['message' => 'This app is for ' . $role . 's.'], 403);
        }

        $request->attributes->set('apiUser', $user);

        return $next($request);
    }
}
