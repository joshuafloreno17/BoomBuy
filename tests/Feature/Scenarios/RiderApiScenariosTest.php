<?php

namespace Tests\Feature\Scenarios;

use App\Models\Product;
use App\Models\SortingCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The BoomBuy Rider app's API (/api/v1). */
class RiderApiScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private SortingCenter $laguna;
    private User $rider;
    private User $buyer;
    private Product $shoes;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->laguna = $this->center('Laguna', 'Santa Cruz');
        $this->rider = $this->riderFor($this->laguna);
        $this->buyer = $this->buyerAt();
        $this->shoes = $this->makeProduct($this->sellerIn('Laguna', 'Santa Cruz'), ['price' => 500]);
    }

    private function assigned(string $status = 'Assigned for Delivery', array $over = []): int
    {
        return $this->makeOrder($this->buyer, $this->shoes, $status, array_merge([
            'shipping_address' => self::LAGUNA_ADDRESS, 'delivery_rider_id' => $this->rider->id,
            'destination_center_id' => $this->laguna->id, 'total_amount' => 550,
        ], $over));
    }

    private function token(?User $who = null): string
    {
        return $this->postJson('/api/v1/login', ['email' => ($who ?? $this->rider)->email, 'password' => 'password123', 'device_name' => 'Test phone'])
            ->assertOk()->json('token');
    }

    private function api(string $token): static
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json']);
    }

    public function test_API01_only_approved_active_riders_can_sign_in(): void
    {
        $this->postJson('/api/v1/login', ['email' => $this->rider->email, 'password' => 'wrong'])->assertStatus(401);
        $this->postJson('/api/v1/login', ['email' => $this->buyer->email, 'password' => 'password123'])->assertStatus(403);

        $pending = $this->makeRider('Pending Verification');
        $this->postJson('/api/v1/login', ['email' => $pending->email, 'password' => 'password123'])->assertStatus(403)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'pending verification'));

        $token = $this->token();
        $this->assertStringStartsWith($this->rider->id . '|', $token);
        $this->assertNotSame($token, DB::table('api_tokens')->value('token_hash')); // only the hash is kept

        $this->getJson('/api/v1/me')->assertStatus(401);
        $this->api($token)->getJson('/api/v1/me')->assertOk()
            ->assertJsonPath('rider.id', $this->rider->id)
            ->assertJsonPath('failure_reasons.0', \App\Services\DeliveryService::REFUSED_REASON);
    }

    public function test_API02_rider_delivers_a_cod_parcel_with_a_photo(): void
    {
        $id = $this->assigned();
        $this->assigned('Out for Delivery', ['delivery_rider_id' => $this->riderFor($this->laguna)->id]); // someone else's
        $token = $this->token();

        $list = $this->api($token)->getJson('/api/v1/deliveries')->assertOk()->json('deliveries');
        $this->assertCount(1, $list);
        $this->assertSame($id, $list[0]['id']);
        $this->assertTrue($list[0]['cod']);
        $this->assertEquals(550, $list[0]['collect_amount']);
        $this->assertTrue($list[0]['can']['start']);

        $this->api($token)->getJson('/api/v1/deliveries/' . $id)->assertOk()
            ->assertJsonPath('delivery.maps_url', fn ($u) => str_contains($u, 'google.com/maps'));

        // Steps in order, with the same rules as the web pages.
        $this->api($token)->postJson('/api/v1/deliveries/' . $id . '/delivered')->assertStatus(422);
        $this->api($token)->postJson('/api/v1/deliveries/' . $id . '/out-for-delivery')->assertOk()->assertJsonPath('delivery.status', 'Out for Delivery');
        $this->api($token)->post('/api/v1/deliveries/' . $id . '/delivered', [])->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'photo'));
        $this->api($token)->post('/api/v1/deliveries/' . $id . '/delivered', ['photo' => $this->deliveryPhoto()])->assertOk()
            ->assertJsonPath('delivery.status', 'Delivered');

        $this->assertNotNull(DB::table('orders')->where('id', $id)->value('cod_collected_at'));
        $this->api($token)->getJson('/api/v1/me')->assertJsonPath('cod_to_hand_in.amount', 550)->assertJsonPath('stats.delivered_today', 1);
        $this->api($token)->getJson('/api/v1/earnings')->assertJsonPath('deliveries', 1)->assertJsonPath('total', 50);
        $this->api($token)->getJson('/api/v1/deliveries?filter=history')->assertJsonCount(1, 'deliveries');
    }

    public function test_API03_failed_delivery_needs_a_reason(): void
    {
        $id = $this->assigned('Out for Delivery');
        $token = $this->token();

        $this->api($token)->postJson('/api/v1/deliveries/' . $id . '/failed', ['reason' => 'Other'])->assertStatus(422);
        $this->api($token)->postJson('/api/v1/deliveries/' . $id . '/failed', ['reason' => 'Buyer not available'])->assertOk()
            ->assertJsonPath('delivery.status', 'Delivery Failed');
    }

    public function test_API04_another_riders_delivery_is_not_found(): void
    {
        $id = $this->assigned('Assigned for Delivery', ['delivery_rider_id' => $this->riderFor($this->laguna)->id]);
        $token = $this->token();

        $this->api($token)->getJson('/api/v1/deliveries/' . $id)->assertNotFound();
        $this->api($token)->postJson('/api/v1/deliveries/' . $id . '/out-for-delivery')->assertNotFound();
    }

    public function test_API05_logout_suspension_and_password_change_end_the_session(): void
    {
        $token = $this->token();
        $this->api($token)->postJson('/api/v1/logout')->assertOk();
        $this->api($token)->getJson('/api/v1/me')->assertStatus(401);

        $token = $this->token();
        DB::table('users')->where('id', $this->rider->id)->update(['status' => 'Suspended']);
        $this->api($token)->getJson('/api/v1/me')->assertStatus(401)->assertJsonPath('message', fn ($m) => str_contains($m, 'suspended'));
        $this->assertSame(0, DB::table('api_tokens')->count());

        DB::table('users')->where('id', $this->rider->id)->update(['status' => 'Active']);
        $token = $this->token();
        \App\Support\LoginGate::passwordChanged($this->rider);
        $this->api($token)->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_API06_notifications_and_marking_read(): void
    {
        $id = $this->assigned();
        $note = createNotification($this->rider->id, 'New Delivery Assignment', 'Order #' . $id, 'delivery', $id);
        $token = $this->token();

        $this->api($token)->getJson('/api/v1/notifications')->assertOk()
            ->assertJsonPath('notifications.0.order_id', $id)
            ->assertJsonPath('notifications.0.read', false);
        $this->api($token)->postJson('/api/v1/notifications/' . $note->id . '/read')->assertOk();
        $this->api($token)->getJson('/api/v1/me')->assertJsonPath('unread_notifications', 0);
    }
}
