<?php

namespace Tests\Feature\Concerns;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Small builders for feature tests. BoomBuy logs people in through
 * session('user') (not Laravel's auth guard), so "acting as" someone
 * means putting their user array in the session.
 */
trait BuildsBoomBuyData
{
    protected function makeUser(string $role = 'buyer', array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role) . ' ' . Str::random(4),
            'email' => Str::random(8) . "@{$role}.test",
            'password' => bcrypt('password123'),
            'role' => $role,
            'phone' => '09' . random_int(100000000, 999999999),
            'address' => '1 Test St, Quezon City',
            'is_verified' => true,
        ], $overrides));
    }

    /** An approved seller registered for one category. */
    protected function makeSeller(string $category = 'shoes', string $shop = 'Test Shop'): User
    {
        $seller = $this->makeUser('seller');

        DB::table('seller_applications')->insert([
            'user_id' => $seller->id,
            'full_name' => $seller->name,
            'business_name' => $shop,
            'phone' => $seller->phone,
            'address' => $seller->address,
            'business_category' => $category,
            'status' => 'Approved',
            'reviewed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $seller;
    }

    protected function makeRider(string $status = 'Approved'): User
    {
        $rider = $this->makeUser('rider');

        DB::table('rider_applications')->insert([
            'user_id' => $rider->id,
            'full_name' => $rider->name,
            'phone' => $rider->phone,
            'address' => $rider->address,
            'vehicle_type' => 'Motorcycle',
            'vehicle_model' => 'Honda Click',
            'plate_number' => 'ABC ' . random_int(1000, 9999),
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $rider;
    }

    protected function makeLogistics(): User
    {
        $user = $this->makeUser('logistics');

        DB::table('logistics_applications')->insert([
            'user_id' => $user->id,
            'full_name' => $user->name,
            'business_name' => 'Metro Sorting Center',
            'status' => 'Approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    /** An order with one line, already at the given status. */
    protected function makeOrder(User $buyer, Product $product, string $status = 'Pending', array $overrides = []): int
    {
        $orderId = DB::table('orders')->insertGetId(array_merge([
            'buyer_id' => $buyer->id,
            'total_amount' => $product->price,
            'status' => $status,
            'shipping_name' => $buyer->name,
            'shipping_phone' => $buyer->phone,
            'shipping_address' => '1 Rizal St, Cebu City',
            'payment_method' => 'Cash on Delivery',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => $product->id,
            'seller_id' => $product->seller_id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $orderId;
    }

    protected function actingAsAdmin(): static
    {
        User::firstOrCreate(
            ['email' => 'admin@boombuy.com'],
            ['name' => 'Admin', 'password' => bcrypt(Str::random(32)), 'role' => 'admin']
        );

        return $this->withSession(['admin_logged_in' => true]);
    }

    protected function orderStatus(int $orderId): ?string
    {
        return DB::table('orders')->where('id', $orderId)->value('status');
    }

    protected function makeProduct(User $seller, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'seller_id' => $seller->id,
            'name' => 'Product ' . Str::random(5),
            'category' => 'Shoes',
            'price' => 500,
            'stock' => 10,
            'description' => 'A test product.',
            'image' => null,
        ], $overrides));
    }

    protected function addVariation(Product $product, string $value = 'Red', int $stock = 5): ProductVariation
    {
        return ProductVariation::create([
            'product_id' => $product->id,
            'variation_type' => 'Color',
            'variation_value' => $value,
            'price_adjustment' => 0,
            'stock' => $stock,
        ]);
    }

    /** The rider's proof-of-delivery photo: a real 1x1 PNG (no GD here, so fake()->image() can't draw one). */
    protected function deliveryPhoto(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'proof.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
        );
    }

    /** Log in the way BoomBuy does: the user's array in session('user'). */
    protected function actingAsUser(User $user, array $extraSession = []): static
    {
        return $this->withSession(array_merge([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'profile_photo' => null,
            ],
        ], $extraSession));
    }

    protected function ajaxHeaders(): array
    {
        return ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'];
    }
}
