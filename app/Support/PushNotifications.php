<?php

namespace App\Support;

use App\Models\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends a BoomBuy notification to the phones (Android app) of the user it is
 * for, through Firebase Cloud Messaging (HTTP v1). Needs the Firebase service
 * account key: FCM_CREDENTIALS in .env is its path. Without it, nothing is
 * sent and the website works as before.
 */
class PushNotifications
{
    /** The cookie the app keeps its push token in (see RememberPushToken). */
    public const COOKIE = 'bb_push_token';

    public static function enabled(): bool
    {
        return self::credentials() !== null;
    }

    public static function send(Notification $notification): void
    {
        $credentials = self::credentials();
        if (!$credentials) {
            return;
        }

        try {
            $tokens = DB::table('device_tokens')->where('user_id', $notification->user_id)->pluck('token');
            if ($tokens->isEmpty()) {
                return;
            }

            $accessToken = self::accessToken($credentials);
            $endpoint = 'https://fcm.googleapis.com/v1/projects/' . $credentials['project_id'] . '/messages:send';

            foreach ($tokens as $token) {
                $response = Http::withToken($accessToken)->timeout(10)->post($endpoint, [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $notification->title,
                            'body' => $notification->message,
                        ],
                        // Tapping it opens the notification (marked read) in the app.
                        'data' => ['url' => route('notifications.open', $notification->id)],
                        'android' => [
                            'priority' => 'high',
                            'notification' => ['channel_id' => 'boombuy', 'color' => '#F34F1D'],
                        ],
                    ],
                ]);

                // The app was uninstalled or the token replaced: stop sending to it.
                $status = $response->json('error.details.0.errorCode') ?? $response->json('error.status');
                if (in_array($status, ['UNREGISTERED', 'NOT_FOUND'], true)) {
                    DB::table('device_tokens')->where('token', $token)->delete();
                } elseif ($response->failed()) {
                    Log::warning('Push notification failed', ['status' => $response->status(), 'body' => $response->json()]);
                }
            }
        } catch (Throwable $e) {
            // A push is a bonus: the notification is already saved on the site.
            Log::warning('Push notification failed: ' . $e->getMessage());
        }
    }

    /** The service account key, or null when push is not set up. */
    private static function credentials(): ?array
    {
        $path = config('services.fcm.credentials');
        if (!$path || !is_file($path)) {
            return null;
        }

        $key = json_decode((string) file_get_contents($path), true);

        return isset($key['project_id'], $key['client_email'], $key['private_key']) ? $key : null;
    }

    /** OAuth access token for FCM, from a signed JWT; kept for 55 minutes. */
    private static function accessToken(array $credentials): string
    {
        return Cache::remember('fcm_access_token', now()->addMinutes(55), function () use ($credentials) {
            $now = time();
            $encode = fn (array $part) => rtrim(strtr(base64_encode(json_encode($part)), '+/', '-_'), '=');
            $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']) . '.' . $encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]);
            openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
            $jwt = $unsigned . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            return Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ])->throw()->json('access_token');
        });
    }
}
