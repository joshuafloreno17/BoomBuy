<?php

namespace Tests\Feature;

use App\Models\FlashSale;
use App\Support\SellerBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * The seller bugs found in the full-system review (2026-11-06): S1 payouts
 * and open returns / refunds after a payout, S2 a flash sale dearer than
 * the price, S3 one pending payout at a time.
 */
class SellerReviewFixesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    /** A Completed ₱1,000 order (10% commission → ₱900 earning). */
    private function completedOrder(): array
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 1000, 'stock' => 5]);
        $orderId = $this->makeOrder($buyer, $product, 'Completed', [
            'total_amount' => 1000,
            'commission_rate' => 10,
            'delivered_at' => now()->subDays(2),
            'buyer_received_at' => now()->subDay(),
        ]);
        $itemId = DB::table('order_items')->where('order_id', $orderId)->value('id');

        return [$buyer, $seller, $orderId, $itemId];
    }

    private function returnRequest($buyer, $seller, int $orderId, int $itemId, string $status, array $extra = []): int
    {
        return DB::table('return_refund_requests')->insertGetId($extra + [
            'order_id' => $orderId, 'order_item_id' => $itemId, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'request_type' => 'Refund', 'reason' => 'Damaged item', 'status' => $status,
            'refund_amount' => 1000, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** S1: an open return holds the order's money in Pending; it can't be paid out. */
    public function test_an_open_return_holds_the_money_out_of_available(): void
    {
        [$buyer, $seller, $orderId, $itemId] = $this->completedOrder();
        $this->assertSame(900.0, SellerBalance::balances($seller->id)['available']);

        $this->returnRequest($buyer, $seller, $orderId, $itemId, 'pending');

        $balances = SellerBalance::balances($seller->id);
        $this->assertSame(0.0, $balances['available']);
        $this->assertSame(900.0, $balances['held']);
        $this->assertSame(900.0, $balances['pending']);

        $this->actingAsUser($seller)->post(route('seller.payouts.store'), ['amount' => '100.00'])->assertSessionHasErrors('amount');
        $this->assertSame(0, DB::table('seller_payout_requests')->count());

        // Rejected (and not escalated): the money is Available again.
        DB::table('return_refund_requests')->update(['status' => 'rejected']);
        $this->assertSame(900.0, SellerBalance::balances($seller->id)['available']);
    }

    /** S1: a refund after a payout shows as owed and blocks the next payout. */
    public function test_a_refund_after_a_payout_is_owed_not_hidden(): void
    {
        [$buyer, $seller, $orderId, $itemId] = $this->completedOrder();

        $this->actingAsUser($seller)->post(route('seller.payouts.store'), ['amount' => '900.00'])->assertSessionHasNoErrors();
        DB::table('seller_payout_requests')->update(['status' => 'Paid']);

        // The buyer is refunded afterwards.
        $this->returnRequest($buyer, $seller, $orderId, $itemId, 'completed', ['refunded_at' => now()]);

        $balances = SellerBalance::balances($seller->id);
        $this->assertSame(0.0, $balances['available']);
        $this->assertSame(900.0, $balances['owed']);

        $this->actingAsUser($seller)->get(route('seller.earnings'))->assertOk()->assertSee('You owe BoomBuy ₱900.00');
        $this->actingAsUser($seller)->post(route('seller.payouts.store'), ['amount' => '100.00'])
            ->assertSessionHasErrors(['amount' => 'You owe BoomBuy ₱900.00 for a refund made after a payout. It comes out of your next earnings before you can request a payout.']);
    }

    /** S3: a second request while one is pending is refused (checked under a lock). */
    public function test_only_one_pending_payout(): void
    {
        [, $seller] = $this->completedOrder();

        $this->actingAsUser($seller)->post(route('seller.payouts.store'), ['amount' => '100.00'])->assertSessionHasNoErrors();
        $this->actingAsUser($seller)->post(route('seller.payouts.store'), ['amount' => '100.00'])->assertSessionHasErrors('amount');

        $this->assertSame(1, DB::table('seller_payout_requests')->count());
    }

    /** S4: an upcoming flash sale that is no longer cheaper isn't announced or reminded. */
    public function test_an_upcoming_flash_sale_dearer_than_the_price_is_not_announced(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 1000, 'stock' => 10]);
        $sale = FlashSale::create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'starts_at' => now()->addMinutes(2), 'ends_at' => now()->addHour(),
            'flash_price' => 800, 'quantity' => 5,
        ]);
        $buyer = $this->makeUser();
        \App\Support\FlashReminders::set($buyer->id, $sale->id);

        $this->assertSame($sale->id, $product->fresh()->upcomingFlash()?->id);

        $product->update(['price' => 700]);
        $this->assertNull($product->fresh()->upcomingFlash());

        $this->get(route('product.details', $product->id))->assertOk()->assertDontSee('Upcoming flash sale');

        \App\Support\FlashReminders::sweep();
        $this->assertDatabaseMissing('notifications', ['user_id' => $buyer->id, 'title' => 'Flash sale starting soon']);
    }

    /** S2: a flash sale that is no longer cheaper than the price is ignored. */
    public function test_a_flash_sale_dearer_than_the_price_is_ignored(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 1000, 'stock' => 10]);

        FlashSale::create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(),
            'flash_price' => 800, 'quantity' => 5,
        ]);
        FlashSale::forgetLive();

        $this->assertSame(800.0, $product->fresh()->unitPrice());

        // The seller lowers the price under the flash price: the buyer pays ₱700.
        $product->update(['price' => 700]);
        FlashSale::forgetLive();
        $fresh = $product->fresh();

        $this->assertNull($fresh->liveFlash());
        $this->assertSame(700.0, $fresh->unitPrice());
        $pieces = \App\Support\CartPricing::pieces($fresh, null, 2);
        $this->assertSame([700.0], array_values(array_unique(array_map(fn ($p) => (float) $p['price'], $pieces))));
    }
}
