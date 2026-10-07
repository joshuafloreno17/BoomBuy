<?php

namespace Tests\Feature\Scenarios;

use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\SortingCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Test Plan → "Rider" (R01–R10), plus the rider bugs from the review. */
class RiderScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private SortingCenter $laguna;
    private User $staff;
    private User $rider;
    private User $buyer;
    private Product $shoes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();

        $this->laguna = $this->center('Laguna', 'Santa Cruz');
        $this->staff = $this->staffAt($this->laguna);
        $this->rider = $this->riderFor($this->laguna);
        $this->buyer = $this->buyerAt();
        $this->shoes = $this->makeProduct($this->sellerIn('Laguna', 'Santa Cruz'), ['price' => 500]);
    }

    /** An order at the Laguna center, handed to $rider. */
    private function assigned(string $status = 'Assigned for Delivery', array $over = []): int
    {
        return $this->makeOrder($this->buyer, $this->shoes, $status, array_merge([
            'shipping_address' => self::LAGUNA_ADDRESS,
            'shipping_province' => 'Laguna',
            'shipping_city' => 'Santa Cruz',
            'origin_center_id' => $this->laguna->id,
            'destination_center_id' => $this->laguna->id,
            'current_center_id' => $this->laguna->id,
            'delivery_rider_id' => $this->rider->id,
            'delivery_zone' => 'local',
            'delivery_fee' => 50,
            'total_amount' => 550,
        ], $over));
    }

    private function asRider(): static
    {
        return $this->actingAsUser($this->rider);
    }

    public function test_R01_rider_logs_in_only_after_logistics_approves(): void
    {
        $applicant = $this->makeRider('Pending Verification');
        DB::table('users')->where('id', $applicant->id)->update(['province' => 'Laguna', 'city_municipality' => 'Santa Cruz']);

        $this->post(route('login.submit'), ['email' => $applicant->email, 'password' => 'password123'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'pending verification'));

        $applicationId = DB::table('rider_applications')->where('user_id', $applicant->id)->value('id');
        $this->actingAsUser($this->staff)->post(route('logistics.riders.approve', $applicationId))->assertSessionHas('success');
        Mail::assertSent(\App\Mail\ApplicationStatusMail::class);

        $this->flushSession();
        $this->post(route('login.submit'), ['email' => $applicant->email, 'password' => 'password123'])
            ->assertRedirect(route('rider.dashboard'));
    }

    public function test_R01b_rejected_rider_can_apply_again_with_the_same_email(): void
    {
        $rejected = $this->makeRider('Rejected');

        $this->post(route('rider.apply.submit'), [
            'last_name' => 'Reyes', 'first_name' => 'Jun', 'sex' => 'Male', 'birthdate' => '1995-03-03',
            'email' => $rejected->email, 'password' => 'secret123', 'password_confirmation' => 'secret123',
            'phone' => $rejected->phone, 'province' => 'Laguna', 'city_municipality' => 'Santa Cruz', 'barangay' => 'Poblacion',
            'street_address' => '2 Rizal St', 'vehicle_type' => 'Motorcycle', 'vehicle_model' => 'Honda Beat', 'plate_number' => 'XYZ 9876',
            'national_id' => $this->png(), 'drivers_license' => $this->png(), 'profile_selfie' => $this->png(),
            'proof_of_address' => $this->pdf(), 'or_cr' => $this->pdf(), 'terms' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])->assertRedirect(route('login'));

        $this->assertSame(1, User::where('email', $rejected->email)->count());
        $this->assertSame('Pending Verification', DB::table('rider_applications')->where('user_id', $rejected->id)->orderByDesc('id')->value('status'));
        $this->assertSame('XYZ 9876', DB::table('rider_applications')->where('user_id', $rejected->id)->orderByDesc('id')->value('plate_number'));
    }

    public function test_R02_dashboard_lists_assignments_and_cash_to_hand_in(): void
    {
        $this->assigned();
        $this->assigned('Delivered', ['payment_method' => 'Cash on Delivery', 'cod_collected_at' => now(), 'delivered_at' => now()]);

        $this->asRider()->get(route('rider.dashboard'))->assertOk()
            ->assertViewHas('myDeliveryAssignments', fn ($a) => count($a) === 1)
            ->assertViewHas('codHeld', fn ($c) => $c->count() === 1 && (float) $c->sum('total_amount') === 550.0)
            ->assertSee('Cash to hand in');
    }

    public function test_R03_cannot_jump_from_assigned_to_delivered(): void
    {
        $orderId = $this->assigned();

        $this->asRider()->post(route('rider.delivery.status', $orderId), ['status' => 'Delivered', 'delivery_proof' => $this->deliveryPhoto()])
            ->assertSessionHas('error', 'This order must be moved to Out for Delivery first.');
        $this->assertSame('Assigned for Delivery', $this->orderStatus($orderId));
    }

    public function test_R04_delivered_needs_a_photo_and_cod_is_marked_collected(): void
    {
        $orderId = $this->assigned('Out for Delivery');

        $this->asRider()->post(route('rider.delivery.status', $orderId), ['status' => 'Delivered'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Take a photo'));

        $this->post(route('rider.delivery.status', $orderId), ['status' => 'Delivered', 'delivery_proof' => $this->deliveryPhoto()])
            ->assertSessionHas('success');

        $order = DB::table('orders')->find($orderId);
        $this->assertSame('Delivered', $order->status);
        $this->assertNotNull($order->delivery_proof);
        $this->assertNotNull($order->cod_collected_at);
        Storage::disk('local')->assertExists($order->delivery_proof);
    }

    public function test_R05_failed_delivery_reasons_and_refusal_is_final(): void
    {
        $other = $this->assigned('Out for Delivery');
        $this->asRider()->post(route('rider.delivery.status', $other), ['status' => 'Delivery Failed', 'failure_code' => 'Other'])
            ->assertSessionHas('error', 'Please describe why the delivery failed.');
        $this->post(route('rider.delivery.status', $other), ['status' => 'Delivery Failed', 'failure_code' => 'Buyer not available'])
            ->assertSessionHas('success');
        $this->assertSame(1, (int) DB::table('orders')->where('id', $other)->value('delivery_attempts'));

        $refused = $this->assigned('Out for Delivery');
        $this->post(route('rider.delivery.status', $refused), ['status' => 'Delivery Failed', 'failure_code' => \App\Services\DeliveryService::REFUSED_REASON])
            ->assertSessionHas('success');
        $this->assertNotNull(DB::table('orders')->where('id', $refused)->value('buyer_refused_at'));
        $this->assertSame(1, \App\Support\CodPolicy::status($this->buyer->id)['strikes']);
    }

    public function test_R05b_failed_parcel_must_be_back_at_the_center_before_it_is_sent_out_again(): void
    {
        $orderId = $this->assigned('Out for Delivery');
        $this->asRider()->post(route('rider.delivery.status', $orderId), ['status' => 'Delivery Failed', 'failure_code' => 'Buyer not available']);

        // The rider still has the parcel; nobody at the center has confirmed it came back.
        $other = $this->riderFor($this->laguna);
        $this->flushSession();
        $this->actingAsUser($this->staff)->post(route('logistics.parcels.reschedule', $orderId), ['rider_id' => $other->id])
            ->assertSessionHas('error');
        $this->post(route('logistics.parcels.return-to-seller', $orderId))->assertSessionHas('error');

        // Once the center confirms it's back, it can go out again.
        $this->post(route('logistics.parcels.confirm-back', $orderId))->assertSessionHas('success');
        $this->post(route('logistics.parcels.confirm-back', $orderId))->assertSessionHas('error');
        $this->post(route('logistics.parcels.reschedule', $orderId), ['rider_id' => $other->id])->assertSessionHas('success');
    }

    public function test_R06_another_riders_delivery_is_not_found(): void
    {
        $orderId = $this->assigned();
        $stranger = $this->riderFor($this->laguna);

        $this->actingAsUser($stranger)->get(route('rider.delivery.details', $orderId))->assertNotFound();
        $this->post(route('rider.delivery.status', $orderId), ['status' => 'Out for Delivery'])
            ->assertSessionHas('error', 'This delivery is not assigned to you.');
    }

    public function test_R07_deactivated_rider_is_logged_out_and_parcels_go_back_to_the_queue(): void
    {
        $orderId = $this->assigned('Out for Delivery');
        // Cash they collected yesterday and haven't handed in.
        $this->assigned('Delivered', ['delivered_at' => now()->subDay(), 'cod_collected_at' => now()->subDay(), 'total_amount' => 550]);
        $this->asRider()->get(route('rider.dashboard'))->assertOk();

        $this->flushSession();
        $this->actingAsUser($this->staff)->post(route('logistics.riders.toggle-status', $this->rider->id), ['status' => 'Deactivated'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, '₱550.00 in COD cash'));
        $this->assertTrue($this->notifiedWith($this->staff, 'Collect Cash From Rider'));

        $this->assertSame('Sorted', $this->orderStatus($orderId));
        $this->assertNull(DB::table('orders')->where('id', $orderId)->value('delivery_rider_id'));

        $this->flushSession();
        $this->asRider()->get(route('rider.dashboard'))->assertRedirect(route('login'));
    }

    public function test_R08_profit_and_history_by_date(): void
    {
        $this->assigned('Delivered', ['delivered_at' => now()->subDays(2)]);
        $this->assigned('Delivered', ['delivered_at' => now()->subDays(40)]);

        $this->asRider()->get(route('rider.profit'))->assertOk()
            ->assertViewHas('totalDeliveries', 1)
            ->assertViewHas('totalProfit', 50.0);
        $this->get(route('rider.profit', ['from' => now()->subDays(60)->toDateString()]))
            ->assertViewHas('totalDeliveries', 2);
        $this->get(route('rider.deliveries.history'))->assertOk()
            ->assertViewHas('history', fn ($h) => $h->count() === 1);
    }

    public function test_R08b_changing_the_fee_later_does_not_change_past_earnings(): void
    {
        // Delivered the normal way, while the fee is ₱50.
        $orderId = $this->assigned('Out for Delivery');
        $this->asRider()->post(route('rider.delivery.status', $orderId), ['status' => 'Delivered', 'delivery_proof' => $this->deliveryPhoto()]);
        $before = $this->get(route('rider.profit'))->viewData('totalProfit');
        $this->assertEquals(50, $before);

        PlatformSetting::set('delivery_fee', '80');

        $after = $this->get(route('rider.profit'))->viewData('totalProfit');
        $this->assertEquals($before, $after, 'Past earnings changed when the admin changed the delivery fee.');
    }

    public function test_R09_notification_for_a_reassigned_delivery(): void
    {
        $orderId = $this->assigned();
        $note = createNotification($this->rider->id, 'New Delivery Assignment', 'x', 'delivery', $orderId);

        $this->asRider()->get(route('notifications.open', $note->id))->assertRedirect(route('rider.delivery.details', $orderId));

        $this->setOrder($orderId, ['delivery_rider_id' => $this->riderFor($this->laguna)->id]);
        $this->get(route('notifications.open', $note->id))
            ->assertRedirect(route('rider.deliveries'))
            ->assertSessionHas('error', 'Order #' . $orderId . ' is no longer assigned to you.');
    }

    public function test_R10b_rider_updates_phone_and_address(): void
    {
        $other = $this->makeUser('buyer');

        $this->asRider()->get(route('rider.profile'))->assertOk()->assertSee('Phone &amp; Address', false);
        $this->post(route('rider.profile.update'), ['phone' => $other->phone, 'address' => self::QC_ADDRESS])->assertSessionHas('error', 'This phone number is already registered.');
        $this->post(route('rider.profile.update'), ['phone' => '09175550000', 'address' => 'Nowhere'])->assertSessionHas('error');
        $this->post(route('rider.profile.update'), ['phone' => '09175550000', 'address' => self::QC_ADDRESS])->assertSessionHas('success');

        $fresh = $this->rider->fresh();
        $this->assertSame('09175550000', $fresh->phone);
        $this->assertSame('Metro Manila (NCR)', $fresh->province);
        $this->assertSame('09175550000', DB::table('rider_applications')->where('user_id', $this->rider->id)->value('phone'));
    }

    public function test_R10_profile_photo_and_password_change(): void
    {
        $this->asRider()->post(route('rider.profile.photo'), ['profile_photo' => $this->png('me.png')])
            ->assertRedirect(route('rider.profile'))
            ->assertSessionHas('success');

        $this->assertTrue(Route::has('rider.profile.password'), 'Riders have no way to change their password.');

        $this->get(route('rider.profile'))->assertOk()->assertSee('Change Password');
        $this->post(route('rider.profile.password'), ['current_password' => 'wrong', 'new_password' => 'newpass123', 'new_password_confirmation' => 'newpass123'])
            ->assertSessionHas('error', 'Current password is incorrect.');
        $this->post(route('rider.profile.password'), ['current_password' => 'password123', 'new_password' => 'newpass123', 'new_password_confirmation' => 'newpass999'])
            ->assertSessionHas('error', 'New passwords do not match.');
        $this->post(route('rider.profile.password'), ['current_password' => 'password123', 'new_password' => 'newpass123', 'new_password_confirmation' => 'newpass123'])
            ->assertSessionHas('success');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpass123', $this->rider->fresh()->password));
    }
}
