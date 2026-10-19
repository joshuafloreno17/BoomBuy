<?php

namespace Tests\Feature\Scenarios;

use App\Models\BuyerAddress;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * The Test Data sheet shared by the classmate's four Module workbooks:
 * Seller 1 (Maria Clara Handicrafts, Quezon City), Seller 2 (RV Gadget Hub,
 * Cebu City), Seller 3 (pending), Buyer 1 and 2, and products P-01 to P-07.
 * Everything is made on first use, so each test only builds what it needs.
 */
trait ModuleSheetWorld
{
    use ScenarioSetup;

    protected const ADDR_1 = '45 Kalayaan Ave., Brgy. Central, Quezon City, Metro Manila (NCR)';
    protected const ADDR_2 = '12F Pacific Tower, 6750 Ayala Ave., Brgy. San Lorenzo, Makati City, Metro Manila (NCR)';
    protected const ADDR_3 = '78 Osmeña Blvd., Brgy. Capitol Site, Cebu City, Cebu';

    private array $world = [];

    protected function seller1(): User
    {
        return $this->world['seller1'] ??= $this->sheetSeller(
            'mariaclara.santos@example.com', 'Tindahan@2026', 'Maria Clara Santos', 'Maria Clara Handicrafts',
            'home-living', 'Metro Manila (NCR)', 'Quezon City', 'Blk 12 Lot 8, Sampaguita St., Commonwealth, Quezon City, Metro Manila (NCR)', '09189876543'
        );
    }

    protected function seller2(): User
    {
        return $this->world['seller2'] ??= $this->sheetSeller(
            'roberto.villanueva@example.com', 'Gadget#Hub26', 'Roberto Villanueva', 'RV Gadget Hub',
            'electronics', 'Cebu', 'Cebu City', '2F, 45 Gorordo Ave., Lahug, Cebu City, Cebu', '09194567890'
        );
    }

    /** Seller 3: registered and verified, still Pending Approval. */
    protected function seller3(): User
    {
        return $this->world['seller3'] ??= $this->sheetSeller(
            'seller.test01@example.com', 'Tindahan@2026', 'Lola Test', 'Tindahan ni Lola',
            'home-living', 'Metro Manila (NCR)', 'Quezon City', '5 Mabini St., Commonwealth, Quezon City, Metro Manila (NCR)', '09170000011', 'Pending Verification'
        );
    }

    protected function buyer1(): User
    {
        return $this->world['buyer1'] ??= $this->sheetBuyer('juanmiguel.delacruz@example.com', 'Mamimili@2026', 'Juan Miguel Dela Cruz', '09171234567', self::ADDR_1);
    }

    protected function buyer2(): User
    {
        return $this->world['buyer2'] ??= $this->sheetBuyer('anamarie.reyes@example.com', 'Suki#2026Ana', 'Ana Marie Reyes', '09173456789', self::ADDR_3);
    }

    /** P-01 Abaca Woven Tote Bag: Color (Natural, Black) × Size (S ₱199, M ₱249, L ₱299). */
    protected function p01(): Product
    {
        if (isset($this->world['p01'])) {
            return $this->world['p01'];
        }

        $product = $this->makeProduct($this->seller1(), [
            'name' => 'Abaca Woven Tote Bag', 'category' => 'Home & Living', 'price' => 199, 'stock' => 0, 'weight_kg' => 0.4,
            'description' => 'Handwoven abaca tote bag from Bulacan artisans.',
        ]);

        $stock = ['Natural' => ['S' => 10, 'M' => 8, 'L' => 5], 'Black' => ['S' => 10, 'M' => 0, 'L' => 3]];
        $adjust = ['S' => 0, 'M' => 50, 'L' => 100];

        foreach ($stock as $color => $sizes) {
            foreach ($sizes as $size => $qty) {
                $this->world['p01:' . $color . '-' . $size] = ProductVariation::create([
                    'product_id' => $product->id,
                    'variation_type' => 'Color', 'variation_value' => $color,
                    'option2_type' => 'Size', 'option2_value' => $size,
                    'price_adjustment' => $adjust[$size], 'stock' => $qty,
                ]);
            }
        }

        return $this->world['p01'] = $product;
    }

    /** One of P-01's six rows, e.g. variant('Natural-M'). */
    protected function variant(string $row): ProductVariation
    {
        $this->p01();

        return $this->world['p01:' . $row]->fresh();
    }

