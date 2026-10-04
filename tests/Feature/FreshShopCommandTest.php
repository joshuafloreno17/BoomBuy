<?php

namespace Tests\Feature;

use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class FreshShopCommandTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    public function test_it_empties_products_and_orders_but_keeps_accounts_and_chats(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('products/a.png', 'x');

        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['image' => 'products/a.png']);
        $this->addVariation($product);
        $order = $this->makeOrder($buyer, $product, 'Delivered');
        DB::table('wishlists')->insert(['user_id' => $buyer->id, 'product_id' => $product->id, 'created_at' => now(), 'updated_at' => now()]);
        createNotification($buyer->id, 'Order Delivered', 'Delivered', 'order', $order);
        createNotification($buyer->id, 'Welcome', 'Hi', 'account_status', null);
        Message::create(['sender_id' => $buyer->id, 'recipient_id' => $seller->id, 'message' => 'Hello']);
        Message::create(['sender_id' => $seller->id, 'recipient_id' => $buyer->id, 'message' => 'Order #1 delivered', 'kind' => Message::ORDER_UPDATE, 'order_id' => $order]);

        // Saying "no" changes nothing.
        $this->artisan('boombuy:fresh-shop')->expectsConfirmation('Delete everything listed above? This empties the shop.', 'no')->assertSuccessful();
        $this->assertSame(1, DB::table('products')->count());

        $this->artisan('boombuy:fresh-shop', ['--force' => true])->assertSuccessful();

        foreach (['products', 'product_variations', 'orders', 'order_items', 'wishlists'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), $table);
        }
        Storage::disk('public')->assertMissing('products/a.png');
        $this->assertCount(1, Storage::disk('local')->files('backups'));

        // Accounts, normal chats and non-order notifications stay.
        $this->assertDatabaseHas('users', ['id' => $buyer->id]);
        $this->assertDatabaseHas('users', ['id' => $seller->id]);
        $this->assertSame(1, Message::count());
        $this->assertDatabaseHas('notifications', ['title' => 'Welcome']);
        $this->assertDatabaseMissing('notifications', ['title' => 'Order Delivered']);
    }
}
