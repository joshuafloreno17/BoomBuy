<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * The buyer bugs found in the full-system review (2026-11-06): B1 refund
 * amount, B2 second refund, B3 recipient name, B6 lost checkout selection.
 */
class BuyerReviewFixesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function requestRefund($buyer, int $orderId, int $itemId)
    {
        return $this->actingAsUser($buyer)->post(route('buyer.return-refund.store', $orderId), [
            'order_item_id' => $itemId,
            'request_type' => 'Refund',
            'reason' => 'Damaged item',
            'message' => 'Screen is cracked.',
            'photos' => [\Illuminate\Http\UploadedFile::fake()->createWithContent('item.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='))],
            'refund_method' => 'GCash',
            'refund_account_name' => 'Juan Dela Cruz',
            'refund_account_number' => '09171234567',
        ]);
    }

    /** B1: a BoomBuy (platform) voucher is taken off the refund too. */
    public function test_refund_takes_off_the_platform_voucher_share(): void
    {
        $buyer = $this->makeUser();
        $product = $this->makeProduct($this->makeSeller(), ['price' => 1000, 'stock' => 5]);
        $orderId = $this->makeOrder($buyer, $product, 'Delivered', [
            'total_amount' => 900,
            'platform_discount' => 100,
            'buyer_received_at' => now()->subDay(),
        ]);
        $itemId = DB::table('order_items')->where('order_id', $orderId)->value('id');

        $this->requestRefund($buyer, $orderId, $itemId)->assertSessionHas('success');

        $this->assertEquals(900.0, (float) DB::table('return_refund_requests')->where('order_item_id', $itemId)->value('refund_amount'));
    }

    /** A completed refund of the order's only item, as BoomBuy records it once sent. */
    private function refundedOrder(array $orderOverrides, float $refundAmount): array
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 1000, 'stock' => 5]);
        $orderId = $this->makeOrder($buyer, $product, 'Completed', $orderOverrides + [
            'commission_rate' => 10,
            'delivered_at' => now()->subDays(2),
            'buyer_received_at' => now()->subDay(),
        ]);
        $itemId = DB::table('order_items')->where('order_id', $orderId)->value('id');

        DB::table('return_refund_requests')->insert([
            'order_id' => $orderId, 'order_item_id' => $itemId, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'request_type' => 'Refund', 'reason' => 'Damaged item', 'status' => 'completed',
            'refund_amount' => $refundAmount, 'refunded_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$seller, $orderId];
    }

    /** R1: a fully refunded item earns the seller nothing, whichever voucher was used. */
    public function test_a_refunded_item_earns_the_seller_nothing(): void
    {
        // BoomBuy voucher: the buyer got ₱900 back; BoomBuy carries its own ₱100.
        [$seller, $orderId] = $this->refundedOrder(['total_amount' => 900, 'platform_discount' => 100], 900);
        $row = \App\Support\SellerBalance::orders((int) $seller->id)->firstWhere('id', $orderId);
        $this->assertEquals(0.0, $row->earning);
        $this->assertEquals(0.0, \App\Support\SellerBalance::summary((int) $seller->id)['earnings']);

        // The seller's own ₱100 store voucher: also nothing left (and never below zero).
        $seller2 = $this->makeSeller('shoes', 'Second Shop');
        $voucher = \App\Models\Voucher::create(['seller_id' => $seller2->id, 'code' => 'STORE100', 'discount_type' => 'fixed', 'discount_value' => 100, 'min_order_amount' => 100, 'is_active' => true]);
        [$s2, $orderId2] = $this->refundedOrder(['total_amount' => 900, 'voucher_code' => $voucher->code, 'discount_amount' => 100], 900);
        DB::table('order_items')->where('order_id', $orderId2)->update(['seller_id' => $seller2->id]);
        DB::table('return_refund_requests')->where('order_id', $orderId2)->update(['seller_id' => $seller2->id]);
        $row2 = \App\Support\SellerBalance::orders((int) $seller2->id)->firstWhere('id', $orderId2);
        $this->assertEquals(0.0, $row2->earning);
    }

    /** B2: an item already refunded can't be refunded again; a rejected one can be asked again. */
    public function test_a_refunded_item_cannot_be_refunded_twice(): void
    {
        $buyer = $this->makeUser();
        $product = $this->makeProduct($this->makeSeller(), ['stock' => 5]);
        $orderId = $this->makeOrder($buyer, $product, 'Delivered', ['buyer_received_at' => now()->subDay()]);
        $itemId = DB::table('order_items')->where('order_id', $orderId)->value('id');

        $this->requestRefund($buyer, $orderId, $itemId)->assertSessionHas('success');

        DB::table('return_refund_requests')->where('order_item_id', $itemId)->update(['status' => 'rejected']);
        $this->requestRefund($buyer, $orderId, $itemId)->assertSessionHas('success');

        DB::table('return_refund_requests')->where('order_item_id', $itemId)->update(['status' => 'completed']);
        $this->requestRefund($buyer, $orderId, $itemId)->assertSessionHas('error');

        $this->assertSame(2, DB::table('return_refund_requests')->where('order_item_id', $itemId)->count());
    }

    /** B3: the saved address's recipient is the name on the order, unless the address was changed. */
    public function test_the_saved_address_recipient_is_the_shipping_name(): void
    {
        $buyer = $this->makeUser('buyer', ['phone' => '09171234567']);
        $product = $this->makeProduct($this->makeSeller(), ['stock' => 10]);
        $savedId = DB::table('buyer_addresses')->insertGetId([
            'user_id' => $buyer->id,
            'label' => 'Nanay',
            'recipient_name' => 'Maria Santos',
            'phone' => '09181112222',
            'address' => '1 Rizal St, Cebu City',
            'is_default' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $place = fn (array $form) => $this->actingAsUser($buyer, ['cart' => ["{$product->id}:0" => 1]])
            ->post(route('checkout.place'), $form + ['payment' => 'Cash on Delivery'])
            ->assertRedirect();

        $place(['address' => '1 Rizal St, Cebu City', 'phone' => '0918 111 2222', 'saved_address' => $savedId]);
        $this->assertSame('Maria Santos', DB::table('orders')->latest('id')->value('shipping_name'));

        // The phone was changed by hand: the account name, not the saved recipient.
        $place(['address' => '1 Rizal St, Cebu City', 'phone' => '09170000000', 'saved_address' => $savedId]);
        $this->assertSame($buyer->name, DB::table('orders')->latest('id')->value('shipping_name'));

        // Another buyer's saved address id gives nothing.
        $other = $this->makeUser('buyer', ['phone' => '09175555555']);
        $this->actingAsUser($other, ['cart' => ["{$product->id}:0" => 1]])
            ->post(route('checkout.place'), ['address' => '1 Rizal St, Cebu City', 'phone' => '09181112222', 'saved_address' => $savedId, 'payment' => 'Cash on Delivery'])
            ->assertRedirect();
        $this->assertSame($other->name, DB::table('orders')->latest('id')->value('shipping_name'));
    }

    /** B6: the picked items are gone — checkout asks again instead of showing the whole cart. */
    public function test_checkout_does_not_fall_back_to_the_whole_cart(): void
    {
        $buyer = $this->makeUser('buyer', ['phone' => '09171234567']);
        $product = $this->makeProduct($this->makeSeller(), ['stock' => 10]);

        $this->actingAsUser($buyer, [
            'cart' => ["{$product->id}:0" => 1],
            'checkout_selection' => ['999:0'],
        ])->get(route('checkout'))
            ->assertRedirect(route('cart'))
            ->assertSessionHas('error');
    }
}
