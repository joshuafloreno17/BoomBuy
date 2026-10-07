<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class ReturnRefundTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    /** A delivered order the buyer confirmed receiving $daysAgo days ago. */
    private function receivedOrder(int $daysAgo = 1, int $stock = 5): array
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['stock' => $stock]);

        $orderId = $this->makeOrder($buyer, $product, 'Delivered', [
            'buyer_received_at' => now()->subDays($daysAgo),
        ]);

        $itemId = DB::table('order_items')->where('order_id', $orderId)->value('id');

        return [$buyer, $seller, $product, $orderId, $itemId];
    }

    private function requestReturn($buyer, int $orderId, int $itemId, string $type = 'Return')
    {
        return $this->actingAsUser($buyer)->post(route('buyer.return-refund.store', $orderId), [
            'order_item_id' => $itemId,
            'request_type' => $type,
            'reason' => 'Damaged item',
            'message' => 'Screen is cracked.',
            'refund_method' => 'GCash',
            'refund_account_name' => 'Juan Dela Cruz',
            'refund_account_number' => '09171234567',
        ]);
    }

    public function test_return_travels_back_is_restocked_and_boombuy_sends_the_refund(): void
    {
        [$buyer, $seller, $product, $orderId, $itemId] = $this->receivedOrder(1, 5);
        $logistics = $this->makeLogistics();

        $this->requestReturn($buyer, $orderId, $itemId)->assertSessionHas('success');

        $requestId = DB::table('return_refund_requests')->value('id');
        $this->assertDatabaseHas('notifications', ['user_id' => $seller->id, 'title' => 'New Return / Refund Request']);
        $this->assertDatabaseHas('return_refund_requests', ['id' => $requestId, 'refund_method' => 'GCash', 'refund_account_number' => '09171234567']);

        $this->flushSession();
        $this->actingAsUser($seller)->post(route('seller.return-refund.approve', $requestId))->assertSessionHas('success');
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'title' => 'Return Approved']);

        // The buyer drops it at the Sorting Center; the seller collects it there.
        $this->flushSession();
        $this->actingAsUser($logistics)->post(route('logistics.returns.receive', $requestId))->assertSessionHas('success');
        $this->assertDatabaseHas('return_refund_requests', ['id' => $requestId, 'status' => 'ready_for_seller']);
        $this->actingAsUser($logistics)->post(route('logistics.returns.hand-to-seller', $requestId))->assertSessionHas('success');
        $this->assertDatabaseHas('return_refund_requests', ['id' => $requestId, 'status' => 'refund_pending']);

        $this->flushSession();
        $this->actingAsUser($seller)->post(route('seller.return-refund.restock', $requestId))->assertSessionHas('success');
        $this->actingAsUser($seller)->post(route('seller.return-refund.restock', $requestId))->assertSessionHas('error');
        $this->assertSame(6, $product->fresh()->stock);

        $this->flushSession();
        $this->actingAsAdmin()->post(route('admin.returns.refunded', $requestId), ['reference' => 'GC-778899'])->assertSessionHas('success');

        $this->assertDatabaseHas('return_refund_requests', ['id' => $requestId, 'status' => 'completed', 'refund_reference' => 'GC-778899']);
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'title' => 'Refund Sent']);
    }

    public function test_refund_only_goes_straight_to_boombuy_once_approved(): void
    {
        [$buyer, $seller, , $orderId, $itemId] = $this->receivedOrder();

        $this->requestReturn($buyer, $orderId, $itemId, 'Refund')->assertSessionHas('success');
        $requestId = DB::table('return_refund_requests')->value('id');

        // Nothing to send before it's approved.
        $this->flushSession();
        $this->actingAsAdmin()->post(route('admin.returns.refunded', $requestId), ['reference' => 'X'])->assertSessionHas('error');

        $this->flushSession();
        $this->actingAsUser($seller)->post(route('seller.return-refund.approve', $requestId))->assertSessionHas('success');
        $this->assertDatabaseHas('return_refund_requests', ['id' => $requestId, 'status' => 'refund_pending']);

        $this->flushSession();
        $this->actingAsAdmin()->post(route('admin.returns.refunded', $requestId), ['reference' => ''])->assertSessionHas('error');
        $this->post(route('admin.returns.refunded', $requestId), ['reference' => 'MAYA-1234'])->assertSessionHas('success');

        $this->assertDatabaseHas('return_refund_requests', ['id' => $requestId, 'status' => 'completed']);
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'title' => 'Refund Sent']);
    }

    public function test_request_is_refused_after_the_7_day_window(): void
    {
        [$buyer, , , $orderId, $itemId] = $this->receivedOrder(8);

        $this->requestReturn($buyer, $orderId, $itemId)->assertSessionHas('error');

        $this->assertDatabaseCount('return_refund_requests', 0);
    }

    public function test_only_one_open_request_per_item(): void
    {
        [$buyer, , , $orderId, $itemId] = $this->receivedOrder();

        $this->requestReturn($buyer, $orderId, $itemId)->assertSessionHas('success');
        $this->requestReturn($buyer, $orderId, $itemId)->assertSessionHas('error');

        $this->assertDatabaseCount('return_refund_requests', 1);
    }

    public function test_another_seller_cannot_handle_the_request(): void
    {
        [$buyer, , , $orderId, $itemId] = $this->receivedOrder();
        $this->requestReturn($buyer, $orderId, $itemId);
        $requestId = DB::table('return_refund_requests')->value('id');

        $this->flushSession();

        $this->actingAsUser($this->makeSeller('electronics', 'Other Shop'))
            ->post(route('seller.return-refund.approve', $requestId))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('return_refund_requests', ['id' => $requestId, 'status' => 'pending']);
    }
}
