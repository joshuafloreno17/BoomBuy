<?php

namespace Tests\Feature\Scenarios;

use App\Models\Product;
use App\Models\SortingCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Delivery branches, like a courier's local branch: Cebu center → Laguna
 * center (Calamba) sorts it by town → truck to the Santa Cruz branch → the
 * branch's rider delivers it in Santa Cruz.
 */
class BranchScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private SortingCenter $cebu;
    private SortingCenter $laguna;
    private SortingCenter $santaCruz;
    private User $cebuStaff;
    private User $lagunaStaff;
    private User $branchStaff;
    private User $branchRider;
    private User $buyer;
    private Product $phone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cebu = $this->center('Cebu', 'Cebu City');
        $this->laguna = $this->center('Laguna', 'Calamba City');
        $this->santaCruz = $this->branchOf($this->laguna, 'Santa Cruz');

        $this->cebuStaff = $this->staffAt($this->cebu);
        $this->lagunaStaff = $this->staffAt($this->laguna);
        $this->branchStaff = $this->staffAt($this->santaCruz);
        $this->branchRider = $this->riderFor($this->laguna, 'Santa Cruz');

        $seller = $this->sellerIn('Cebu', 'Cebu City', 'electronics');
        $this->buyer = $this->buyerAt();
        $this->phone = $this->makeProduct($seller, ['price' => 1500]);
    }

    private function branchOf(SortingCenter $center, string $city): SortingCenter
    {
        return SortingCenter::create([
            'parent_id' => $center->id,
            'name' => SortingCenter::branchNameFor($city),
            'region' => $center->region,
            'province' => $center->province,
            'city_municipality' => $city,
            'address' => 'Poblacion, ' . $city,
            'is_active' => true,
        ]);
    }

    /** A parcel for Santa Cruz that just arrived at the Laguna center from Cebu. */
    private function atLagunaCenter(): int
    {
        return $this->makeOrder($this->buyer, $this->phone, 'At Sorting Center', [
            'shipping_address' => self::LAGUNA_ADDRESS,
            'shipping_province' => 'Laguna',
            'shipping_city' => 'Santa Cruz',
            'origin_center_id' => $this->cebu->id,
            'destination_center_id' => $this->laguna->id,
            'current_center_id' => $this->laguna->id,
            'sorting_center_received_at' => now(),
        ]);
    }

    private function orderStatusOf(int $id): string
    {
        return DB::table('orders')->where('id', $id)->value('status');
    }

    public function test_BR01_center_trucks_it_to_the_branch_and_the_branch_rider_delivers(): void
    {
        $id = $this->atLagunaCenter();

        // Sorting finds the branch in the buyer's town.
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.sort', $id))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'BoomBuy Branch – Santa Cruz'));
        $this->assertSame('Sorted', $this->orderStatusOf($id));
        $this->assertSame($this->santaCruz->id, (int) DB::table('orders')->where('id', $id)->value('branch_id'));

        // The center can't hand it to a rider — it goes to the branch.
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.assign', $id), ['rider_id' => $this->branchRider->id])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Dispatch this parcel to BoomBuy Branch – Santa Cruz first'));
        $this->actingAsUser($this->lagunaStaff)->get(route('logistics.parcels'))->assertSee('To Branches (1)')->assertSee('Send to BoomBuy Branch – Santa Cruz');

        // Truck to the branch.
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.to-branch', $id))->assertSessionHas('success');
        $this->assertSame('To Branch', $this->orderStatusOf($id));
        $this->assertTrue($this->notifiedWith($this->branchStaff, 'Incoming Parcel'));
        $this->assertTrue($this->notifiedWith($this->buyer, 'Parcel Near You'));

        // Only the branch confirms it arrived.
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.at-branch', $id))->assertSessionHas('error');
        $this->actingAsUser($this->branchStaff)->get(route('logistics.parcels'))->assertSee('From the Sorting Center (1)');
        $this->actingAsUser($this->branchStaff)->post(route('logistics.parcels.at-branch', $id))->assertSessionHas('success');
        $this->assertSame('At Branch', $this->orderStatusOf($id));
        $this->assertSame($this->santaCruz->id, (int) DB::table('orders')->where('id', $id)->value('current_center_id'));

        // The branch assigns its rider; the center no longer can.
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.assign', $id), ['rider_id' => $this->branchRider->id])->assertSessionHas('error');
        $this->actingAsUser($this->branchStaff)->get(route('logistics.parcels'))->assertSee('Awaiting Assignment (1)')->assertSee($this->branchRider->name . ' (area match)', false);
        $this->actingAsUser($this->branchStaff)->post(route('logistics.parcels.assign', $id), ['rider_id' => $this->branchRider->id])->assertSessionHas('success');
        $this->assertSame('Assigned for Delivery', $this->orderStatusOf($id));
        $this->assertTrue(DB::table('notifications')->where('user_id', $this->branchRider->id)->where('message', 'like', '%pick it up from BoomBuy Branch – Santa Cruz%')->exists());

        // The rider delivers; the branch (not the center) sees it on the road and takes the COD cash.
        $this->actingAsUser($this->branchStaff)->get(route('logistics.parcels'))->assertSee('On the Road (1)');
        $this->actingAsUser($this->lagunaStaff)->get(route('logistics.parcels'))->assertSee('On the Road (0)');
        $this->actingAsUser($this->branchRider)->post(route('rider.delivery.status', $id), ['status' => 'Out for Delivery'])->assertSessionHas('success');
        $this->actingAsUser($this->branchRider)->post(route('rider.delivery.status', $id), ['status' => 'Delivered', 'delivery_proof' => $this->deliveryPhoto()])->assertSessionHas('success');
        $this->assertSame('Delivered', $this->orderStatusOf($id));
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.riders.receive-cod', $this->branchRider->id))->assertSessionHas('error');
        $this->actingAsUser($this->branchStaff)->post(route('logistics.riders.receive-cod', $this->branchRider->id))->assertSessionHas('success');
        $this->assertSame($this->santaCruz->id, (int) DB::table('orders')->where('id', $id)->value('cod_remitted_center_id'));

        // The buyer's tracking shows the branch steps.
        $steps = DB::table('order_events')->where('order_id', $id)->pluck('status')->all();
        $this->assertContains('To Branch', $steps);
        $this->assertContains('At Branch', $steps);
    }

    public function test_BR02_a_town_without_a_branch_is_delivered_from_the_center(): void
    {
        $id = $this->makeOrder($this->buyer, $this->phone, 'At Sorting Center', [
            'shipping_address' => '3 Rizal St, Pagsanjan, Laguna',
            'shipping_province' => 'Laguna',
            'shipping_city' => 'Pagsanjan',
            'origin_center_id' => $this->cebu->id,
            'destination_center_id' => $this->laguna->id,
            'current_center_id' => $this->laguna->id,
        ]);
        $rider = $this->riderFor($this->laguna, 'Pagsanjan');

        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.sort', $id))->assertSessionHas('success', fn ($m) => str_contains($m, 'assign the rider'));
        $this->assertNull(DB::table('orders')->where('id', $id)->value('branch_id'));
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.assign', $id), ['rider_id' => $rider->id])->assertSessionHas('success');
        $this->assertSame('Assigned for Delivery', $this->orderStatusOf($id));
    }

    public function test_BR03_a_branch_without_staff_or_closed_gets_no_parcels(): void
    {
        DB::table('users')->where('id', $this->branchStaff->id)->update(['sorting_center_id' => null]);
        $id = $this->atLagunaCenter();
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.sort', $id))->assertSessionHas('success');
        $this->assertNull(DB::table('orders')->where('id', $id)->value('branch_id'));

        DB::table('users')->where('id', $this->branchStaff->id)->update(['sorting_center_id' => $this->santaCruz->id]);
        $this->santaCruz->update(['is_active' => false]);
        $other = $this->atLagunaCenter();
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.sort', $other))->assertSessionHas('success');
        $this->assertNull(DB::table('orders')->where('id', $other)->value('branch_id'));
    }

    public function test_BR11_a_branch_in_the_centers_own_town_is_skipped(): void
    {
        // Like Branch – Cebu City next to the Cebu center in Cebu City.
        $cityBranch = $this->branchOf($this->laguna, 'Calamba City');
        $this->staffAt($cityBranch);

        $id = $this->makeOrder($this->buyer, $this->phone, 'At Sorting Center', [
            'shipping_address' => '5 Rizal St, Calamba City, Laguna',
            'shipping_province' => 'Laguna',
            'shipping_city' => 'Calamba City',
            'origin_center_id' => $this->cebu->id,
            'destination_center_id' => $this->laguna->id,
            'current_center_id' => $this->laguna->id,
        ]);
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.sort', $id))->assertSessionHas('success');
        $this->assertNull(DB::table('orders')->where('id', $id)->value('branch_id'));

        // Pickup side: a seller in Calamba City brings it straight to the center.
        $this->assertNull(\App\Support\ParcelRoute::branchFor($this->laguna->id, 'Laguna', 'Calamba City'));
        $this->assertSame($this->santaCruz->id, \App\Support\ParcelRoute::branchFor($this->laguna->id, 'Laguna', 'Santa Cruz')?->id);
    }

    public function test_BR04_failed_delivery_goes_back_to_the_branch_and_a_released_rider_returns_it_there(): void
    {
        $id = $this->atLagunaCenter();
        $centers = app(\App\Services\SortingCenterService::class);
        $centers->sort($id);
        $centers->dispatchToBranch($id);
        $centers->confirmAtBranch($id);
        $centers->assign($id, $this->branchRider->id);

        // A rider who stops working: the parcel waits at the branch again.
        \App\Support\RiderRelease::release($this->branchRider->id);
        $this->assertSame('At Branch', $this->orderStatusOf($id));

        $centers->assign($id, $this->branchRider->id);
        $this->actingAsUser($this->branchRider)->post(route('rider.delivery.status', $id), ['status' => 'Out for Delivery'])->assertSessionHas('success');
        $this->actingAsUser($this->branchRider)->post(route('rider.delivery.status', $id), ['status' => 'Delivery Failed', 'failure_code' => 'Other', 'failure_reason' => 'Buyer not home'])->assertSessionHas('success');

        // The branch handles the failed delivery, not the center.
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.confirm-back', $id))->assertSessionHas('error');
        $this->actingAsUser($this->branchStaff)->post(route('logistics.parcels.confirm-back', $id))->assertSessionHas('success');
        $this->actingAsUser($this->branchStaff)->post(route('logistics.parcels.reschedule', $id), ['rider_id' => $this->branchRider->id])->assertSessionHas('success');
        $this->assertSame('Assigned for Delivery', $this->orderStatusOf($id));
    }

    public function test_BR06_full_flow_seller_branch_to_buyer_branch(): void
    {
        // The seller is in Mandaue City, which has its own branch.
        $cebuCity = $this->branchOf($this->cebu, 'Mandaue City');
        $cebuBranchStaff = $this->staffAt($cebuCity);
        $pickupRider = $this->riderFor($this->cebu, 'Mandaue City');
        $seller = User::find($this->phone->seller_id);
        $seller->forceFill(['city_municipality' => 'Mandaue City', 'address' => 'Poblacion, Mandaue City, Cebu'])->save();

        $id = $this->makeOrder($this->buyer, $this->phone, 'Preparing', [
            'shipping_address' => self::LAGUNA_ADDRESS,
            'shipping_province' => 'Laguna',
            'shipping_city' => 'Santa Cruz',
            'origin_center_id' => $this->cebu->id,
            'destination_center_id' => $this->laguna->id,
        ]);

        // Seller books the pickup: the parcel will go to the Mandaue City branch.
        $this->actingAsUser($seller)->post(route('seller.order.pickup', $id), ['pickup_date' => now()->toDateString()])->assertSessionHas('success');
        $this->assertSame($cebuCity->id, (int) DB::table('orders')->where('id', $id)->value('origin_branch_id'));

        // The Mandaue City rider accepts and picks it up, and is told to bring it to the branch.
        $this->actingAsUser($pickupRider)->post(route('rider.pickup.accept', $id))->assertSessionHas('success');
        $this->actingAsUser($pickupRider)->post(route('rider.pickup.picked-up', $id))->assertSessionHas('success');
        $this->assertTrue($this->notifiedWith($pickupRider, 'Bring Parcel to Branch'));
        $this->assertTrue($this->notifiedWith($cebuBranchStaff, 'Parcel Picked Up'));
        $this->assertFalse($this->notifiedWith($this->cebuStaff, 'Parcel Picked Up'));

        // The center can't take it from the rider — the branch does.
        $this->actingAsUser($this->cebuStaff)->post(route('logistics.parcels.confirm-received', $id))->assertSessionHas('error');
        $this->actingAsUser($cebuBranchStaff)->get(route('logistics.parcels'))->assertSee('Picked up by a rider');
        $this->actingAsUser($cebuBranchStaff)->post(route('logistics.parcels.confirm-received', $id))->assertSessionHas('success');
        $this->assertSame('Received at Branch', $this->orderStatusOf($id));

        // The branch trucks it to the Cebu center, which confirms it.
        $this->actingAsUser($cebuBranchStaff)->get(route('logistics.parcels'))->assertSee('To the Sorting Center (1)');
        $this->actingAsUser($cebuBranchStaff)->post(route('logistics.parcels.to-center-all'))->assertSessionHas('success');
        $this->assertSame('To Sorting Center', $this->orderStatusOf($id));
        $this->assertTrue($this->notifiedWith($this->cebuStaff, 'Incoming Parcel'));
        $this->actingAsUser($this->cebuStaff)->get(route('logistics.parcels'))->assertSee('Sent by BoomBuy Branch – Mandaue City');
        $this->actingAsUser($this->cebuStaff)->post(route('logistics.parcels.confirm-received', $id))->assertSessionHas('success');
        $this->assertSame('At Sorting Center', $this->orderStatusOf($id));
        $this->assertSame($this->cebu->id, (int) DB::table('orders')->where('id', $id)->value('current_center_id'));

        // Cebu → Laguna → Santa Cruz branch → rider → buyer.
        $this->actingAsUser($this->cebuStaff)->post(route('logistics.parcels.dispatch', $id))->assertSessionHas('success');
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.confirm-arrival', $id))->assertSessionHas('success');
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.sort', $id))->assertSessionHas('success');
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.parcels.to-branch', $id))->assertSessionHas('success');
        $this->actingAsUser($this->branchStaff)->post(route('logistics.parcels.at-branch', $id))->assertSessionHas('success');
        $this->actingAsUser($this->branchStaff)->post(route('logistics.parcels.assign', $id), ['rider_id' => $this->branchRider->id])->assertSessionHas('success');
        $this->actingAsUser($this->branchRider)->post(route('rider.delivery.status', $id), ['status' => 'Out for Delivery'])->assertSessionHas('success');
        $this->actingAsUser($this->branchRider)->post(route('rider.delivery.status', $id), ['status' => 'Delivered', 'delivery_proof' => $this->deliveryPhoto()])->assertSessionHas('success');
        $this->assertSame('Delivered', $this->orderStatusOf($id));

        $steps = DB::table('order_events')->where('order_id', $id)->orderBy('id')->pluck('status')->all();
        foreach (['Picked Up', 'Received at Branch', 'To Sorting Center', 'At Sorting Center', 'In Transit', 'Sorted', 'To Branch', 'At Branch', 'Assigned for Delivery', 'Delivered'] as $step) {
            $this->assertContains($step, $steps);
        }

        // The buyer's order page reads every step.
        $this->actingAsUser($this->buyer)->get(route('buyer.orders'))->assertOk();
    }

    public function test_BR07_sorting_center_staff_open_branches_and_add_staff(): void
    {
        $this->actingAsUser($this->lagunaStaff)->get(route('logistics.branches'))->assertOk()->assertSee('BoomBuy Branch – Santa Cruz');
        $this->actingAsUser($this->lagunaStaff)->get(route('logistics.dashboard'))->assertSee(route('logistics.branches'), false);

        // A branch's staff don't manage branches.
        $this->actingAsUser($this->branchStaff)->get(route('logistics.branches'))->assertRedirect(route('logistics.dashboard'));
        $this->actingAsUser($this->branchStaff)->get(route('logistics.dashboard'))->assertDontSee(route('logistics.branches'), false);

        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.branches.store'), ['city_municipality' => 'Lumban'])->assertSessionHas('success');
        $lumban = SortingCenter::where('parent_id', $this->laguna->id)->where('city_municipality', 'Lumban')->firstOrFail();
        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.branches.store'), ['city_municipality' => 'Cebu City'])->assertSessionHas('error');

        // Another center's staff can't touch it.
        $this->actingAsUser($this->cebuStaff)->post(route('logistics.branches.toggle', $lumban->id))->assertSessionHas('error');

        $this->actingAsUser($this->lagunaStaff)->post(route('logistics.branches.staff', $lumban->id), [
            'first_name' => 'Lina', 'last_name' => 'Cruz', 'email' => 'branch.lumban@boombuy.test', 'phone' => '09171112222', 'password' => 'password123',
        ])->assertSessionHas('success');

        $staff = User::where('email', 'branch.lumban@boombuy.test')->firstOrFail();
        $this->assertSame($lumban->id, (int) $staff->sorting_center_id);
        $this->post(route('logistics.login.submit'), ['email' => 'branch.lumban@boombuy.test', 'password' => 'password123'])->assertRedirect();
        $this->assertSame($staff->id, session('user.id'));

        // With staff, Lumban parcels now go to the branch.
        $this->assertSame($lumban->id, \App\Support\ParcelRoute::branchFor($this->laguna->id, 'Laguna', 'Lumban')?->id);
    }

    public function test_BR08_branch_staff_manage_their_towns_riders_only(): void
    {
        $calambaRider = $this->riderFor($this->laguna, 'Calamba City');

        $this->actingAsUser($this->branchStaff)->get(route('logistics.riders', ['status' => 'all']))
            ->assertSee($this->branchRider->email)
            ->assertDontSee($calambaRider->email);
        $this->actingAsUser($this->lagunaStaff)->get(route('logistics.riders', ['status' => 'all']))
            ->assertSee($this->branchRider->email)
            ->assertSee($calambaRider->email);

        $this->actingAsUser($this->branchStaff)->post(route('logistics.riders.areas.store', $this->branchRider->id), ['province' => 'Laguna', 'city_municipality' => 'Pagsanjan'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'covers Santa Cruz only'));
    }

    public function test_BR09_return_goes_back_through_the_sellers_center_to_the_sellers_branch(): void
    {
        $cebuCity = $this->branchOf($this->cebu, 'Cebu City');
        $cebuBranchStaff = $this->staffAt($cebuCity);
        $seller = User::find($this->phone->seller_id);

        // A parcel that failed twice at the Santa Cruz branch, picked up via the Cebu City branch.
        $id = $this->atLagunaCenter();
        DB::table('orders')->where('id', $id)->update(['origin_branch_id' => $cebuCity->id]);
        $centers = app(\App\Services\SortingCenterService::class);
        $centers->sort($id);
        $centers->dispatchToBranch($id);
        $centers->confirmAtBranch($id);
        $centers->assign($id, $this->branchRider->id);
        $this->actingAsUser($this->branchRider)->post(route('rider.delivery.status', $id), ['status' => 'Out for Delivery'])->assertSessionHas('success');
        $this->actingAsUser($this->branchRider)->post(route('rider.delivery.status', $id), ['status' => 'Delivery Failed', 'failure_code' => 'Other', 'failure_reason' => 'Buyer not home'])->assertSessionHas('success');
        $this->actingAsUser($this->branchStaff)->post(route('logistics.parcels.confirm-back', $id))->assertSessionHas('success');

        // Santa Cruz branch → Cebu center.
        $this->actingAsUser($this->branchStaff)->post(route('logistics.parcels.return-to-seller', $id))->assertSessionHas('success', fn ($m) => str_contains($m, 'BoomBuy Sorting Center – Cebu'));
        $this->assertSame('Returning', $this->orderStatusOf($id));
        $this->actingAsUser($cebuBranchStaff)->post(route('logistics.parcels.confirm-return', $id))->assertSessionHas('error');

        // Cebu center → Cebu City branch.
        $this->actingAsUser($this->cebuStaff)->get(route('logistics.parcels'))->assertSee('Confirm Return Arrived');
        $this->actingAsUser($this->cebuStaff)->post(route('logistics.parcels.confirm-return', $id))->assertSessionHas('success', fn ($m) => str_contains($m, 'BoomBuy Branch – Cebu City'));
        $this->assertSame('Returning to Branch', $this->orderStatusOf($id));
        $this->assertTrue($this->notifiedWith($cebuBranchStaff, 'Incoming Return'));
        $this->assertFalse($this->notifiedWith($seller, 'Collect Your Returned Parcel'));

        // The branch takes it in; the seller collects it there.
        $this->actingAsUser($this->cebuStaff)->post(route('logistics.parcels.confirm-return', $id))->assertSessionHas('error');
        $this->actingAsUser($cebuBranchStaff)->get(route('logistics.parcels'))->assertSee('Confirm Return Arrived');
        $this->actingAsUser($cebuBranchStaff)->post(route('logistics.parcels.confirm-return', $id))->assertSessionHas('success');
        $this->assertSame('Return Ready', $this->orderStatusOf($id));
        $this->assertSame($cebuCity->id, (int) DB::table('orders')->where('id', $id)->value('current_center_id'));
        $this->assertTrue(DB::table('notifications')->where('user_id', $seller->id)->where('title', 'Collect Your Returned Parcel')->where('message', 'like', '%BoomBuy Branch – Cebu City%')->exists());

        $this->actingAsUser($this->cebuStaff)->post(route('logistics.parcels.hand-back', $id))->assertSessionHas('error');
        $this->actingAsUser($cebuBranchStaff)->post(route('logistics.parcels.hand-back', $id))->assertSessionHas('success');
        $this->assertSame('Returned to Seller', $this->orderStatusOf($id));
    }

    public function test_BR10_return_waits_at_the_center_when_the_sellers_branch_has_no_staff(): void
    {
        $cebuCity = $this->branchOf($this->cebu, 'Cebu City');
        $id = $this->makeOrder($this->buyer, $this->phone, 'Returning', [
            'origin_center_id' => $this->cebu->id,
            'origin_branch_id' => $cebuCity->id,
            'destination_center_id' => $this->laguna->id,
        ]);

        $this->actingAsUser($this->cebuStaff)->post(route('logistics.parcels.confirm-return', $id))->assertSessionHas('success');
        $this->assertSame('Return Ready', $this->orderStatusOf($id));
        $this->assertSame($this->cebu->id, (int) DB::table('orders')->where('id', $id)->value('current_center_id'));
    }

    public function test_BR05_admin_opens_a_branch_and_assigns_its_staff(): void
    {
        $this->actingAsAdmin()->post(route('admin.sorting-centers.branches.store'), ['parent_id' => $this->laguna->id, 'city_municipality' => 'Pagsanjan'])
            ->assertSessionHas('success');
        $branch = SortingCenter::where('parent_id', $this->laguna->id)->where('city_municipality', 'Pagsanjan')->firstOrFail();
        $this->assertSame('BoomBuy Branch – Pagsanjan', $branch->name);

        // Same town twice, or a town of another province, is refused.
        $this->actingAsAdmin()->post(route('admin.sorting-centers.branches.store'), ['parent_id' => $this->laguna->id, 'city_municipality' => 'Pagsanjan'])->assertSessionHas('error');
        $this->actingAsAdmin()->post(route('admin.sorting-centers.branches.store'), ['parent_id' => $this->laguna->id, 'city_municipality' => 'Cebu City'])->assertSessionHas('error');

        $this->actingAsAdmin()->get(route('admin.logistics', ['tab' => 'branches']))->assertOk()->assertSee('Delivery Branches (2)')->assertSee('BoomBuy Branch – Pagsanjan');

        // Branches never count as a province's center.
        $this->assertSame($this->laguna->id, \App\Support\ParcelRoute::centerFor('Laguna', 'Santa Cruz')->id);
    }
}
