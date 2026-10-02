<?php

namespace Tests\Feature;

use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class VoucherTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function voucherFor($seller, array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'seller_id' => $seller->id,
            'code' => 'SAVE10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 0,
            'is_active' => true,
        ], $overrides));
    }

    public function test_seller_creates_a_voucher_but_not_over_100_percent(): void
    {
        $seller = $this->makeSeller();

        $this->actingAsUser($seller)
            ->post(route('seller.vouchers.store'), ['code' => 'huge', 'discount_type' => 'percentage', 'discount_value' => 150])
            ->assertSessionHasErrors('discount_value');

        $this->actingAsUser($seller)
            ->post(route('seller.vouchers.store'), ['code' => 'save10', 'discount_type' => 'percentage', 'discount_value' => 10])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('vouchers', ['seller_id' => $seller->id, 'code' => 'SAVE10', 'is_active' => true]);
    }

    public function test_seller_cannot_touch_another_sellers_voucher(): void
    {
        $voucher = $this->voucherFor($this->makeSeller());
        $other = $this->makeSeller('electronics', 'Other Shop');

        $this->actingAsUser($other)->post(route('seller.vouchers.toggle', $voucher->id))->assertNotFound();
        $this->actingAsUser($other)->delete(route('seller.vouchers.delete', $voucher->id));

        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'is_active' => true]);
    }

    public function test_voucher_only_applies_when_that_sellers_items_are_in_the_cart(): void
    {
        $voucherSeller = $this->makeSeller();
        $this->voucherFor($voucherSeller);
        $otherProduct = $this->makeProduct($this->makeSeller('electronics', 'Other Shop'));

        $this->actingAsUser($this->makeUser(), ['cart' => ["{$otherProduct->id}:0" => 1]])
            ->post(route('cart.voucher.apply'), ['voucher_code' => 'save10'])
            ->assertSessionHas('error');

        $this->assertNull(session('applied_voucher'));
    }

    public function test_expired_inactive_or_below_minimum_vouchers_are_refused(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 500]);
        $cart = ['cart' => ["{$product->id}:0" => 1]];

        $this->voucherFor($seller, ['code' => 'OLD', 'expires_at' => now()->subDay()]);
        $this->voucherFor($seller, ['code' => 'OFF', 'is_active' => false]);
        $this->voucherFor($seller, ['code' => 'BIG', 'min_order_amount' => 1000]);

        foreach (['OLD', 'OFF', 'BIG'] as $code) {
            $this->actingAsUser($this->makeUser(), $cart)
                ->post(route('cart.voucher.apply'), ['voucher_code' => $code])
                ->assertSessionHas('error');
        }
    }

    public function test_voucher_discounts_only_its_own_sellers_order_and_counts_one_use(): void
    {
        $buyer = $this->makeUser();
        $voucherSeller = $this->makeSeller();
        $otherSeller = $this->makeSeller('electronics', 'Other Shop');

        $shoe = $this->makeProduct($voucherSeller, ['price' => 2000]);
        $phone = $this->makeProduct($otherSeller, ['price' => 3000]);
        $voucher = $this->voucherFor($voucherSeller);

        $this->actingAsUser($buyer, [
            'cart' => ["{$shoe->id}:0" => 1, "{$phone->id}:0" => 1],
            'applied_voucher' => 'SAVE10',
        ])->post('/checkout/place-order', [
            'address' => '1 Rizal St, Cebu City',
            'phone' => '09171234567',
            'payment' => 'Cash on Delivery',
        ])->assertRedirect();

        $shoeOrder = \DB::table('order_items')->where('product_id', $shoe->id)->value('order_id');
        $phoneOrder = \DB::table('order_items')->where('product_id', $phone->id)->value('order_id');

        $this->assertNotSame($shoeOrder, $phoneOrder);
        $this->assertDatabaseHas('orders', ['id' => $shoeOrder, 'voucher_code' => 'SAVE10', 'discount_amount' => 200]);
        $this->assertDatabaseHas('orders', ['id' => $phoneOrder, 'voucher_code' => null, 'discount_amount' => 0]);
        $this->assertSame(1, (int) $voucher->fresh()->used_count);
    }

    public function test_cancelling_the_order_gives_the_voucher_use_back(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $voucher = $this->voucherFor($seller, ['used_count' => 1, 'max_uses' => 1]);

        $orderId = $this->makeOrder($buyer, $this->makeProduct($seller), 'Pending', ['voucher_code' => 'SAVE10']);

        $this->actingAsUser($buyer)
            ->post(route('buyer.order.cancel', $orderId), ['cancel_reason' => 'Changed my mind'])
            ->assertSessionHas('success');

        $this->assertSame(0, (int) $voucher->fresh()->used_count);
    }
}
