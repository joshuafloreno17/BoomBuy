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

    public function test_unconfirmed_orders_count_as_received_seven_days_after_delivery(): void
    {
        // Buyer Modules A11: 7 days (168 hours) after delivery.
        [$buyer, $seller, $overdue] = $this->deliveredOrder(8);
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

    public function test_a_page_visit_runs_it_and_the_return_window_has_then_passed(): void
    {
        [$buyer, , $orderId] = $this->deliveredOrder(8);

        $this->actingAsUser($buyer)->get(route('buyer.orders'))->assertOk();
        $this->assertNotNull(DB::table('orders')->where('id', $orderId)->value('buyer_received_at'));

        $itemId = DB::table('order_items')->where('order_id', $orderId)->value('id');
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->actingAsUser($buyer)
            ->post(route('buyer.return-refund.store', $orderId), [
                'order_item_id' => $itemId,
                'request_type' => 'Return',
                'reason' => 'Damaged item',
                'message' => 'The item arrived damaged in the box.', 'photos' => [\Illuminate\Http\UploadedFile::fake()->createWithContent('item.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='))],
                'refund_method' => 'GCash',
                'refund_account_name' => 'Buyer Name',
                'refund_account_number' => '09171234567',
            ])
            // The 7 days counted from delivery are over (A11).
            ->assertSessionHas('error', 'The 7-day return/refund window for this order has passed.');

        $this->assertDatabaseMissing('return_refund_requests', ['order_id' => $orderId]);
    }

    public function test_the_order_card_says_when_it_confirms_itself(): void
    {
        [$buyer] = $this->deliveredOrder(1);

        $this->actingAsUser($buyer)->get(route('buyer.orders'))
            ->assertOk()
            ->assertSee('it will be confirmed for you on ' . now()->addDays(6)->format('M j'));
    }
}
