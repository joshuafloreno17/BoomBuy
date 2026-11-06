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
