<?php

namespace Tests\Feature\Concerns;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
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
