<?php

namespace Tests\Feature;

use App\Support\AutoReceive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class AutoReceiveTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private function deliveredOrder(int $daysAgo): array
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $orderId = $this->makeOrder($buyer, $this->makeProduct($seller), 'Delivered', [
            'delivered_at' => now()->subDays($daysAgo),
        ]);

        return [$buyer, $seller, $orderId];
    }

    public function test_unconfirmed_orders_count_as_received_three_days_after_delivery(): void
    {
        [$buyer, $seller, $overdue] = $this->deliveredOrder(4);
        [, , $recent] = $this->deliveredOrder(1);

        $this->assertSame(1, AutoReceive::sweep(force: true));

        // Received on the day it fell due, not on the day the sweep ran.
        $receivedAt = DB::table('orders')->where('id', $overdue)->value('buyer_received_at');
        $this->assertSame(now()->subDays(1)->toDateString(), substr($receivedAt, 0, 10));
        $this->assertNull(DB::table('orders')->where('id', $recent)->value('buyer_received_at'));

        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'title' => 'Order Marked as Received']);
        $this->assertDatabaseHas('notifications', ['user_id' => $seller->id, 'reference_id' => $overdue]);

        // Running it again changes nothing.
        $this->assertSame(0, AutoReceive::sweep(force: true));
    }

    public function test_a_page_visit_runs_it_and_the_buyer_can_then_request_a_return(): void
    {
        [$buyer, , $orderId] = $this->deliveredOrder(4);

        $this->actingAsUser($buyer)->get(route('buyer.orders'))->assertOk();
        $this->assertNotNull(DB::table('orders')->where('id', $orderId)->value('buyer_received_at'));

        $itemId = DB::table('order_items')->where('order_id', $orderId)->value('id');
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->actingAsUser($buyer)
            ->post(route('buyer.return-refund.store', $orderId), [
                'order_item_id' => $itemId,
                'request_type' => 'Return',
                'reason' => 'Damaged item',
                'refund_method' => 'GCash',
                'refund_account_name' => 'Buyer Name',
                'refund_account_number' => '09171234567',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('return_refund_requests', ['order_id' => $orderId]);
    }

    public function test_the_order_card_says_when_it_confirms_itself(): void
    {
        [$buyer] = $this->deliveredOrder(1);

        $this->actingAsUser($buyer)->get(route('buyer.orders'))
            ->assertOk()
            ->assertSee('it will be confirmed for you on ' . now()->addDays(2)->format('M j'));
    }
}
