<?php

namespace Tests\Feature;

use App\Support\PushNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * The Android app's phones get their user's notifications as push
 * notifications (Firebase Cloud Messaging).
 */
class PushNotificationsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private const APP = 'Mozilla/5.0 (Linux; Android 16) Chrome/140 Mobile Safari/537.36 BoomBuyApp/1.4.0';
    private const TOKEN = 'fcm-token_ABCdef123:APA91bTestTokenValue';

    private ?string $keyFile = null;

    protected function tearDown(): void
    {
        if ($this->keyFile) {
            @unlink($this->keyFile);
        }
        parent::tearDown();
    }

    /** A made-up Firebase service account key, so a push can be "sent". */
    private function fakeFirebase(?\Closure $fcm = null): void
    {
        // PHP on Windows needs to be told where openssl.cnf is.
        $cnf = dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf';
        $options = is_file($cnf) ? ['config' => $cnf] : [];
        $key = openssl_pkey_new($options + ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem, null, $options);
        $this->keyFile = tempnam(sys_get_temp_dir(), 'fcm');
        file_put_contents($this->keyFile, json_encode([
            'project_id' => 'boombuy-test',
            'client_email' => 'push@boombuy-test.iam.gserviceaccount.com',
            'private_key' => $pem,
        ]));
        config(['services.fcm.credentials' => $this->keyFile]);

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'test-access-token']),
            'fcm.googleapis.com/*' => $fcm ?? Http::response(['name' => 'projects/boombuy-test/messages/1']),
        ]);
    }

    private function openFromApp(string $url = '/')
    {
        return $this->withHeader('User-Agent', self::APP)
            ->withUnencryptedCookie(PushNotifications::COOKIE, self::TOKEN)
            ->get($url);
    }

    public function test_the_app_phone_is_tied_to_whoever_is_signed_in(): void
    {
        $buyer = $this->makeUser('buyer');
        $this->actingAsUser($buyer)->openFromApp();

        $this->assertDatabaseHas('device_tokens', ['token' => self::TOKEN, 'user_id' => $buyer->id]);

        // Someone else signs in on the same phone: it moves to them.
        $seller = $this->makeSeller();
        $this->flushSession();
        $this->actingAsUser($seller)->openFromApp();

        $this->assertDatabaseHas('device_tokens', ['token' => self::TOKEN, 'user_id' => $seller->id]);
        $this->assertDatabaseCount('device_tokens', 1);
    }

    public function test_signed_out_phones_and_browsers_are_not_kept(): void
    {
        $buyer = $this->makeUser('buyer');
        $this->actingAsUser($buyer)->openFromApp();

        // Signed out on that phone.
        $this->withSession(['user' => null])->openFromApp();
        $this->assertDatabaseCount('device_tokens', 0);

        // A browser with the cookie (not the app) is ignored.
        $this->actingAsUser($buyer)
            ->withUnencryptedCookie(PushNotifications::COOKIE, self::TOKEN)
            ->get('/');
        $this->assertDatabaseCount('device_tokens', 0);
    }

    public function test_a_new_notification_is_pushed_to_the_users_phone(): void
    {
        $this->fakeFirebase();
        $seller = $this->makeSeller();
        $this->actingAsUser($seller)->openFromApp();

        $notification = createNotification($seller->id, 'New order', 'Order #12 is waiting for you.', 'order', 12);
        app()->terminate();

        Http::assertSent(function (Request $request) use ($notification) {
            return str_contains($request->url(), 'fcm.googleapis.com/v1/projects/boombuy-test/messages:send')
                && $request->hasHeader('Authorization', 'Bearer test-access-token')
                && $request['message']['token'] === self::TOKEN
                && $request['message']['notification']['title'] === 'New order'
                && $request['message']['data']['url'] === route('notifications.open', $notification->id);
        });
    }

    public function test_uninstalled_phones_are_forgotten(): void
    {
        $this->fakeFirebase(fn () => Http::response(['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404));
        $buyer = $this->makeUser('buyer');
        $this->actingAsUser($buyer)->openFromApp();

        createNotification($buyer->id, 'Order shipped', 'Your order is on its way.', 'order_status', 3);
        app()->terminate();

        $this->assertDatabaseCount('device_tokens', 0);
    }

    public function test_without_a_firebase_key_nothing_is_sent(): void
    {
        Http::fake();
        config(['services.fcm.credentials' => null]);
        $buyer = $this->makeUser('buyer');
        $this->actingAsUser($buyer)->openFromApp();

        createNotification($buyer->id, 'Hello', 'Just the website.', null, null);
        app()->terminate();

        Http::assertNothingSent();
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'title' => 'Hello']);
    }
}