    protected function p02(): Product
    {
        return $this->world['p02'] ??= $this->makeProduct($this->seller1(), [
            'name' => 'Rattan Fruit Basket', 'category' => 'Home & Living', 'price' => 450, 'stock' => 20, 'weight_kg' => 0.8,
            'description' => 'Handwoven rattan fruit basket, 30 cm wide.',
        ]);
    }

    protected function p03(): Product
    {
        return $this->world['p03'] ??= $this->makeProduct($this->seller1(), [
            'name' => 'Capiz Shell Lamp', 'category' => 'Home & Living', 'price' => 1250, 'stock' => 1, 'weight_kg' => 1.5,
            'description' => 'Table lamp with a capiz shell shade.',
        ]);
    }

    protected function p04(): Product
    {
        return $this->world['p04'] ??= $this->makeProduct($this->seller1(), [
            'name' => 'Banig Placemat Set of 4', 'category' => 'Home & Living', 'price' => 350, 'stock' => 0, 'weight_kg' => 0.5,
            'description' => 'Four handwoven banig placemats.',
        ]);
    }

    protected function p05(): Product
    {
        return $this->world['p05'] ??= $this->makeProduct($this->seller2(), [
            'name' => 'USB-C Fast Charger 20W', 'category' => 'Electronics', 'price' => 399, 'stock' => 50, 'weight_kg' => 0.2,
            'description' => '20W USB-C power delivery wall charger.',
        ]);
    }

    protected function p06(): Product
    {
        if (isset($this->world['p06'])) {
            return $this->world['p06'];
        }

        $product = $this->makeProduct($this->seller2(), [
            'name' => 'Wireless Earbuds X2', 'category' => 'Electronics', 'price' => 1499, 'stock' => 0, 'weight_kg' => 0.3,
            'description' => 'True wireless earbuds with charging case.',
        ]);

        foreach (['Black', 'White'] as $color) {
            $this->addVariation($product, $color, 10);
        }

        return $this->world['p06'] = $product;
    }

    protected function p07(): Product
    {
        return $this->world['p07'] ??= $this->makeProduct($this->seller2(), [
            'name' => 'Power Bank 10000mAh', 'category' => 'Electronics', 'price' => 899, 'stock' => 5, 'weight_kg' => 0.4,
            'description' => '10000mAh power bank with two USB ports.',
        ]);
    }

    /**
     * The sheet's shipping rates (Test Data): first kg + each extra kg.
     * Same town/province (Within NCR) ₱50 + ₱15; other islands (Visayas ↔ NCR) ₱120 + ₱40.
     * No free-shipping minimum, so the fees always show.
     */
    protected function sheetRates(): void
    {
        foreach (['delivery_fee' => 50, 'delivery_fee_province' => 50, 'delivery_fee_island' => 120, 'delivery_fee_far' => 120,
            'delivery_fee_per_kg_local' => 15, 'delivery_fee_per_kg_province' => 15, 'delivery_fee_per_kg_island' => 40, 'delivery_fee_per_kg_far' => 40,
            \App\Support\DeliveryFee::FREE_SHIPPING_KEY => 1000000] as $key => $value) {
            \App\Models\PlatformSetting::set($key, (string) $value);
        }
    }

    /* ---------------- Logistics & riders (Logistics / Rider Modules) ---------------- */

    /** The Quezon City Sorting Center that Logistics 1 (Bilis Hatid Express) runs. */
    protected function qcCenter(): \App\Models\SortingCenter
    {
        return $this->world['qcCenter'] ??= $this->center('Metro Manila (NCR)', 'Quezon City');
    }

    protected function davaoCenter(): \App\Models\SortingCenter
    {
        return $this->world['davaoCenter'] ??= $this->center('Davao del Sur', 'Davao City');
    }

    protected function logistics1(): User
    {
        return $this->world['logistics1'] ??= $this->sheetLogistics('operations@bilishatid.example.com', 'Hatid@2026', 'Carlos Antonio Mendoza', 'Bilis Hatid Express Inc.', $this->qcCenter());
    }

    protected function logistics2(): User
    {
        return $this->world['logistics2'] ??= $this->sheetLogistics('admin@agilacargo.example.com', 'Agila#Cargo26', 'Liza Mae Fernandez', 'Agila Cargo Movers Corp.', $this->davaoCenter());
    }

