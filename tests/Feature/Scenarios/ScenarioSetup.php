<?php

namespace Tests\Feature\Scenarios;

use App\Models\BuyerAddress;
use App\Models\Product;
use App\Models\RiderArea;
use App\Models\SortingCenter;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;

/**
 * Shared world for the scenario tests (one test per row of the BoomBuy Test
 * Plan, named after its id: test_B07_…). Places used:
 *
 *   Santa Cruz, Laguna  — the "home" town: a center, its staff and rider
 *   Quezon City, NCR    — another province on Luzon
 *   Cebu City, Cebu     — another island group
 */
trait ScenarioSetup
{
    use BuildsBoomBuyData;

    protected const LAGUNA_ADDRESS = '15 P. Guevarra St, Poblacion, Santa Cruz, Laguna';
    protected const QC_ADDRESS = '8 Aurora Blvd, Cubao, Quezon City, Metro Manila (NCR)';
    protected const CEBU_ADDRESS = '22 Osmeña Blvd, Cebu City, Cebu';

    /** Same as BuildsBoomBuyData, but with a lowercase email (the forms lowercase what is typed; SQLite compares case-sensitively). */
    protected function makeUser(string $role = 'buyer', array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role) . ' ' . \Illuminate\Support\Str::random(4),
            'email' => strtolower(\Illuminate\Support\Str::random(8)) . "@{$role}.test",
            'password' => bcrypt('password123'),
            'role' => $role,
            'phone' => '09' . random_int(100000000, 999999999),
            'address' => self::QC_ADDRESS,
            'is_verified' => true,
        ], $overrides));
    }

    protected function center(string $province, string $city): SortingCenter
    {
        return SortingCenter::create([
            'name' => 'BoomBuy Sorting Center – ' . $province,
            'region' => \App\Support\PhLocations::region($province),
            'province' => $province,
            'city_municipality' => $city,
            'address' => 'Poblacion, ' . $city,
            'is_active' => true,
        ]);
    }

    protected function staffAt(?SortingCenter $center): User
    {
        $staff = $this->makeLogistics();
        DB::table('users')->where('id', $staff->id)->update(['sorting_center_id' => $center?->id]);

        return $staff->fresh();
    }

    protected function riderFor(SortingCenter $center, ?string $city = null): User
    {
        $rider = $this->makeRider();
        RiderArea::create(['rider_id' => $rider->id, 'province' => $center->province, 'city_municipality' => $city ?? $center->city_municipality]);

        return $rider;
    }

    protected function sellerIn(string $province, string $city, string $category = 'shoes', ?string $shop = null): User
    {
        $seller = $this->makeSeller($category, $shop ?? 'Shop ' . \Illuminate\Support\Str::random(5));
        $seller->forceFill(['province' => $province, 'city_municipality' => $city, 'address' => 'Poblacion, ' . $city . ', ' . $province])->save();

        return $seller->fresh();
    }

    /** A buyer whose profile is complete enough to check out, living at $address. */
    protected function buyerAt(string $address = self::LAGUNA_ADDRESS): User
    {
        $buyer = $this->makeUser('buyer', ['phone' => '0917' . random_int(1000000, 9999999), 'address' => $address]);
        BuyerAddress::create(['user_id' => $buyer->id, 'label' => 'Home', 'phone' => $buyer->phone, 'address' => $address, 'is_default' => true]);

        return $buyer;
    }

    protected function placeOrder(User $buyer, array $cart, string $address = self::LAGUNA_ADDRESS, string $payment = 'Cash on Delivery', array $extra = [])
    {
        return $this->actingAsUser($buyer, array_merge(['cart' => $cart], $extra['session'] ?? []))
            ->post(route('checkout.place'), array_merge([
                'address' => $address,
                'phone' => '09171234567',
                'payment' => $payment,
            ], $extra['form'] ?? []));
    }

    protected function lastOrder(User $buyer): ?object
    {
        return DB::table('orders')->where('buyer_id', $buyer->id)->orderByDesc('id')->first();
    }

    protected function notifiedWith(User $user, string $title): bool
    {
        return DB::table('notifications')->where('user_id', $user->id)->where('title', $title)->exists();
    }

    protected function stockOf(Product $product): int
    {
        return (int) DB::table('products')->where('id', $product->id)->value('stock');
    }

    protected function setOrder(int $orderId, array $columns): void
    {
        DB::table('orders')->where('id', $orderId)->update($columns);
    }

    /** A real tiny PNG (GD isn't available to draw one). */
    protected function png(string $name = 'photo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
        );
    }

    protected function pdf(string $name = 'doc.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 100, 'application/pdf');
    }
}
