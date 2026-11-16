<?php

namespace Tests\Feature;

use App\Models\FlashSale;
use App\Models\ProductSale;
use App\Models\Voucher;
use App\Models\VoucherClaim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * The voucher box on the checkout page, and store/flash sales starting and
 * ending exactly at their set times (no job needed — prices are worked out
 * on every request).
 */
class CheckoutVoucherAndSaleTimingTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->travelTo(now()->setDate(2026, 11, 16)->setTime(10, 0));
    }

    private function buyer()
    {
        return $this->makeUser('buyer', ['phone' => '09171234567', 'address' => '1 Rizal St, Cebu City']);
    }

    private function voucher($seller, array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'seller_id' => $seller->id,
            'code' => 'SAVE50',
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'min_order_amount' => 0,
            'is_active' => true,
        ], $overrides));
    }

    // ---------------------------------------------------------------- vouchers

    public function test_checkout_page_has_a_voucher_code_box(): void
    {
        $product = $this->makeProduct($this->makeSeller(), ['price' => 400]);

        $this->actingAsUser($this->buyer(), ['cart' => ["{$product->id}:0" => 1]])
            ->get(route('checkout'))
            ->assertOk()
            ->assertSee('id="checkoutVoucherForm"', false)
            ->assertSee('Enter voucher code');
    }

    public function test_code_typed_at_checkout_is_applied_and_shown_in_the_total(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 400]);
        $this->voucher($seller);
        $buyer = $this->buyer();
        $session = ['cart' => ["{$product->id}:0" => 1]];

        $this->actingAsUser($buyer, $session)
            ->from(route('checkout'))
            ->post(route('cart.voucher.apply'), ['voucher_code' => 'save50', 'from' => 'checkout'])
            ->assertRedirect(route('checkout'))
            ->assertSessionHas('voucher_success');

        $this->get(route('checkout'))
            ->assertOk()
            ->assertSee('Store voucher (SAVE50)')
            ->assertSee('−₱200.00', false);
    }

    public function test_bad_code_at_checkout_shows_the_reason_in_the_voucher_box(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 400]);
        $this->voucher($seller, ['code' => 'BIG', 'min_order_amount' => 1000]);
        $session = ['cart' => ["{$product->id}:0" => 1]];

        $this->actingAsUser($this->buyer(), $session)
            ->from(route('checkout'))
            ->post(route('cart.voucher.apply'), ['voucher_code' => 'nope', 'from' => 'checkout'])
            ->assertSessionHas('voucher_error', 'Invalid voucher code.')
            ->assertSessionMissing('error');

        $this->from(route('checkout'))
            ->post(route('cart.voucher.apply'), ['voucher_code' => 'BIG', 'from' => 'checkout'])
            ->assertSessionHas('voucher_error');

        $this->get(route('checkout'))->assertSee('minimum spend');
    }

    public function test_checkout_checks_the_voucher_against_only_the_items_being_checked_out(): void
    {
        $seller = $this->makeSeller();
        $other = $this->makeSeller('electronics', 'Other Shop');
        $mine = $this->makeProduct($seller, ['price' => 400]);
        $theirs = $this->makeProduct($other, ['price' => 400]);
        $this->voucher($seller);

        // The voucher's store is in the cart, but not among the items picked for checkout.
        $this->actingAsUser($this->buyer(), [
            'cart' => ["{$mine->id}:0" => 1, "{$theirs->id}:0" => 1],
            'checkout_selection' => ["{$theirs->id}:0"],
        ])
            ->from(route('checkout'))
            ->post(route('cart.voucher.apply'), ['voucher_code' => 'SAVE50', 'from' => 'checkout'])
            ->assertSessionHas('voucher_error');
    }

    public function test_claimed_voucher_shows_as_a_chip_and_can_be_removed_at_checkout(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 400]);
        $voucher = $this->voucher($seller, ['display' => 'claimable']);
        $buyer = $this->buyer();
        VoucherClaim::create(['voucher_id' => $voucher->id, 'user_id' => $buyer->id]);

        $this->actingAsUser($buyer, ['cart' => ["{$product->id}:0" => 1]])
            ->get(route('checkout'))
            ->assertSee('From My Vouchers')
            ->assertSee('value="SAVE50"', false);

        $this->from(route('checkout'))->post(route('cart.voucher.apply'), ['voucher_code' => 'SAVE50', 'from' => 'checkout']);
        $this->get(route('checkout'))->assertSee('Store voucher (SAVE50)')->assertDontSee('From My Vouchers');

        $this->from(route('checkout'))
            ->post(route('cart.voucher.remove'), ['voucher_code' => 'SAVE50', 'from' => 'checkout'])
            ->assertSessionHas('voucher_success', 'Voucher removed.');
        $this->get(route('checkout'))->assertDontSee('Store voucher (SAVE50)');
    }

    // ------------------------------------------------------------- sale timing

    public function test_store_sale_starts_and_ends_exactly_on_time(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 1000]);
        ProductSale::create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'discount_type' => 'percent', 'discount_value' => 50,
            'starts_at' => now()->setTime(12, 0), 'ends_at' => now()->setTime(14, 0),
        ]);

        $priceAt = function (int $h, int $m, int $s) use ($product) {
            $this->travelTo(now()->setTime($h, $m, $s));

            return $product->fresh()->unitPrice();
        };

        $this->assertSame(1000.0, $priceAt(11, 59, 59));
        $this->assertSame(500.0, $priceAt(12, 0, 0));
        $this->assertSame(500.0, $priceAt(13, 59, 59));
        $this->assertSame(1000.0, $priceAt(14, 0, 0));
    }

    public function test_ending_a_sale_early_brings_back_the_regular_price_at_once(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 1000]);
        $sale = ProductSale::create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'discount_type' => 'percent', 'discount_value' => 50,
            'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(),
        ]);
        $this->assertSame(500.0, $product->fresh()->unitPrice());

        $this->actingAsUser($seller)->post(route('seller.sales.end', $sale->id))->assertSessionHas('success');

        $this->travel(1)->seconds();
        $this->assertSame(1000.0, $product->fresh()->unitPrice());
    }

    public function test_cart_and_order_use_the_sale_price_only_while_it_runs(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 1000]);
        ProductSale::create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'discount_type' => 'percent', 'discount_value' => 50,
            'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(),
        ]);
        $buyer = $this->buyer();
        $cart = ['cart' => ["{$product->id}:0" => 1]];

        $this->actingAsUser($buyer, $cart)->get(route('cart'))->assertSee('500.00');

        $this->travelTo(now()->addHour());
        $this->actingAsUser($buyer, $cart)->get(route('cart'))->assertSee('1,000.00')->assertDontSee('₱500.00');
    }

    public function test_place_order_after_the_sale_ended_warns_instead_of_charging_silently(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 1000]);
        ProductSale::create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'discount_type' => 'percent', 'discount_value' => 50,
            'starts_at' => now()->subHour(), 'ends_at' => now()->addMinute(),
        ]);
        $buyer = $this->buyer();
        $cart = ['cart' => ["{$product->id}:0" => 1]];

        // The checkout page was shown with the sale price…
        $page = $this->actingAsUser($buyer, $cart)->get(route('checkout'))->assertOk();
        preg_match('/name="expected_total"[^>]*value="([\d.]+)"/', $page->getContent(), $m);
        $shown = (float) ($m[1] ?? 0);
        $this->assertGreaterThan(0, $shown);

        // …and the sale ends before Place Order.
        $this->travel(2)->minutes();
        $this->post(route('checkout.place'), [
            'address' => '1 Rizal St, Cebu City', 'phone' => '09171234567', 'payment' => 'COD',
            'expected_total' => number_format($shown, 2, '.', ''),
        ]);

        $this->assertDatabaseMissing('orders', ['buyer_id' => $buyer->id]);
    }

    public function test_flash_sale_runs_only_in_its_slot(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 1000, 'stock' => 10]);
        FlashSale::create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'starts_at' => now()->setTime(12, 0), 'ends_at' => now()->setTime(14, 0),
            'flash_price' => 299, 'quantity' => 5,
        ]);

        $priceAt = function (int $h, int $m, int $s) use ($product) {
            $this->travelTo(now()->setTime($h, $m, $s));
            FlashSale::forgetLive();

            return $product->fresh()->unitPrice();
        };

        $this->assertSame(1000.0, $priceAt(11, 59, 59));
        $this->assertSame(299.0, $priceAt(12, 0, 0));
        $this->assertSame(1000.0, $priceAt(14, 0, 0));
    }

    public function test_product_page_reloads_itself_when_the_sale_ends_using_server_time(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 1000]);
        ProductSale::create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'discount_type' => 'percent', 'discount_value' => 50,
            'starts_at' => now()->subHour(), 'ends_at' => now()->addMinutes(10),
        ]);

        $this->get(route('product.details', $product->id))
            ->assertOk()
            ->assertSee('data-price-changes-in="600"', false);
    }

    public function test_product_page_also_reloads_when_an_upcoming_sale_starts(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['price' => 1000]);
        ProductSale::create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'discount_type' => 'percent', 'discount_value' => 50,
            'starts_at' => now()->addMinutes(5), 'ends_at' => now()->addHour(),
        ]);

        $this->get(route('product.details', $product->id))
            ->assertOk()
            ->assertSee('data-price-changes-in="300"', false);
    }
}