    /** Rider 1: Pedro Jose Garcia, Motorcycle 123ABC, covers Quezon City (near Seller 1). Offline. */
    protected function rider1(): User
    {
        return $this->world['rider1'] ??= $this->sheetRider($this->logistics1(), 'pedrojose.garcia@example.com', 'Rider@2026', 'Pedro Jose Garcia', 'Motorcycle', '123ABC', 'N01-23-456789', '2030-03-22', 'Quezon City');
    }

    /** Rider 2: Mark Anthony Bautista, Van NBC1234, covers Makati (farther from Seller 1). Offline. */
    protected function rider2(): User
    {
        return $this->world['rider2'] ??= $this->sheetRider($this->logistics1(), 'markanthony.bautista@example.com', 'Hatid#Mark26', 'Mark Anthony Bautista', 'Van', 'NBC1234', 'N02-24-111222', '2029-06-30', 'Makati');
    }

    /** Rider 3: Ramon Cruz, Suspended by Logistics 1. */
    protected function rider3(): User
    {
        if (!isset($this->world['rider3'])) {
            $rider = $this->sheetRider($this->logistics1(), 'rider.test01@example.com', 'Rider@2026', 'Ramon Cruz', 'Motorcycle', '456DEF', 'N03-25-333444', '2031-01-15', 'Quezon City');
            DB::table('users')->where('id', $rider->id)->update(['status' => 'Suspended']);
            $this->world['rider3'] = $rider->fresh();
        }

        return $this->world['rider3'];
    }

    /** A third Online-able rider of Logistics 1 (for the three-declines case). */
    protected function rider4(): User
    {
        return $this->world['rider4'] ??= $this->sheetRider($this->logistics1(), 'rider.test03@example.com', 'Rider@2026', 'Jose Rizal Santos', 'Motorcycle', '789JKL', 'N04-26-555666', '2030-12-31', 'Pasig');
    }

    /** A rider of Logistics 2 (Davao). */
    protected function davaoRider(): User
    {
        return $this->world['davaoRider'] ??= $this->sheetRider($this->logistics2(), 'rider.test05@example.com', 'Rider@2026', 'Lito Fernandez', 'Motorcycle', '321XYZ', 'N05-27-777888', '2030-05-05', 'Davao City', 'Davao del Sur');
    }

    protected function goOnline(User ...$riders): void
    {
        foreach ($riders as $rider) {
            DB::table('users')->where('id', $rider->id)->update(['is_online' => true, 'online_at' => now()]);
        }
    }

    /**
     * Seller 1's COD order for $product (P-02 by default) to Buyer 1 at
     * ADDR-1, packed and marked Ready for Pickup. Returns the order id.
     */
    protected function readyJob(?Product $product = null, string $payment = 'Cash on Delivery', ?User $buyer = null): int
    {
        $this->sheetRates();
        $this->qcCenter();
        $product ??= $this->p02();
        $buyer ??= $this->buyer1();

        $this->flushSession();
        DB::table('saved_carts')->delete();
        $this->actingAsUser($buyer, ['cart' => [$product->id . ':0' => 1]])
            ->post(route('checkout.place'), ['address' => self::ADDR_1, 'phone' => '09171234567', 'payment' => $payment]);
        $orderId = (int) DB::table('orders')->where('buyer_id', $buyer->id)->max('id');

        if ($payment !== 'Cash on Delivery') {
            $this->post(route('payment.pay', DB::table('orders')->where('id', $orderId)->value('payment_group')));
        }

        $seller = User::find(DB::table('order_items')->where('order_id', $orderId)->value('seller_id'));
        $this->flushSession();
        foreach (['Confirmed', 'Preparing'] as $step) {
            $this->actingAsUser($seller)->post(route('seller.order.status', $orderId), ['status' => $step]);
        }
        $this->actingAsUser($seller)->post(route('seller.order.pickup', $orderId), ['pickup_date' => now()->toDateString()]);
        $this->flushSession();

        return $orderId;
    }

