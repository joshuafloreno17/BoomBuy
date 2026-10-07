<?php

namespace Tests\Feature;

use App\Models\Complaint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class PrivacyAndSellerOrdersTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    /** A real 1x1 PNG (this machine has no GD, so fake()->image() can't draw one). */
    private function photo(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
        );
    }

    public function test_every_role_can_change_their_profile_photo_and_a_non_image_is_refused(): void
    {
        Storage::fake('public');

        $users = [
            'buyer.profile.photo' => $this->makeUser('buyer'),
            'seller.profile.photo' => $this->makeSeller(),
            'rider.profile.photo' => $this->makeRider(),
            'logistics.profile.photo' => $this->makeLogistics(),
        ];

        foreach ($users as $route => $user) {
            $this->flushSession();

            $this->actingAsUser($user)
                ->post(route($route), ['profile_photo' => $this->photo('me.png')])
                ->assertSessionHas('success');

            $saved = DB::table('users')->where('id', $user->id)->value('profile_photo');
            $this->assertStringStartsWith($user->role . '_' . $user->id . '_', $saved);
            $this->assertStringEndsWith('.png', $saved);
            Storage::disk('public')->assertExists('profile-photos/' . $saved);
            $this->assertSame($saved, session('user')['profile_photo']);
        }

        // Anything that isn't an image is refused by the shared rule. (Fake
        // uploads report their type from the name; real ones from the content.)
        $this->flushSession();
        $buyer = $users['buyer.profile.photo'];
        $before = DB::table('users')->where('id', $buyer->id)->value('profile_photo');

        $this->actingAsUser($buyer)
            ->post(route('buyer.profile.photo'), [
                'profile_photo' => UploadedFile::fake()->createWithContent('notes.txt', 'not a picture'),
            ])
            ->assertSessionHasErrors('profile_photo');

        $this->assertSame($before, DB::table('users')->where('id', $buyer->id)->value('profile_photo'));
    }

    public function test_seller_orders_have_tabs_search_and_always_open(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['name' => 'Trail Runner']);
        $alice = $this->makeUser('buyer', ['name' => 'Alice Reyes']);

        $pending = $this->makeOrder($alice, $product, 'Pending');
        $done = $this->makeOrder($this->makeUser(), $product, 'Delivered', ['buyer_received_at' => now()]);

        $this->actingAsUser($seller)->get(route('seller.orders', ['tab' => 'to-process']))
            ->assertOk()
            ->assertSee('Order #' . $pending)
            ->assertDontSee('Order #' . $done);

        // A completed, received order can still be opened.
        $this->actingAsUser($seller)->get(route('seller.orders', ['tab' => 'completed']))
            ->assertSee('Order #' . $done)
            ->assertSee(route('seller.order.details', ['id' => $done]), false);

        $this->actingAsUser($seller)->get(route('seller.orders', ['q' => 'Alice']))
            ->assertSee('Order #' . $pending)
            ->assertDontSee('Order #' . $done);

        $this->actingAsUser($seller)->get(route('seller.orders', ['q' => 'Trail']))
            ->assertSee('Order #' . $pending)
            ->assertSee('Order #' . $done);
    }

    public function test_seller_never_sees_another_sellers_orders(): void
    {
        $mine = $this->makeSeller();
        $other = $this->makeSeller('electronics', 'Other Shop');
        $theirs = $this->makeOrder($this->makeUser(), $this->makeProduct($other), 'Pending');

        $this->actingAsUser($mine)->get(route('seller.orders'))
            ->assertOk()
            ->assertDontSee('Order #' . $theirs);
    }

    public function test_seller_dashboard_has_a_product_search(): void
    {
        $seller = $this->makeSeller();
        $this->makeProduct($seller, ['name' => 'Canvas Slip-on']);

        $this->actingAsUser($seller)->get(route('seller.dashboard'))
            ->assertOk()
            ->assertSee('id="productSearch"', false)
            ->assertSee('data-product-search="canvas slip-on', false);
    }

    public function test_complaint_evidence_is_private(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $buyer = $this->makeUser();

        $this->actingAsUser($buyer)->post(route('complaints.store'), [
            'subject' => 'Broken item',
            'description' => 'See photo.',
            'evidence' => $this->photo('proof.png'),
        ])->assertSessionHas('success');

        $complaint = Complaint::first();

        Storage::disk('local')->assertExists($complaint->evidence);
        Storage::disk('public')->assertMissing($complaint->evidence);

        $this->actingAsUser($buyer)->get(route('complaints.evidence', $complaint->id))->assertOk();

        $this->flushSession();
        $this->actingAsUser($this->makeUser())->get(route('complaints.evidence', $complaint->id))->assertForbidden();

        $this->flushSession();
        $this->actingAsAdmin()->get(route('complaints.evidence', $complaint->id))->assertOk();
    }

    public function test_return_evidence_is_only_for_the_buyer_and_seller(): void
    {
        Storage::fake('local');

        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $orderId = $this->makeOrder($buyer, $this->makeProduct($seller), 'Delivered', ['buyer_received_at' => now()]);
        $itemId = DB::table('order_items')->where('order_id', $orderId)->value('id');

        $this->actingAsUser($buyer)->post(route('buyer.return-refund.store', $orderId), [
            'order_item_id' => $itemId,
            'request_type' => 'Return',
            'reason' => 'Damaged item',
            'evidence' => $this->photo('damage.png'),
            'refund_method' => 'GCash',
            'refund_account_name' => 'Buyer Name',
            'refund_account_number' => '09171234567',
        ])->assertSessionHas('success');

        $requestId = DB::table('return_refund_requests')->value('id');

        $this->actingAsUser($buyer)->get(route('return-refund.evidence', $requestId))->assertOk();

        $this->flushSession();
        $this->actingAsUser($seller)->get(route('return-refund.evidence', $requestId))->assertOk();

        $this->flushSession();
        $this->actingAsUser($this->makeSeller('electronics', 'Nosy Shop'))
            ->get(route('return-refund.evidence', $requestId))
            ->assertForbidden();
    }

    public function test_forgot_password_does_not_reveal_who_has_an_account(): void
    {
        Mail::fake();
        // Registration always stores emails in lowercase.
        $buyer = $this->makeUser('buyer', ['email' => 'reset-me@example.com']);

        $known = $this->post(route('password.email'), ['email' => $buyer->email]);
        $this->flushSession();
        $unknown = $this->post(route('password.email'), ['email' => 'nobody-' . uniqid() . '@example.com']);

        $known->assertRedirect(route('password.reset'));
        $unknown->assertRedirect(route('password.reset'));
        $this->assertSame($known->getSession()->get('success'), $unknown->getSession()->get('success'));

        Mail::assertSentCount(1);

        // No code can ever work for the unknown email.
        $this->post(route('password.update'), [
            'otp_code' => '000000',
            'password' => 'whatever-123',
            'password_confirmation' => 'whatever-123',
        ])->assertSessionHas('error');
    }

    public function test_buyer_registration_waits_for_the_administrator(): void
    {
        $this->withSession([
            'pending_registration' => [
                'name' => 'New Buyer',
                'email' => 'newbuyer@example.com',
                'password' => bcrypt('password123'),
                'role' => 'buyer',
                'phone' => '09170000001',
                'address' => '1 Test St',
            ],
            'otp_code' => '654321',
            'otp_expires_at' => now()->addMinutes(10),
        ])->post(route('otp.verify'), ['otp_code' => '654321'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, "administrator's approval"));

        $userId = DB::table('users')->where('email', 'newbuyer@example.com')->value('id');

        $this->assertNotNull($userId);
        $this->assertDatabaseHas('buyer_applications', ['user_id' => $userId, 'status' => 'Pending Verification']);
        $this->assertNull(session('user.id'));
    }

    public function test_pending_and_rejected_buyers_cannot_log_in_until_approved(): void
    {
        Mail::fake();
        $buyer = $this->makeUser('buyer', ['email' => 'waiting@example.com', 'password' => bcrypt('password123')]);
        $appId = DB::table('buyer_applications')->insertGetId([
            'user_id' => $buyer->id, 'full_name' => $buyer->name, 'status' => 'Pending Verification',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->post(route('login.submit'), ['email' => 'waiting@example.com', 'password' => 'password123'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'pending verification'));
        $this->assertNull(session('user.id'));

        // The admin approves: an email goes out and the buyer can log in.
        $this->actingAsAdmin()->get(route('admin.applications', ['type' => 'buyer']))->assertOk()->assertSee($buyer->name);
        DB::table('buyer_applications')->where('id', $appId)->update(['status' => 'Approved']);
        $this->flushSession();
        $this->post(route('login.submit'), ['email' => 'waiting@example.com', 'password' => 'password123'])
            ->assertSessionMissing('error');
        $this->assertSame($buyer->id, session('user.id'));
    }

    public function test_a_buyer_from_before_the_approval_step_can_still_log_in(): void
    {
        // Old accounts have no application row at all.
        $buyer = $this->makeUser('buyer', ['email' => 'legacy@example.com', 'password' => bcrypt('password123')]);
        DB::table('buyer_applications')->where('user_id', $buyer->id)->delete();

        $this->post(route('login.submit'), ['email' => 'legacy@example.com', 'password' => 'password123'])
            ->assertSessionMissing('error');
        $this->assertSame($buyer->id, session('user.id'));
    }
}
