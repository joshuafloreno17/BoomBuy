<?php

namespace Tests\Feature\Scenarios;

use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\SortingCenter;
use App\Models\User;
use App\Support\OrphanUploads;
use App\Support\StaleOrders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Orders that would wait forever, and files of registrations never finished. */
class HousekeepingScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private SortingCenter $laguna;
    private User $seller;
    private Product $shoes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('local');

        $this->laguna = $this->center('Laguna', 'Santa Cruz');
        $this->seller = $this->sellerIn('Laguna', 'Santa Cruz');
        $this->shoes = $this->makeProduct($this->seller, ['stock' => 9]);

        // The timers have been running for a while.
        PlatformSetting::set(StaleOrders::SINCE_KEY, now()->subMonth()->toDateTimeString());
    }

    public function test_H01_seller_is_reminded_then_an_unconfirmed_order_is_cancelled(): void
    {
        $buyer = $this->buyerAt();
        $twoDays = $this->makeOrder($buyer, $this->shoes, 'Pending', ['created_at' => now()->subDays(2)->subHour()]);
        $fourDays = $this->makeOrder($buyer, $this->shoes, 'Pending', ['created_at' => now()->subDays(4)]);
        $confirmed = $this->makeOrder($buyer, $this->shoes, 'Preparing', ['created_at' => now()->subDays(5)]);

        $done = StaleOrders::sweep(true);

        $this->assertSame(1, $done['cancelled']);
        $this->assertSame('Pending', $this->orderStatus($twoDays));
        $this->assertSame('Cancelled', $this->orderStatus($fourDays));
        $this->assertSame('Preparing', $this->orderStatus($confirmed));
        $this->assertSame(10, $this->stockOf($this->shoes));
        $this->assertTrue($this->notifiedWith($buyer, 'Order Cancelled'));
        $this->assertTrue($this->notifiedWith($this->seller, 'Confirm Order Soon'));
        $this->assertTrue($this->notifiedWith($this->seller, 'Order Cancelled Automatically'));

        // Not the buyer's fault: no strike against their Cash on Delivery.
        $this->assertSame(0, \App\Support\CodPolicy::status($buyer->id)['strikes']);

        // One reminder only.
        StaleOrders::sweep(true);
        $this->assertSame(1, DB::table('notifications')->where('user_id', $this->seller->id)->where('title', 'Confirm Order Soon')->count());
    }

    public function test_H02_buyer_is_reminded_then_an_uncollected_pick_up_goes_back(): void
    {
        $buyer = $this->buyerAt();
        $base = ['fulfillment' => 'pickup', 'origin_center_id' => $this->laguna->id, 'destination_center_id' => $this->laguna->id, 'current_center_id' => $this->laguna->id];
        $sixDays = $this->makeOrder($buyer, $this->shoes, 'Ready to Collect', $base + ['updated_at' => now()->subDays(6)]);
        $eightDays = $this->makeOrder($buyer, $this->shoes, 'Ready to Collect', $base + ['updated_at' => now()->subDays(8)]);

        $done = StaleOrders::sweep(true);

        $this->assertSame(1, $done['returned']);
        $this->assertSame('Ready to Collect', $this->orderStatus($sixDays));
        $this->assertSame('Return Ready', $this->orderStatus($eightDays));
        $this->assertTrue($this->notifiedWith($buyer, 'Collect Your Order Soon'));
        $this->assertTrue($this->notifiedWith($this->seller, 'Collect Your Returned Parcel'));
    }

    public function test_H03_orders_already_waiting_when_the_timers_start_get_the_full_grace_period(): void
    {
        PlatformSetting::set(StaleOrders::SINCE_KEY, '');
        $old = $this->makeOrder($this->buyerAt(), $this->shoes, 'Pending', ['created_at' => now()->subWeeks(3)]);

        StaleOrders::sweep(true); // first run: starts counting now
        $this->assertSame('Pending', $this->orderStatus($old));

        $this->travelTo(now()->addDays(3)->addHour());
        StaleOrders::sweep(true);
        $this->assertSame('Cancelled', $this->orderStatus($old));
    }

    public function test_H05_seller_is_reminded_to_collect_a_return_then_boombuy_is_told(): void
    {
        $staff = $this->staffAt($this->laguna);
        $admin = \App\Models\User::create(['name' => 'Admin', 'email' => 'admin@boombuy.com', 'password' => bcrypt('x'), 'role' => 'admin']);
        $base = ['origin_center_id' => $this->laguna->id, 'current_center_id' => $this->laguna->id];
        $fourDays = $this->makeOrder($this->buyerAt(), $this->shoes, 'Return Ready', $base + ['updated_at' => now()->subDays(4)]);
        $eightDays = $this->makeOrder($this->buyerAt(), $this->shoes, 'Return Ready', $base + ['updated_at' => now()->subDays(8)]);

        StaleOrders::sweep(true);
        StaleOrders::sweep(true); // reminders are sent once

        $this->assertSame(2, DB::table('notifications')->where('user_id', $this->seller->id)->where('title', 'Collect Your Return Soon')->count());
        $this->assertSame(1, DB::table('notifications')->where('user_id', $admin->id)->where('title', 'Return Not Collected')->count());
        $this->assertSame($eightDays, (int) DB::table('notifications')->where('user_id', $admin->id)->where('title', 'Return Not Collected')->value('reference_id'));
        $this->assertTrue($this->notifiedWith($staff, 'Return Not Collected'));
        $this->assertNotNull($fourDays);
    }

    public function test_H04_abandoned_registration_files_are_removed_after_a_day(): void
    {
        $disk = Storage::disk('local');
        $disk->put('seller-applications/abandoned/id.png', 'x');
        $disk->put('seller-applications/in-use/id.png', 'x');
        $disk->put('buyer-ids/just-now/id.png', 'x');

        $user = $this->makeUser('seller');
        DB::table('seller_applications')->insert(['user_id' => $user->id, 'full_name' => 'A', 'phone' => '0917', 'address' => 'x', 'national_id' => 'seller-applications/in-use/id.png', 'status' => 'Pending Verification', 'created_at' => now(), 'updated_at' => now()]);

        // The abandoned and in-use files are two days old; just-now was saved a minute ago.
        touch($disk->path('seller-applications/abandoned/id.png'), now()->subDays(2)->getTimestamp());
        touch($disk->path('seller-applications/in-use/id.png'), now()->subDays(2)->getTimestamp());
        touch($disk->path('buyer-ids/just-now/id.png'), now()->subMinute()->getTimestamp());

        $this->assertSame(1, OrphanUploads::sweep(true));
        $disk->assertMissing('seller-applications/abandoned/id.png');
        $disk->assertExists('seller-applications/in-use/id.png');
        $disk->assertExists('buyer-ids/just-now/id.png');
    }
}