    private function sheetLogistics(string $email, string $password, string $name, string $company, \App\Models\SortingCenter $center): User
    {
        $user = $this->makeUser('logistics', ['email' => $email, 'password' => Hash::make($password), 'name' => $name]);
        DB::table('users')->where('id', $user->id)->update(['sorting_center_id' => $center->id, 'is_head_office' => false]);
        DB::table('logistics_applications')->insert([
            'user_id' => $user->id, 'full_name' => $name, 'business_name' => $company, 'status' => 'Approved',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $user->fresh();
    }

    private function sheetRider(User $company, string $email, string $password, string $name, string $vehicle, string $plate, string $license, string $expiry, string $city, string $province = 'Metro Manila (NCR)'): User
    {
        $rider = $this->makeUser('rider', ['email' => $email, 'password' => Hash::make($password), 'name' => $name, 'address' => '1 Rider St., ' . $city . ', ' . $province]);
        DB::table('users')->where('id', $rider->id)->update(['province' => $province, 'city_municipality' => $city, 'is_online' => false]);
        \Illuminate\Support\Facades\Storage::disk('local')->put('rider-documents/' . $rider->id . '-license.jpg', 'license');
        \Illuminate\Support\Facades\Storage::disk('local')->put('rider-documents/' . $rider->id . '-orcr.pdf', 'or-cr');

        DB::table('rider_applications')->insert([
            'user_id' => $rider->id, 'created_by' => $company->id, 'full_name' => $name, 'phone' => $rider->phone, 'address' => $rider->address,
            'vehicle_type' => $vehicle, 'vehicle_model' => $vehicle === 'Van' ? 'Toyota Hiace' : 'Honda Click 125', 'plate_number' => $plate,
            'drivers_license_no' => $license, 'license_expiry' => $expiry,
            'drivers_license' => 'rider-documents/' . $rider->id . '-license.jpg', 'or_cr' => 'rider-documents/' . $rider->id . '-orcr.pdf',
            'status' => 'Approved', 'created_at' => now(), 'updated_at' => now(),
        ]);
        \App\Models\RiderArea::create(['rider_id' => $rider->id, 'province' => $province, 'city_municipality' => $city]);

        return $rider->fresh();
    }

    protected function as(User $user): static
    {
        $this->flushSession();

        return $this->actingAsUser($user);
    }

    /** A file whose real content is a JPEG, exactly $bytes long (size_4.9MB.jpg and friends). */
    protected function jpegOfSize(string $name, int $bytes): UploadedFile
    {
        $head = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00";

        return $this->realUpload($name, $head . str_repeat("\x00", $bytes - strlen($head) - 2) . "\xFF\xD9");
    }

    /** A Windows program, optionally renamed (renamed_exe.jpg). */
    protected function exe(string $name = 'installer.exe'): UploadedFile
    {
        return $this->realUpload($name, "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xFF\xFF\x00\x00" . str_repeat("\x00", 2048) . 'This program cannot be run in DOS mode.');
    }

    protected function docx(string $name = 'price_list.docx'): UploadedFile
    {
        return $this->realUpload($name, "PK\x03\x04\x14\x00\x06\x00\x08\x00\x00\x00!\x00" . str_repeat('w', 2048));
    }

    /**
     * An upload whose type is read from its content (like a real browser
     * upload), not from its name — fake() files go by the name only.
     */
    protected function realUpload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'bbup');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    protected function mb(float $mb): int
    {
        return match ($mb) {
            4.9 => 5138022,
            5.0 => 5242880,
            5.1 => 5347738,
            default => (int) round($mb * 1048576),
        };
    }

    private function sheetSeller(string $email, string $password, string $name, string $shop, string $category, string $province, string $city, string $address, string $phone, string $status = 'Approved'): User
    {
        $seller = $this->makeUser('seller', [
            'email' => $email, 'password' => Hash::make($password), 'name' => $name, 'phone' => $phone,
            'address' => $address,
        ]);
        $seller->forceFill(['province' => $province, 'city_municipality' => $city])->save();

        DB::table('seller_applications')->insert([
            'user_id' => $seller->id, 'full_name' => $name, 'business_name' => $shop, 'phone' => $phone,
            'address' => $address, 'business_category' => $category, 'status' => $status,
            'reviewed_at' => $status === 'Approved' ? now() : null, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $seller->fresh();
    }

    private function sheetBuyer(string $email, string $password, string $name, string $phone, string $address): User
    {
        $buyer = $this->makeUser('buyer', ['email' => $email, 'password' => Hash::make($password), 'name' => $name, 'phone' => $phone, 'address' => $address]);
        BuyerAddress::create(['user_id' => $buyer->id, 'label' => 'Home', 'phone' => $phone, 'address' => $address, 'is_default' => true]);

        return $buyer;
    }
}
