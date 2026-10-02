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
        ]);
    }

    public function test_return_is_approved_received_and_restocked(): void
    {
        [$buyer, $seller, $product, $orderId, $itemId] = $this->receivedOrder(1, 5);

        $this->requestReturn($buyer, $orderId, $itemId)->assertSessionHas('success');

        $requestId = DB::table('return_refund_requests')->value('id');
        $this->assertDatabaseHas('notifications', ['user_id' => $seller->id, 'title' => 'New Return / Refund Request']);

        $this->flushSession();

        $this->actingAsUser($seller)->post(route('seller.return-refund.approve', $requestId))->assertSessionHas('success');
        $this->actingAsUser($seller)->post(route('seller.return-refund.returned', $requestId))->assertSessionHas('success');

        $this->assertDatabaseHas('return_refund_requests', ['id' => $requestId, 'status' => 'returned']);
        $this->assertSame(6, $product->fresh()->stock);
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'title' => 'Item Marked as Returned']);
    }

    public function test_refund_goes_through_processing_to_completed(): void
    {
        [$buyer, $seller, , $orderId, $itemId] = $this->receivedOrder();

        $this->requestReturn($buyer, $orderId, $itemId, 'Refund')->assertSessionHas('success');
        $requestId = DB::table('return_refund_requests')->value('id');

        $this->flushSession();

        // Can't complete before it's approved and processing.
        $this->actingAsUser($seller)->post(route('seller.return-refund.complete', $requestId))->assertSessionHas('error');

        $this->actingAsUser($seller)->post(route('seller.return-refund.approve', $requestId));
        $this->actingAsUser($seller)->post(route('seller.return-refund.processing', $requestId))->assertSessionHas('success');
        $this->actingAsUser($seller)->post(route('seller.return-refund.complete', $requestId))->assertSessionHas('success');

        $this->assertDatabaseHas('return_refund_requests', ['id' => $requestId, 'status' => 'completed']);
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'title' => 'Refund Completed']);
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
