<?php

namespace Tests\Feature\Scenarios;

use App\Models\Product;
use App\Models\RiderArea;
use App\Models\SortingCenter;
use App\Models\User;
use App\Support\Waybill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Test Plan → "Logistics" (L01–L14), plus the logistics bugs from the review. */
class LogisticsScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private SortingCenter $laguna;
    private SortingCenter $ncr;
    private User $lagunaStaff;
    private User $ncrStaff;
    private User $lagunaRider;
    private User $seller;
    private Product $shoes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('local');
        Mail::fake();

        $this->laguna = $this->center('Laguna', 'Santa Cruz');
        $this->ncr = $this->center('Metro Manila (NCR)', 'Quezon City');
        $this->lagunaStaff = $this->staffAt($this->laguna);
        $this->ncrStaff = $this->staffAt($this->ncr);
        $this->lagunaRider = $this->riderFor($this->laguna);
        $this->seller = $this->sellerIn('Laguna', 'Santa Cruz');
        $this->shoes = $this->makeProduct($this->seller, ['price' => 500]);
    }

    /** A Laguna seller's parcel for a buyer at $address, at $status. */
    private function parcel(string $status, string $address = self::LAGUNA_ADDRESS, array $over = []): int
    {
        $town = \App\Support\PhLocations::locate($address);
        $destination = \App\Support\ParcelRoute::centerFor($town['province'], $town['city']);

        return $this->makeOrder($this->buyerAt($address), $this->shoes, $status, array_merge([
            'shipping_address' => $address,
            'shipping_province' => $town['province'],
            'shipping_city' => $town['city'],
            'origin_center_id' => $this->laguna->id,
            'destination_center_id' => $destination?->id,
        ], $over));
    }

    private function as(User $user): static
    {
        $this->flushSession();

        return $this->actingAsUser($user);
    }

    public function test_L01_admin_approves_a_logistics_account_and_assigns_its_center(): void
    {
        $applicant = $this->makeUser('logistics');
        $appId = DB::table('logistics_applications')->insertGetId(['user_id' => $applicant->id, 'full_name' => 'L', 'business_name' => 'Hub', 'status' => 'Pending Verification', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAsAdmin()->post(route('admin.applications.approve', ['logistics', $appId]))->assertSessionHas('success');
        $this->post(route('admin.sorting-centers.staff', $applicant->id), ['sorting_center_id' => $this->laguna->id])->assertSessionHas('success');

        $this->assertSame($this->laguna->id, (int) $applicant->fresh()->sorting_center_id);
        $this->assertTrue($this->notifiedWith($applicant, 'Sorting Center Assignment'));

        $this->flushSession();
        $this->post(route('login.submit'), ['email' => $applicant->email, 'password' => 'password123'])->assertRedirect(route('logistics.dashboard'));
    }

    public function test_L01b_an_account_not_yet_assigned_to_a_center_sees_no_parcels(): void
    {
        $this->parcel('Dropped Off');
        $unassigned = $this->staffAt(null);
        // Head office is a deliberate choice by the admin, not the default for every new account.
        DB::table('users')->where('id', $unassigned->id)->update(['sorting_center_id' => null, 'is_head_office' => false]);

        $this->as($unassigned)->get(route('logistics.parcels'))->assertOk()
            ->assertViewHas('awaitingConfirmation', fn ($list) => $list->isEmpty());
    }

    public function test_L02_staff_see_their_center_and_head_office_sees_all(): void
    {
        $this->parcel('Dropped Off');
        $ncrSeller = $this->sellerIn('Metro Manila (NCR)', 'Quezon City');
        $this->makeOrder($this->buyerAt(self::QC_ADDRESS), $this->makeProduct($ncrSeller), 'Dropped Off', ['origin_center_id' => $this->ncr->id, 'destination_center_id' => $this->ncr->id]);

        $this->as($this->lagunaStaff)->get(route('logistics.dashboard'))->assertOk()
            ->assertViewHas('pipeline', fn ($p) => $p['Dropped Off']['count'] === 1);

        $headOffice = $this->makeLogistics(); // no center
        $this->as($headOffice)->get(route('logistics.dashboard'))
            ->assertViewHas('pipeline', fn ($p) => $p['Dropped Off']['count'] === 2);
    }

    public function test_L03_scanning_the_label_opens_the_parcel(): void
    {
        $orderId = $this->parcel('Dropped Off');
        $waybill = Waybill::number($orderId);

        foreach ([$waybill, strtolower(str_replace('-', '', $waybill)), (string) $orderId] as $code) {
            $this->as($this->lagunaStaff)->get(route('logistics.scan', $code))
                ->assertRedirect(route('logistics.parcels', ['q' => $waybill]));
        }

        $this->get(route('logistics.scan', 'BB-999999'))->assertRedirect(route('logistics.parcels'))->assertSessionHas('error');
        $this->get(route('logistics.parcels', ['q' => $waybill]))->assertViewHas('awaitingConfirmation', fn ($l) => $l->pluck('id')->all() === [$orderId]);
    }

    public function test_L04_drop_off_is_confirmed_only_at_its_own_center(): void
    {
        $orderId = $this->parcel('Dropped Off');

        $this->as($this->ncrStaff)->post(route('logistics.parcels.confirm-received', $orderId))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'dropped off at another Sorting Center'));
        $this->as($this->lagunaStaff)->post(route('logistics.parcels.confirm-received', $orderId))->assertSessionHas('success');
        $this->assertSame('At Sorting Center', $this->orderStatus($orderId));
    }

    public function test_L05_parcel_for_another_province_is_dispatched_not_given_to_a_rider(): void
    {
        $one = $this->parcel('Dropped Off', self::QC_ADDRESS);
        $two = $this->parcel('Dropped Off', self::QC_ADDRESS);
        $this->as($this->lagunaStaff)->post(route('logistics.parcels.confirm-received', $one));
        $this->post(route('logistics.parcels.confirm-received', $two));

        $this->post(route('logistics.parcels.assign', $this->sorted($one)), ['rider_id' => $this->lagunaRider->id])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Dispatch this parcel'));

        $this->post(route('logistics.parcels.dispatch-all'), ['destination_center_id' => $this->ncr->id])
            ->assertSessionHas('success', fn ($m) => str_starts_with($m, '2 parcel(s) dispatched'));
        $this->assertSame('In Transit', $this->orderStatus($one));
        $this->assertSame('In Transit', $this->orderStatus($two));
    }

    public function test_L06_destination_confirms_and_suggests_riders_from_the_buyers_town(): void
    {
        $ncrRider = $this->riderFor($this->ncr);
        $orderId = $this->parcel('In Transit', self::QC_ADDRESS);

        $this->as($this->ncrStaff)->post(route('logistics.parcels.confirm-arrival', $orderId))->assertSessionHas('success');
        $this->get(route('logistics.parcels'))
            ->assertViewHas('suggestedRidersByOrder', fn ($s) => $s[$orderId]->pluck('id')->first() === $ncrRider->id);

        $this->post(route('logistics.parcels.assign', $this->sorted($orderId)), ['rider_id' => $ncrRider->id])->assertSessionHas('success');
        $this->assertSame('Assigned for Delivery', $this->orderStatus($orderId));

        $pickup = $this->parcel('In Transit', self::QC_ADDRESS, ['fulfillment' => 'pickup']);
        $this->post(route('logistics.parcels.confirm-arrival', $pickup));
        $this->assertSame('Ready to Collect', $this->orderStatus($pickup));
    }

    public function test_L07_pick_up_handed_over_with_cash_paid_at_the_counter(): void
    {
        $orderId = $this->parcel('Ready to Collect', self::LAGUNA_ADDRESS, ['fulfillment' => 'pickup', 'current_center_id' => $this->laguna->id, 'pickup_code' => '482915']);

        $this->as($this->lagunaStaff)->post(route('logistics.parcels.hand-to-buyer', $orderId), ['pickup_code' => '482915'])->assertSessionHas('success');

        $order = DB::table('orders')->find($orderId);
        $this->assertSame('Completed', $order->status);
        $this->assertNotNull($order->cod_remitted_at);
        $this->assertNotNull($order->buyer_received_at);
        $this->assertTrue($this->notifiedWith($this->seller, 'Order Delivered'));
    }

    public function test_L07b_pick_up_needs_the_buyers_pickup_code(): void
    {
        $orderId = $this->parcel('Ready to Collect', self::LAGUNA_ADDRESS, ['fulfillment' => 'pickup', 'current_center_id' => $this->laguna->id, 'pickup_code' => '482915']);

        // Someone who only knows the order number.
        $this->as($this->lagunaStaff)->post(route('logistics.parcels.hand-to-buyer', $orderId))->assertSessionHas('error');
        $this->post(route('logistics.parcels.hand-to-buyer', $orderId), ['pickup_code' => '111111'])->assertSessionHas('error');
        $this->assertSame('Ready to Collect', $this->orderStatus($orderId));

        $this->post(route('logistics.parcels.hand-to-buyer', $orderId), ['pickup_code' => '482915'])->assertSessionHas('success');
        $this->assertSame('Completed', $this->orderStatus($orderId));
    }

    public function test_L08_two_attempts_at_most_and_refused_parcels_go_back(): void
    {
        $failed = $this->parcel('Delivery Failed', self::LAGUNA_ADDRESS, ['back_at_center_at' => now(), 'current_center_id' => $this->laguna->id, 'delivery_attempts' => 1]);
        $this->as($this->lagunaStaff)->post(route('logistics.parcels.reschedule', $failed), ['rider_id' => $this->lagunaRider->id])->assertSessionHas('success');

        $twice = $this->parcel('Delivery Failed', self::LAGUNA_ADDRESS, ['back_at_center_at' => now(), 'current_center_id' => $this->laguna->id, 'delivery_attempts' => 2]);
        $this->post(route('logistics.parcels.reschedule', $twice), ['rider_id' => $this->lagunaRider->id])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'maximum delivery attempts'));

        $refused = $this->parcel('Delivery Failed', self::LAGUNA_ADDRESS, ['back_at_center_at' => now(), 'current_center_id' => $this->laguna->id, 'delivery_attempts' => 1, 'buyer_refused_at' => now()]);
        $this->post(route('logistics.parcels.reschedule', $refused), ['rider_id' => $this->lagunaRider->id])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'refused'));
    }

    public function test_L09_return_travels_back_to_the_sellers_center_and_is_handed_back(): void
    {
        $orderId = $this->parcel('Delivery Failed', self::QC_ADDRESS, ['back_at_center_at' => now(), 'current_center_id' => $this->ncr->id, 'delivery_attempts' => 2]);

        $this->as($this->ncrStaff)->post(route('logistics.parcels.return-to-seller', $orderId))->assertSessionHas('success');
        $this->assertSame('Returning', $this->orderStatus($orderId));

        $this->as($this->lagunaStaff)->post(route('logistics.parcels.confirm-return', $orderId))->assertSessionHas('success');
        $this->assertSame('Return Ready', $this->orderStatus($orderId));
        $this->assertTrue($this->notifiedWith($this->seller, 'Collect Your Returned Parcel'));

        $this->post(route('logistics.parcels.hand-back', $orderId))->assertSessionHas('success');
        $this->assertSame('Returned to Seller', $this->orderStatus($orderId));
    }

    public function test_L10_cash_from_riders_releases_the_sellers_payout(): void
    {
        $orderId = $this->parcel('Delivered', self::LAGUNA_ADDRESS, ['delivery_rider_id' => $this->lagunaRider->id, 'destination_center_id' => $this->laguna->id, 'cod_collected_at' => now(), 'total_amount' => 550]);

        $this->as($this->lagunaStaff)->get(route('logistics.parcels'))
            ->assertViewHas('codToReceive', fn ($c) => $c->count() === 1 && (float) $c->first()->amount === 550.0);

        $this->post(route('logistics.riders.receive-cod', $this->lagunaRider->id))->assertSessionHas('success');
        $this->assertNotNull(DB::table('orders')->where('id', $orderId)->value('cod_remitted_at'));
        $this->assertTrue($this->notifiedWith($this->seller, 'COD Cash Received'));
        $this->assertTrue($this->notifiedWith($this->lagunaRider, 'Cash Handed In'));
    }

    public function test_L11_rider_documents_approve_reject_once(): void
    {
        $applicant = $this->makeRider('Pending Verification');
        DB::table('users')->where('id', $applicant->id)->update(['province' => 'Laguna']);
        Storage::disk('local')->put('rider-applications/x/id.png', 'img');
        DB::table('rider_applications')->where('user_id', $applicant->id)->update(['national_id' => 'rider-applications/x/id.png']);
        $appId = DB::table('rider_applications')->where('user_id', $applicant->id)->value('id');

        $this->as($this->lagunaStaff)->get(route('logistics.riders.document', [$appId, 'national_id']))->assertOk();
        $this->get(route('logistics.riders.document', [$appId, 'password']))->assertNotFound();

        $this->post(route('logistics.riders.reject', $appId), ['admin_remarks' => 'Blurry license'])->assertSessionHas('success');
        $this->post(route('logistics.riders.approve', $appId))->assertSessionHas('error', 'This application was already rejected.');
        $this->assertTrue($this->notifiedWith($applicant, 'Rider Application Rejected'));
        Mail::assertSent(\App\Mail\ApplicationStatusMail::class);
    }

    public function test_L11b_staff_cannot_decide_riders_outside_their_area(): void
    {
        // A rider who lives in Laguna; NCR staff should not be the ones approving them.
        $applicant = $this->makeRider('Pending Verification');
        DB::table('users')->where('id', $applicant->id)->update(['province' => 'Laguna', 'city_municipality' => 'Santa Cruz']);
        $appId = DB::table('rider_applications')->where('user_id', $applicant->id)->value('id');

        $this->as($this->ncrStaff)->post(route('logistics.riders.approve', $appId))->assertSessionHas('error');
        $this->as($this->ncrStaff)->post(route('logistics.riders.toggle-status', $this->lagunaRider->id), ['status' => 'Deactivated'])->assertSessionHas('error');
        $this->assertSame('Active', $this->lagunaRider->fresh()->status ?? 'Active');
    }

    public function test_L12_rider_areas_and_status(): void
    {
        $rider = $this->makeRider();
        DB::table('users')->where('id', $rider->id)->update(['province' => 'Laguna', 'city_municipality' => 'Pagsanjan']);

        $this->as($this->lagunaStaff)->post(route('logistics.riders.areas.store', $rider->id), ['province' => 'Laguna', 'city_municipality' => 'Pagsanjan'])
            ->assertSessionHas('success');
        $area = RiderArea::where('rider_id', $rider->id)->first();
        $this->assertSame('Pagsanjan', $area->city_municipality);

        $this->post(route('logistics.riders.areas.delete', $area->id));
        $this->assertNull(RiderArea::find($area->id));

        $this->post(route('logistics.riders.toggle-status', $rider->id), ['status' => 'Deactivated']);
        $this->assertSame('Deactivated', $rider->fresh()->status);
        $this->post(route('logistics.riders.toggle-status', $rider->id), ['status' => 'Active']);
        $this->assertSame('Active', $rider->fresh()->status);
    }

    public function test_L13_second_click_on_the_same_parcel_is_refused(): void
    {
        $orderId = $this->parcel('Dropped Off');
        $colleague = $this->staffAt($this->laguna);

        $this->as($this->lagunaStaff)->post(route('logistics.parcels.confirm-received', $orderId))->assertSessionHas('success');
        $this->as($colleague)->post(route('logistics.parcels.confirm-received', $orderId))->assertSessionHas('error');
        $this->assertSame(1, DB::table('order_events')->where('order_id', $orderId)->where('status', 'At Sorting Center')->count());
    }

    public function test_L14_profile_and_password(): void
    {
        $this->as($this->lagunaStaff)->post(route('logistics.profile.update'), ['name' => 'Hub Staff', 'phone' => '09179998888', 'address' => self::LAGUNA_ADDRESS])
            ->assertSessionHas('success');
        $this->post(route('logistics.profile.password'), ['current_password' => 'wrong', 'new_password' => 'newpass123', 'new_password_confirmation' => 'newpass123'])
            ->assertSessionHas('error', 'Current password is incorrect.');
        $this->post(route('logistics.profile.password'), ['current_password' => 'password123', 'new_password' => 'newpass123', 'new_password_confirmation' => 'newpass123'])
            ->assertSessionHas('success');
    }
}
