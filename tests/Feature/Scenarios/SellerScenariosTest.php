<?php

namespace Tests\Feature\Scenarios;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\SortingCenter;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Test Plan → "Seller" (S01–S18), plus the seller bugs from the review. */
class SellerScenariosTest extends TestCase
{
    use RefreshDatabase;
    use ScenarioSetup;

    private SortingCenter $laguna;
    private User $seller;
    private Product $shoes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();

        $this->laguna = $this->center('Laguna', 'Santa Cruz');
        $this->seller = $this->sellerIn('Laguna', 'Santa Cruz', 'shoes', 'Santa Cruz Kicks');
        $this->shoes = $this->makeProduct($this->seller, ['name' => 'Canvas Sneakers', 'price' => 500, 'stock' => 10]);
    }

    private function asSeller(): static
    {
        return $this->actingAsUser($this->seller);
    }

    private function productForm(array $over = []): array
    {
        return array_merge([
            'name' => 'Running Shoes ' . Str::random(3),
            'category' => 'shoes',
            'price' => 1200,
            'stock' => 5,
            'description' => 'Light and comfy.',
            'photos' => [$this->png('shoe.png')],
        ], $over);
    }

    // ---------------- Account ----------------

    public function test_S01_pending_and_rejected_sellers_cannot_log_in(): void
    {
        $pending = $this->makeUser('seller');
        DB::table('seller_applications')->insert(['user_id' => $pending->id, 'full_name' => 'P', 'phone' => '0917', 'address' => 'x', 'status' => 'Pending Verification', 'created_at' => now(), 'updated_at' => now()]);
        $rejected = $this->makeUser('seller');
        DB::table('seller_applications')->insert(['user_id' => $rejected->id, 'full_name' => 'R', 'phone' => '0917', 'address' => 'x', 'status' => 'Rejected', 'created_at' => now(), 'updated_at' => now()]);

        $this->post(route('login.submit'), ['email' => $pending->email, 'password' => 'password123'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'pending verification'));
        $this->post(route('login.submit'), ['email' => $rejected->email, 'password' => 'password123'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'not approved'));
        $this->assertNull(session('user'));
    }

    public function test_S01b_rejected_seller_can_apply_again(): void
    {
        $rejected = $this->makeUser('seller', ['email' => 'again@seller.test']);
        DB::table('seller_applications')->insert(['user_id' => $rejected->id, 'full_name' => 'R', 'phone' => '0917', 'address' => 'x', 'status' => 'Rejected', 'created_at' => now(), 'updated_at' => now()]);

        $this->post(route('seller.register.submit'), [
            'last_name' => 'Santos', 'first_name' => 'Ana', 'sex' => 'Female', 'birthdate' => '1999-01-01',
            'email' => 'again@seller.test', 'password' => 'secret123', 'password_confirmation' => 'secret123',
            'phone' => '09170000001', 'province' => 'Laguna', 'city_municipality' => 'Santa Cruz', 'barangay' => 'Poblacion',
            'street_address' => '1 Rizal St', 'business_name' => 'Ana Shoes', 'business_category' => 'shoes', 'terms' => '1',
            'national_id' => $this->png(), 'business_permit' => $this->pdf(),
        ])->assertRedirect(route('otp.show'));

        $this->post(route('otp.verify'), ['otp_code' => session('otp_code')])->assertRedirect(route('login'));

        // Same account, a new application waiting for review, and the new password works.
        $this->assertSame(1, \App\Models\User::where('email', 'again@seller.test')->count());
        $latest = DB::table('seller_applications')->where('user_id', $rejected->id)->orderByDesc('id')->first();
        $this->assertSame('Pending Verification', $latest->status);
        $this->assertSame('Ana Shoes', $latest->business_name);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('secret123', $rejected->fresh()->password));

        $this->post(route('login.submit'), ['email' => 'again@seller.test', 'password' => 'secret123'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'pending verification'));
    }

    public function test_S01c_an_approved_or_pending_account_still_cannot_register_twice(): void
    {
        $this->post(route('seller.register.submit'), [
            'last_name' => 'Santos', 'first_name' => 'Ana', 'sex' => 'Female', 'birthdate' => '1999-01-01',
            'email' => $this->seller->email, 'password' => 'secret123', 'password_confirmation' => 'secret123',
            'phone' => '09170000003', 'province' => 'Laguna', 'city_municipality' => 'Santa Cruz', 'barangay' => 'Poblacion',
            'street_address' => '1 Rizal St', 'business_name' => 'Another Shop', 'business_category' => 'shoes', 'terms' => '1',
            'national_id' => $this->png(), 'business_permit' => $this->pdf(),
        ])->assertSessionHas('error', 'Email is already registered.');
    }

    // ---------------- Dashboard ----------------

    public function test_S02_dashboard_counts_work_low_stock_and_cod_money(): void
    {
        $buyer = $this->buyerAt();
        $this->makeProduct($this->seller, ['stock' => 2]);
        $this->makeOrder($buyer, $this->shoes, 'Pending');
        $this->makeOrder($buyer, $this->shoes, 'Preparing');
        $this->makeOrder($buyer, $this->shoes, 'Delivered', ['cod_collected_at' => now()]);
        $this->makeOrder($buyer, $this->shoes, 'Delivered', ['cod_collected_at' => now(), 'cod_remitted_at' => now()]);

        $this->asSeller()->get(route('seller.dashboard'))->assertOk()
            ->assertViewHas('newOrders', 1)
            ->assertViewHas('toDropOff', 1)
            ->assertViewHas('lowStockCount', 1)
            ->assertViewHas('codPayout', fn ($c) => (float) $c->pending === 500.0 && (float) $c->released === 500.0)
            ->assertViewHas('salesTrend', fn ($t) => count($t) === 7);
    }

    // ---------------- Products ----------------

    public function test_S03_add_product_rules(): void
    {
        $add = fn (array $over) => $this->asSeller()->post(route('seller.products.store'), $this->productForm($over));

        $add(['category' => 'electronics'])->assertSessionHas('error', fn ($m) => str_contains($m, 'You can only sell Shoes'));
        $add(['name' => 'Canvas Sneakers'])->assertSessionHas('error', 'You already have a product with this name.');
        $add(['photos' => []])->assertSessionHas('error', 'Please add at least one product photo.');
        $add(['price' => 100, 'variations' => [['type' => 'Size', 'value' => 'Kids', 'price_adjustment' => -150, 'stock' => 3]]])
            ->assertSessionHas('error', fn ($m) => str_contains($m, '₱0 or less'));

        $add(['name' => 'Trail Runner', 'variations' => [['type' => 'Size', 'value' => '9', 'price_adjustment' => 0, 'stock' => 3]]])
            ->assertRedirect(route('seller.dashboard'));
        $created = Product::where('name', 'Trail Runner')->first();
        $this->assertSame('Shoes', $created->category);
        $this->assertSame(1, $created->variations()->count());
        $this->assertNotNull($created->image);
    }

    public function test_S04_edit_product_keeps_a_photo_and_options_above_zero(): void
    {
        $this->asSeller()->post(route('seller.products.store'), $this->productForm(['name' => 'Edit Me']));
        $product = Product::where('name', 'Edit Me')->first();
        $photo = $product->images()->first();
        $base = ['name' => 'Edit Me', 'category' => 'shoes', 'price' => 1200, 'stock' => 5, 'description' => 'x'];

        $this->put(route('seller.products.update', $product->id), $base + ['remove_photos' => [$photo->id]])
            ->assertSessionHas('error', 'Please keep at least one product photo.');

        ProductVariation::create(['product_id' => $product->id, 'variation_type' => 'Size', 'variation_value' => 'Small', 'price_adjustment' => -1000, 'stock' => 1]);
        $this->put(route('seller.products.update', $product->id), array_merge($base, ['price' => 900]))
            ->assertSessionHas('error', fn ($m) => str_contains($m, '₱0 or less'));

        $this->put(route('seller.products.update', $product->id), array_merge($base, ['price' => 1500]))
            ->assertRedirect(route('seller.dashboard'));
        $this->assertEquals(1500, $product->fresh()->price);
    }

    public function test_S05_options_no_duplicates_bulk_restock_and_photos_go_with_a_deleted_option(): void
    {
        $red = $this->addVariation($this->shoes, 'Red', 1);
        $blue = $this->addVariation($this->shoes, 'Blue', 1);

        $this->asSeller()->post(route('seller.products.variations.store', $this->shoes->id), ['variation_type' => 'color', 'variation_value' => 'Red', 'stock' => 3])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'already has that option'));

        $this->put(route('seller.products.variations.update', $this->shoes->id), ['variations' => [
            $red->id => ['stock' => 10, 'price_adjustment' => 0],
            $blue->id => ['stock' => 7, 'price_adjustment' => 50],
        ]])->assertSessionHas('success', 'Saved changes to 2 options.');

        // The product has its own photo; Green gets one of its own.
        app(\App\Services\ProductService::class)->storeFiles($this->shoes, null, [$this->png('all.png')]);
        $this->post(route('seller.products.variations.store', $this->shoes->id), ['variation_type' => 'Color', 'variation_value' => 'Green', 'stock' => 3, 'image' => $this->png('green.png')]);
        $green = ProductVariation::where('variation_value', 'Green')->first();
        $path = ProductImage::where('product_variation_id', $green->id)->value('path');
        Storage::disk('public')->assertExists($path);

        $this->delete(route('seller.products.variations.delete', [$this->shoes->id, $green->id]));
        $this->assertNull(ProductVariation::find($green->id));
        Storage::disk('public')->assertMissing($path);
        $this->assertNotSame($path, $this->shoes->fresh()->image);
    }

    public function test_S05b_deleting_the_only_option_with_photos_does_not_leave_its_photo_as_the_cover(): void
    {
        // All of this product's photos belong to one option.
        $this->asSeller()->post(route('seller.products.variations.store', $this->shoes->id), ['variation_type' => 'Color', 'variation_value' => 'Green', 'stock' => 3, 'image' => $this->png('green.png')]);
        $green = ProductVariation::where('variation_value', 'Green')->first();
        $path = ProductImage::where('product_variation_id', $green->id)->value('path');
        $this->assertSame($path, $this->shoes->fresh()->image);

        $this->delete(route('seller.products.variations.delete', [$this->shoes->id, $green->id]));

        $this->assertNotSame($path, $this->shoes->fresh()->image, 'The cover is still the photo of an option that was deleted.');
    }

    public function test_S06_product_with_orders_cannot_be_deleted_but_can_be_archived(): void
    {
        $this->makeOrder($this->buyerAt(), $this->shoes, 'Delivered');

        $this->asSeller()->delete(route('seller.products.delete', $this->shoes->id))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Archive it instead'));
        $this->assertNotNull(Product::find($this->shoes->id));

        $this->post(route('seller.products.archive', $this->shoes->id))->assertSessionHas('success');
        $this->assertFalse(Product::onSale()->whereKey($this->shoes->id)->exists());
    }

    // ---------------- Orders ----------------

    public function test_S07_orders_tabs_search_and_pages(): void
    {
        $buyer = $this->buyerAt();
        $ids = [];
        foreach (range(1, 16) as $i) {
            $ids[] = $this->makeOrder($buyer, $this->shoes, 'Pending');
        }
        $this->makeOrder($buyer, $this->shoes, 'Delivered');

        $this->asSeller()->get(route('seller.orders'))->assertOk()
            ->assertViewHas('tabCounts', fn ($c) => $c['all'] === 17 && $c['to-process'] === 16 && $c['completed'] === 1)
            ->assertViewHas('orders', fn ($page) => $page->count() === 15 && $page->total() === 17);

        $this->get(route('seller.orders', ['q' => '#' . $ids[3]]))
            ->assertViewHas('orders', fn ($page) => $page->total() === 1);
        $this->get(route('seller.orders', ['q' => 'Canvas']))
            ->assertViewHas('orders', fn ($page) => $page->total() === 17);
    }

    public function test_S08_order_steps_cannot_be_skipped_and_cancel_returns_stock_and_voucher(): void
    {
        $buyer = $this->buyerAt();
        $voucher = Voucher::create(['seller_id' => $this->seller->id, 'code' => 'KICKS50', 'discount_type' => 'fixed', 'discount_value' => 50, 'min_order_amount' => 0, 'used_count' => 0, 'is_active' => true]);
        $this->placeOrder($buyer, [$this->shoes->id . ':0' => 2], self::LAGUNA_ADDRESS, 'Cash on Delivery', ['session' => ['applied_voucher' => 'KICKS50']]);
        $orderId = $this->lastOrder($buyer)->id;
        $this->assertSame(8, $this->stockOf($this->shoes));
        $this->assertSame(1, (int) $voucher->fresh()->used_count);

        $this->flushSession();
        $status = fn (array $data) => $this->actingAsUser($this->seller)->post(route('seller.order.status', $orderId), $data);

        $status(['status' => 'Dropped Off'])->assertSessionHas('error', 'Invalid seller order status.');
        $status(['status' => 'Preparing'])->assertSessionHas('error', 'Accept the order first.');
        $status(['status' => 'Cancelled'])->assertSessionHas('error', 'Please provide a reason for cancelling this order.');
        $status(['status' => 'Cancelled', 'cancellation_reason' => 'Out of size'])->assertSessionHas('success');

        $this->assertSame(10, $this->stockOf($this->shoes));
        $this->assertSame(0, (int) $voucher->fresh()->used_count);
        $this->assertTrue($this->notifiedWith($buyer, 'Order Cancelled by Seller'));
    }

    public function test_S09_shipping_label_and_waybill(): void
    {
        $orderId = $this->makeOrder($this->buyerAt(), $this->shoes, 'Preparing', ['origin_center_id' => $this->laguna->id]);

        $this->asSeller()->get(route('seller.order.label', $orderId))->assertOk()
            ->assertSee(\App\Support\Waybill::number($orderId))
            ->assertSee($this->laguna->name);
        $this->get(route('seller.order.waybill', $orderId))->assertOk();

        // Another seller can't print it.
        $this->flushSession();
        $this->actingAsUser($this->makeSeller())->get(route('seller.order.label', $orderId))->assertNotFound();
    }

    public function test_S10_order_details_show_the_buyers_cancel_history(): void
    {
        $buyer = $this->buyerAt();
        $this->makeOrder($buyer, $this->shoes, 'Cancelled', ['cancelled_by' => 'buyer', 'cancelled_at' => now()]);
        $this->makeOrder($buyer, $this->shoes, 'Returned to Seller', ['buyer_refused_at' => now()]);
        $orderId = $this->makeOrder($buyer, $this->shoes, 'Pending');

        $this->asSeller()->get(route('seller.order.details', $orderId))->assertOk()
            ->assertViewHas('buyerHistory', fn ($h) => $h['cancelled'] === 1 && $h['refused'] === 1 && $h['orders'] === 3);
    }

    public function test_S11_returned_parcel_is_restocked_once(): void
    {
        $orderId = $this->makeOrder($this->buyerAt(), $this->shoes, 'Returned to Seller');

        $this->asSeller()->post(route('seller.order.restock', $orderId))->assertSessionHas('success');
        $this->post(route('seller.order.restock', $orderId))->assertSessionHas('error');
        $this->assertSame(11, $this->stockOf($this->shoes));
    }

    public function test_S12_return_and_refund_requests_step_by_step(): void
    {
        $buyer = $this->buyerAt();
        $make = function (string $type) use ($buyer) {
            $orderId = $this->makeOrder($buyer, $this->shoes, 'Delivered', ['buyer_received_at' => now()]);
            $itemId = DB::table('order_items')->where('order_id', $orderId)->value('id');

            return DB::table('return_refund_requests')->insertGetId([
                'order_id' => $orderId, 'order_item_id' => $itemId, 'buyer_id' => $buyer->id, 'seller_id' => $this->seller->id,
                'request_type' => $type, 'reason' => 'Damaged', 'status' => 'pending', 'refund_amount' => 500,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        };
        $statusOf = fn (int $id) => DB::table('return_refund_requests')->where('id', $id)->value('status');

        // A return: approved → the item comes back through the Sorting Center → restock once.
        $return = $make('Return');
        $this->asSeller()->post(route('seller.return-refund.restock', $return))->assertSessionHas('error');
        $this->post(route('seller.return-refund.approve', $return))->assertSessionHas('success');
        $this->assertSame('approved', $statusOf($return));
        $this->assertTrue($this->notifiedWith($buyer, 'Return Approved'));

        $staff = $this->staffAt($this->laguna);
        $this->flushSession();
        $this->actingAsUser($staff)->post(route('logistics.returns.receive', $return))->assertSessionHas('success');
        $this->post(route('logistics.returns.hand-to-seller', $return))->assertSessionHas('success');
        $this->assertSame('refund_pending', $statusOf($return));

        $this->flushSession();
        $this->asSeller()->get(route('seller.orders', ['tab' => 'returns']))->assertOk()->assertSee('Add back to stock');
        $this->post(route('seller.return-refund.restock', $return))->assertSessionHas('success');
        $this->assertSame(11, $this->stockOf($this->shoes));

        // A refund: approved → straight to BoomBuy (the seller no longer sends money).
        $refund = $make('Refund');
        $this->post(route('seller.return-refund.approve', $refund))->assertSessionHas('success');
        $this->assertSame('refund_pending', $statusOf($refund));

        // Rejecting needs a reason the buyer sees.
        $rejected = $make('Refund');
        $this->post(route('seller.return-refund.reject', $rejected), ['seller_note' => ''])->assertSessionHas('error');
        $this->post(route('seller.return-refund.reject', $rejected), ['seller_note' => 'Used item'])->assertSessionHas('success');
        $this->assertTrue($this->notifiedWith($buyer, 'Refund Request Rejected'));
    }

    // ---------------- Shop ----------------

    public function test_S13_voucher_form_rules(): void
    {
        $create = fn (array $over) => $this->asSeller()->post(route('seller.vouchers.store'), array_merge([
            'code' => 'SALE' . Str::random(3), 'discount_type' => 'percentage', 'discount_value' => 10,
        ], $over));

        $create(['discount_value' => 150])->assertSessionHasErrors('discount_value');
        $create(['code' => 'DUPE'])->assertSessionHas('success');
        $create(['code' => 'DUPE'])->assertSessionHasErrors('code');

        $voucher = Voucher::where('code', 'DUPE')->first();
        $this->post(route('seller.vouchers.toggle', $voucher->id));
        $this->assertFalse($voucher->fresh()->is_active);

        // A date that has already passed makes a voucher nobody can use.
        $create(['code' => 'OLDDATE', 'expires_at' => now()->subWeek()->toDateString()])->assertSessionHasErrors('expires_at');
    }

    public function test_S13b_deleting_a_used_voucher_does_not_change_past_sales(): void
    {
        $buyer = $this->buyerAt();
        $voucher = Voucher::create(['seller_id' => $this->seller->id, 'code' => 'LESS100', 'discount_type' => 'fixed', 'discount_value' => 100, 'min_order_amount' => 0, 'used_count' => 1, 'is_active' => true]);
        $this->makeOrder($buyer, $this->shoes, 'Delivered', ['voucher_code' => 'LESS100', 'discount_amount' => 100, 'total_amount' => 400]);

        $before = $this->asSeller()->get(route('seller.reports'))->viewData('totalSales');
        $this->assertEquals(400, $before);

        $this->delete(route('seller.vouchers.delete', $voucher->id));

        $after = $this->get(route('seller.reports'))->viewData('totalSales');
        $this->assertEquals($before, $after, 'Deleting a used voucher changed past sales.');
    }

    public function test_S14_seller_reply_shows_on_the_product_page(): void
    {
        $buyer = $this->buyerAt();
        $orderId = $this->makeOrder($buyer, $this->shoes, 'Delivered');
        $reviewId = DB::table('product_reviews')->insertGetId(['buyer_id' => $buyer->id, 'order_id' => $orderId, 'product_id' => $this->shoes->id, 'rating' => 4, 'review' => 'Nice', 'created_at' => now(), 'updated_at' => now()]);

        $this->asSeller()->post(route('seller.reviews.reply', $reviewId), ['seller_reply' => 'Salamat po!'])->assertSessionHas('success');

        $this->flushSession();
        $this->get(route('product.details', Str::slug($this->shoes->name) . '-' . $this->shoes->id))->assertSee('Salamat po!');

        // An old name-only link still opens the product.
        $this->get(route('product.details', Str::slug($this->shoes->name)))->assertOk()->assertSee('Salamat po!');
    }

    public function test_S14b_reviews_are_shown_twenty_a_page(): void
    {
        $buyer = $this->buyerAt();
        foreach (range(1, 23) as $i) {
            $orderId = $this->makeOrder($buyer, $this->shoes, 'Delivered');
            DB::table('product_reviews')->insert(['buyer_id' => $buyer->id, 'order_id' => $orderId, 'product_id' => $this->shoes->id, 'rating' => 5, 'review' => 'Review ' . $i, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->asSeller()->get(route('seller.reviews'))->assertOk()
            ->assertViewHas('reviews', fn ($page) => $page->count() === 20 && $page->total() === 23)
            ->assertSee('Page 1 of 2');
    }

    public function test_S15_reports_use_the_admin_commission_rate(): void
    {
        \App\Models\PlatformSetting::set('commission_rate', '5');
        $this->makeOrder($this->buyerAt(), $this->shoes, 'Delivered');
        $this->makeOrder($this->buyerAt(), $this->shoes, 'Delivered');

        $this->asSeller()->get(route('seller.reports'))->assertOk()
            ->assertViewHas('totalSales', 1000.0)
            ->assertViewHas('commissionOwed', 50.0)
            ->assertViewHas('netEarnings', 950.0)
            ->assertViewHas('deliveredOrders', 2);
    }

    public function test_S16_shop_name_is_unique_and_auto_reply_can_be_set(): void
    {
        $this->makeSeller('shoes', 'Taken Name');

        $this->asSeller()->post(route('seller.shop.update'), ['business_name' => 'taken name'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Another shop already uses that name'));
        $this->post(route('seller.shop.update'), ['business_name' => 'Kicks Laguna', 'shop_description' => 'Shoes!'])->assertSessionHas('success');

        $this->post(route('seller.autoreply.update'), ['auto_reply_enabled' => '1', 'auto_reply_message' => 'Hi, online kami 9-5.']);
        $settings = \App\Support\ChatAutomation::settingsFor($this->seller->id);
        $this->assertTrue($settings['enabled']);
        $this->assertSame('Hi, online kami 9-5.', $settings['message']);
    }

    public function test_S16b_changing_the_shop_address_moves_the_shop_to_that_town(): void
    {
        $this->asSeller()->post(route('seller.profile.update'), [
            'name' => $this->seller->name,
            'phone' => $this->seller->phone,
            'address' => self::CEBU_ADDRESS,
        ])->assertSessionHas('success');

        $this->assertSame('Cebu', $this->seller->fresh()->province, 'The seller moved to Cebu but is still routed from the old province.');
    }

    public function test_S16c_registration_rejects_a_shop_name_already_in_use(): void
    {
        $this->post(route('seller.register.submit'), [
            'last_name' => 'Santos', 'first_name' => 'Ana', 'sex' => 'Female', 'birthdate' => '1999-01-01',
            'email' => 'ana@new.test', 'password' => 'secret123', 'password_confirmation' => 'secret123',
            'phone' => '09170000002', 'province' => 'Laguna', 'city_municipality' => 'Santa Cruz', 'barangay' => 'Poblacion',
            'street_address' => '1 Rizal St', 'business_name' => 'Santa Cruz Kicks', 'business_category' => 'shoes', 'terms' => '1',
            'national_id' => $this->png(), 'business_permit' => $this->pdf(),
        ])->assertSessionHas('error');
    }

    public function test_S17_shop_page_closes_when_the_seller_is_suspended(): void
    {
        $this->get(route('shop.seller', $this->seller->id))->assertOk()->assertSee('Canvas Sneakers');

        DB::table('users')->where('id', $this->seller->id)->update(['status' => 'Suspended']);

        $this->get(route('shop.seller', $this->seller->id))->assertNotFound();
        $this->get(route('products'))->assertDontSee('Canvas Sneakers');
    }

    public function test_S18_notifications_open_the_order_or_the_returns_tab(): void
    {
        $orderId = $this->makeOrder($this->buyerAt(), $this->shoes, 'Pending');
        $orderNote = createNotification($this->seller->id, 'New Order Received', 'x', 'order', $orderId);
        $returnId = DB::table('return_refund_requests')->insertGetId([
            'order_id' => $orderId, 'order_item_id' => DB::table('order_items')->where('order_id', $orderId)->value('id'),
            'buyer_id' => 1, 'seller_id' => $this->seller->id, 'request_type' => 'Return', 'reason' => 'x', 'status' => 'pending',
            'refund_amount' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $returnNote = createNotification($this->seller->id, 'New Return / Refund Request', 'x', 'return_refund', $returnId);

        $this->asSeller()->get(route('notifications.open', $orderNote->id))->assertRedirect(route('seller.order.details', $orderId));
        $this->get(route('notifications.open', $returnNote->id))->assertRedirect(route('seller.orders', ['tab' => 'returns']));
    }
}
