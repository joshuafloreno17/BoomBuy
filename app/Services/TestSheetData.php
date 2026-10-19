<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\DeliveryFee;
use App\Support\PhLocations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * The "Test Data" tab shared by the classmates' black-box test sheets, on
 * the real database, so other groups can test boombuy.store by hand.
 *
 *  - seed():  makes (or puts back) the sheet's accounts, addresses, store
 *             categories, products P-01…P-07, vouchers and zones & rates.
 *             Accounts the testers already made through Account Creation
 *             are kept and just set to the sheet's state. Run it again
 *             before each module ("reset to the Test Data values", A1).
 *  - clear(): removes every @example.com account with everything that
 *             hangs off it, the sheet's vouchers and zones, and the test
 *             mailbox, so Account Creation can be run again from the start.
 *
 * Mail to these accounts is read at /mailbox (App\Support\MailCatcher).
 * Mirrors tests/Feature/Scenarios/ModuleSheetWorld.
 */
class TestSheetData
{
    /** Platform settings the sheet changes; their old values are kept here and put back by clear(). */
    private const SAVED_SETTINGS_KEY = 'testsheet_saved_settings';

    private const ZONE_TAG = ' (Test Data)';

    public const VOUCHER_CODES = ['MCH50', 'MCH10', 'RVSECRET', 'MCHOLD', 'MCHSOON', 'MCHMAX', 'PLAT100', 'FREESHIP'];

    private const ADDR_1 = '45 Kalayaan Ave., Brgy. Central, Quezon City, Metro Manila (NCR)';
    private const ADDR_3 = '78 Osmeña Blvd., Brgy. Capitol Site, Cebu City, Cebu';

    /** A 1×1 PNG for the placeholder rider documents. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';

    /** @var array<string, string> what seed() did, for the command / admin page */
    private array $log = [];

    /** @return array<string, string> */
    public function seed(): array
    {
        $this->log = [];

        DB::transaction(function () {
            $this->settings();
            $this->zones();

            $seller1 = $this->seller('mariaclara.santos@example.com', 'Tindahan@2026', 'Maria Clara', 'Santos', 'Maria Clara Handicrafts', 'home-living',
                'Metro Manila (NCR)', 'Quezon City', 'Commonwealth', 'Blk 12 Lot 8, Sampaguita St.', '09189876543', '1998-01-10',
                ['seller_type' => 'Sole Proprietorship', 'tin' => '123-456-789-000', 'region' => 'NCR', 'postal_code' => '1121']);
            $seller2 = $this->seller('roberto.villanueva@example.com', 'Gadget#Hub26', 'Roberto', 'Villanueva', 'RV Gadget Hub', 'electronics',
                'Cebu', 'Cebu City', 'Lahug', '2F, 45 Gorordo Ave.', '09194567890', '1990-07-21',
                ['seller_type' => 'Corporation', 'tin' => '456-789-012-000', 'region' => 'Region VII', 'postal_code' => '6000']);
            $this->seller('seller.test01@example.com', 'Tindahan@2026', 'Lola', 'Test', 'Tindahan ni Lola', 'home-living',
                'Metro Manila (NCR)', 'Quezon City', 'Commonwealth', '5 Mabini St.', '09170000011', '1985-04-02', [], 'Pending Verification');

            $buyer1 = $this->buyer('juanmiguel.delacruz@example.com', 'Mamimili@2026', 'Juan Miguel', 'Dela Cruz', '09171234567', '1998-05-14', self::ADDR_1,
                ['province' => 'Metro Manila (NCR)', 'city_municipality' => 'Quezon City', 'barangay' => 'Central', 'street_address' => '45 Kalayaan Ave.']);
            $buyer2 = $this->buyer('anamarie.reyes@example.com', 'Suki#2026Ana', 'Ana Marie', 'Reyes', '09173456789', '2001-11-02', self::ADDR_3,
                ['province' => 'Cebu', 'city_municipality' => 'Cebu City', 'barangay' => 'Capitol Site', 'street_address' => '78 Osmeña Blvd.']);

            $qc = $this->center('Metro Manila (NCR)', 'Quezon City');
            $davao = $this->center('Davao del Sur', 'Davao City');

            $logistics1 = $this->logistics('operations@bilishatid.example.com', 'Hatid@2026', 'Carlos Antonio', 'Mendoza', 'Bilis Hatid Express Inc.', $qc, '09285551234',
                ['position' => 'Operations Manager', 'sec_registration_no' => 'CS202012345', 'tin' => '234-567-890-000', 'region' => 'NCR', 'postal_code' => '1603',
                    'service_areas' => json_encode(['ncr', 'calabarzon']), 'address' => 'Unit 5B, 88 West Capitol Drive, Kapitolyo, Pasig City, Metro Manila (NCR)']);
            $logistics2 = $this->logistics('admin@agilacargo.example.com', 'Agila#Cargo26', 'Liza Mae', 'Fernandez', 'Agila Cargo Movers Corp.', $davao, '09396667890',
                ['position' => 'General Manager', 'sec_registration_no' => 'CS201998765', 'tin' => '567-890-123-000', 'region' => 'Region XI', 'postal_code' => '8000',
                    'service_areas' => json_encode(['davao']), 'address' => 'Door 3, 210 Sampaguita St., Buhangin, Davao City, Davao del Sur']);

            $this->rider($logistics1, 'pedrojose.garcia@example.com', 'Rider@2026', 'Pedro Jose', 'Garcia', '09451239876', '1995-03-22', 'Motorcycle', '123ABC', 'N01-23-456789', '2030-03-22', 'Quezon City');
            $this->rider($logistics1, 'markanthony.bautista@example.com', 'Hatid#Mark26', 'Mark Anthony', 'Bautista', '09662345678', '1999-08-09', 'Van', 'NBC1234', 'D05-19-123456', '2031-08-09', 'Makati');
            $this->rider($logistics1, 'rider.test01@example.com', 'Rider@2026', 'Ramon', 'Cruz', '09170000015', '1993-02-11', 'Motorcycle', '456DEF', 'N03-25-333444', '2031-01-15', 'Quezon City', 'Suspended');
            $this->rider($logistics2, 'rider.test05@example.com', 'Rider@2026', 'Lito', 'Fernandez', '09170000017', '1994-06-30', 'Motorcycle', '321XYZ', 'N05-27-777888', '2030-05-05', 'Davao City', 'Active', 'Davao del Sur');

            $products = $this->products($seller1, $seller2);
            $this->storeCategories($seller1, $products);
            $this->clearPromotions([$seller1->id, $seller2->id]);
            $this->vouchers($seller1, $seller2, $buyer2);
            $this->freshCarts([$buyer1->id, $buyer2->id]);
        });

        return $this->log;
    }

    /** @return array<string, string> */
    public function clear(): array
    {
        $this->log = [];

        DB::transaction(function () {
            $ids = self::testAccounts()->pluck('id')->all();
            $this->purge('users', $ids);
            $this->log['Test accounts removed'] = (string) count($ids);

            $vouchers = DB::table('vouchers')->whereIn('code', self::VOUCHER_CODES)->pluck('id')->all();
            $this->purge('vouchers', $vouchers);
            $this->log['Sheet vouchers removed'] = (string) count($vouchers);

            $zones = DB::table('shipping_zones')->where('name', 'like', '%' . self::ZONE_TAG)->pluck('id')->all();
            $this->purge('shipping_zones', $zones);
            $this->log['Sheet zones removed'] = (string) count($zones);

            if ($saved = PlatformSetting::get(self::SAVED_SETTINGS_KEY)) {
                foreach (json_decode($saved, true) ?: [] as $key => $value) {
                    $value === null ? DB::table('platform_settings')->where('key', $key)->delete() : PlatformSetting::set($key, $value);
                }
                DB::table('platform_settings')->where('key', self::SAVED_SETTINGS_KEY)->delete();
                $this->log['Settings put back'] = 'free-shipping minimum';
            }

            $this->log['Test mailbox emptied'] = (string) DB::table('test_mails')->delete();
        });

        return $this->log;
    }

    /** Every test account: @example.com and its subdomains (…@bilishatid.example.com), as MailCatcher catches. */
    public static function testAccounts(): \Illuminate\Database\Query\Builder
    {
        return DB::table('users')->where(function ($q) {
            foreach (\App\Support\MailCatcher::DOMAINS as $domain) {
                $q->orWhere('email', 'like', '%@' . $domain)->orWhere('email', 'like', '%.' . $domain);
            }
        });
    }

    /* ---------------------------------------------------------------- settings & zones */

    /** No free-shipping minimum while testing, so the sheet's fees always show (as in the automated tests). */
    private function settings(): void
    {
        if (!PlatformSetting::get(self::SAVED_SETTINGS_KEY)) {
            $old = DB::table('platform_settings')->where('key', DeliveryFee::FREE_SHIPPING_KEY)->value('value');
            PlatformSetting::set(self::SAVED_SETTINGS_KEY, json_encode([DeliveryFee::FREE_SHIPPING_KEY => $old]));
        }

        PlatformSetting::set(DeliveryFee::FREE_SHIPPING_KEY, '1000000');
        $this->log['Free-shipping minimum'] = 'off while testing';
    }

    /**
     * NCR and Visayas with the sheet's rates; Luzon and Mindanao without
     * (the admin's Delivery Fees apply there). Batanes is in no zone: ADDR-4
     * is the sheet's unserviceable address.
     */
    private function zones(): void
    {
        $regions = fn (array $keys) => collect($keys)->flatMap(fn ($key) => PhLocations::REGIONS[$key]['provinces'])->values()->all();

        $zones = [
            'NCR' => $regions(['ncr']),
            'Visayas' => $regions(['western-visayas', 'central-visayas', 'eastern-visayas']),
            'Luzon' => array_values(array_diff($regions(['car', 'ilocos', 'cagayan-valley', 'central-luzon', 'calabarzon', 'mimaropa', 'bicol']), ['Batanes'])),
            'Mindanao' => $regions(['zamboanga', 'northern-mindanao', 'davao', 'soccsksargen', 'caraga', 'barmm']),
        ];

        // A zone a tester added (e.g. Batanes in LOG-RATE-004) goes, so the sheet's zones fit again.
        DB::table('shipping_zones')->where('name', 'not like', '%' . self::ZONE_TAG)->get()->each(function ($zone) {
            $this->purge('shipping_zones', [$zone->id]);
        });

        $ids = [];
        foreach ($zones as $name => $provinces) {
            $ids[$name] = $this->upsertId('shipping_zones', ['name' => $name . self::ZONE_TAG], ['provinces' => json_encode($provinces)]);
        }

        DB::table('shipping_rates')->whereIn('from_zone_id', $ids)->orWhereIn('to_zone_id', $ids)->delete();
        foreach ([['NCR', 'NCR', 50, 15], ['Visayas', 'NCR', 120, 40], ['NCR', 'Visayas', 120, 40], ['Visayas', 'Visayas', 60, 20]] as [$from, $to, $first, $extra]) {
            DB::table('shipping_rates')->insert(['from_zone_id' => $ids[$from], 'to_zone_id' => $ids[$to], 'first_kg' => $first, 'per_kg' => $extra, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->log['Zones & rates'] = 'NCR, Visayas (sheet rates), Luzon, Mindanao; Batanes unserviceable';
    }

    /* ---------------------------------------------------------------- accounts */

    private function user(string $email, string $password, string $role, string $first, string $last, string $phone, string $birthdate, array $place, string $status = 'Active'): User
    {
        $user = User::where('email', $email)->first() ?? new User(['email' => $email]);

        $user->forceFill(array_merge([
            'name' => $first . ' ' . $last, 'first_name' => $first, 'last_name' => $last,
            'password' => Hash::make($password), 'role' => $role, 'phone' => $phone,
            'birthdate' => $birthdate, 'age' => \Illuminate\Support\Carbon::parse($birthdate)->age,
            'is_verified' => true, 'email_verified_at' => $user->email_verified_at ?? now(),
            'otp_code' => null, 'otp_expires_at' => null, 'status' => $status,
        ], $place))->save();

        $this->log[$email] = $user->wasRecentlyCreated ? 'created' : 'reset';

        return $user;
    }

    private function buyer(string $email, string $password, string $first, string $last, string $phone, string $birthdate, string $address, array $place): User
    {
        $buyer = $this->user($email, $password, 'buyer', $first, $last, $phone, $birthdate, ['address' => $address] + $place);

        DB::table('buyer_applications')->updateOrInsert(['user_id' => $buyer->id], [
            'full_name' => $buyer->name, 'phone' => $phone, 'address' => $address, 'status' => 'Approved',
            'reviewed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        // The address book: just the default Home address (ADDR-1 / ADDR-3); testers add the others.
        DB::table('buyer_addresses')->where('user_id', $buyer->id)->delete();
        DB::table('buyer_addresses')->insert([
            'user_id' => $buyer->id, 'label' => 'Home', 'recipient_name' => $buyer->name, 'phone' => $phone, 'address' => $address,
            'province' => $place['province'], 'city_municipality' => $place['city_municipality'], 'barangay' => $place['barangay'], 'street' => $place['street_address'],
            'region' => PhLocations::region($place['province']), 'is_default' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $buyer;
    }

    private function seller(string $email, string $password, string $first, string $last, string $shop, string $category, string $province, string $city, string $barangay, string $street, string $phone, string $birthdate, array $details, string $status = 'Approved'): User
    {
        $address = $street . ', ' . $barangay . ', ' . $city . ', ' . $province;
        $seller = $this->user($email, $password, 'seller', $first, $last, $phone, $birthdate,
            ['address' => $address, 'province' => $province, 'city_municipality' => $city, 'barangay' => $barangay, 'street_address' => $street]);

        $this->upsertId('seller_applications', ['user_id' => $seller->id], array_merge([
            'full_name' => $seller->name, 'business_name' => $shop, 'phone' => $phone, 'address' => $address,
            'business_category' => $category, 'status' => $status, 'vacation_mode' => false,
            'admin_remarks' => null, 'reviewed_at' => $status === 'Approved' ? now() : null,
        ], $details));

        return $seller;
    }

    private function logistics(string $email, string $password, string $first, string $last, string $company, ?object $center, string $phone, array $details): User
    {
        $user = $this->user($email, $password, 'logistics', $first, $last, $phone, '1988-09-15', ['address' => $details['address']]);
        $user->forceFill(['sorting_center_id' => $center?->id, 'is_head_office' => false])->save();

        $this->upsertId('logistics_applications', ['user_id' => $user->id], array_merge([
            'full_name' => $user->name, 'business_name' => $company, 'phone' => $phone, 'status' => 'Approved',
            'admin_remarks' => null, 'reviewed_at' => now(),
        ], $details));

        return $user;
    }

    private function rider(User $company, string $email, string $password, string $first, string $last, string $phone, string $birthdate, string $vehicle, string $plate, string $license, string $expiry, string $city, string $status = 'Active', string $province = 'Metro Manila (NCR)'): User
    {
        $rider = $this->user($email, $password, 'rider', $first, $last, $phone, $birthdate,
            ['address' => '1 Rider St., ' . $city . ', ' . $province, 'province' => $province, 'city_municipality' => $city, 'is_online' => false], $status);

        $docs = [];
        foreach (['license' => 'drivers_license', 'orcr' => 'or_cr'] as $file => $column) {
            $docs[$column] = 'rider-documents/' . $rider->id . '-' . $file . '.png';
            Storage::disk('local')->put($docs[$column], base64_decode(self::PNG));
        }

        $this->upsertId('rider_applications', ['user_id' => $rider->id], array_merge([
            'created_by' => $company->id, 'full_name' => $rider->name, 'phone' => $phone, 'address' => $rider->address,
            'vehicle_type' => $vehicle, 'vehicle_model' => $vehicle === 'Van' ? 'Toyota Hiace' : 'Honda Click 125', 'plate_number' => $plate,
            'drivers_license_no' => $license, 'license_expiry' => $expiry, 'status' => 'Approved',
            'activation_token' => null, 'activation_expires_at' => null, 'activated_at' => now(),
            'suspension_reason' => $status === 'Suspended' ? 'Suspended for the blocked-rider test cases.' : null,
        ], $docs));

        DB::table('rider_areas')->where('rider_id', $rider->id)->delete();
        DB::table('rider_areas')->insert(['rider_id' => $rider->id, 'province' => $province, 'city_municipality' => $city, 'created_at' => now(), 'updated_at' => now()]);

        return $rider;
    }

    /** The main Sorting Center of a province (made if the network wasn't seeded). */
    private function center(string $province, string $city): object
    {
        $center = DB::table('sorting_centers')->where('province', $province)->where('city_municipality', $city)->orderBy('id')->first();

        if (!$center) {
            $id = DB::table('sorting_centers')->insertGetId([
                'name' => 'BoomBuy Sorting Center – ' . $province, 'region' => PhLocations::region($province), 'province' => $province,
                'city_municipality' => $city, 'address' => 'Poblacion, ' . $city, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $center = DB::table('sorting_centers')->find($id);
        }

        return $center;
    }

    /* ---------------------------------------------------------------- products, categories, vouchers */

    /** @return array<string, int> P-01…P-07 => product id */
    private function products(User $seller1, User $seller2): array
    {
        $rows = [
            'P-01' => [$seller1, 'Abaca Woven Tote Bag', "Women's Fashion", 199, 0, 0.4, [40, 35, 5], null, 'Handwoven abaca tote bag from Bulacan artisans.'],
            'P-02' => [$seller1, 'Rattan Fruit Basket', 'Home & Living', 450, 20, 0.8, [30, 30, 15], null, 'Handwoven rattan fruit basket, 30 cm wide.'],
            'P-03' => [$seller1, 'Capiz Shell Lamp', 'Home & Living', 1250, 1, 1.5, [30, 30, 40], null, 'Table lamp with a capiz shell shade.'],
            'P-04' => [$seller1, 'Banig Placemat Set of 4', 'Home & Living', 350, 0, 0.5, [null, null, null], null, 'Four handwoven banig placemats.'],
            'P-05' => [$seller2, 'USB-C Fast Charger 20W', 'Electronics', 399, 50, 0.2, [10, 8, 5], null, '20W USB-C power delivery wall charger.'],
            'P-06' => [$seller2, 'Wireless Earbuds X2', 'Electronics', 1499, 0, 0.3, [null, null, null], null, 'True wireless earbuds with charging case.'],
            'P-07' => [$seller2, 'Power Bank 10000mAh', 'Electronics', 899, 5, 0.4, [null, null, null], 2, '10000mAh power bank with two USB ports.'],
        ];

        $ids = [];
        foreach ($rows as $ref => [$seller, $name, $category, $price, $stock, $kg, [$l, $w, $h], $limit, $description]) {
            $ids[$ref] = $this->upsertId('products', ['seller_id' => $seller->id, 'name' => $name], [
                'category' => $category, 'price' => $price, 'original_price' => null, 'discount_percent' => null, 'stock' => $stock,
                'purchase_limit' => $limit, 'weight_kg' => $kg, 'length_cm' => $l, 'width_cm' => $w, 'height_cm' => $h,
                'description' => $description, 'condition' => 'New', 'is_flagged' => false, 'flag_reason' => null,
                'is_archived' => false, 'is_draft' => false, 'low_stock_alerted' => false, 'deleted_at' => null,
            ]);
        }

        // P-01: Color × Size, M +₱50 and L +₱100 on the ₱199 base.
        $this->variations($ids['P-01'], [
            ['Natural', 'S', 0, 10], ['Natural', 'M', 50, 8], ['Natural', 'L', 100, 5],
            ['Black', 'S', 0, 10], ['Black', 'M', 50, 0], ['Black', 'L', 100, 3],
        ], 'MCH-TOTE');
        $this->variations($ids['P-06'], [['Black', null, 0, 10], ['White', null, 0, 10]], 'RV-EARBUDS-X2');

        $this->log['Products'] = 'P-01 to P-07 at their Test Data price and stock';

        return $ids;
    }

    private function variations(int $productId, array $rows, string $skuPrefix): void
    {
        $keep = [];
        foreach ($rows as [$color, $size, $adjust, $stock]) {
            $keep[] = $this->upsertId('product_variations', [
                'product_id' => $productId, 'variation_type' => 'Color', 'variation_value' => $color,
                'option2_type' => $size ? 'Size' : null, 'option2_value' => $size,
            ], [
                'sku' => strtoupper($skuPrefix . '-' . $color . ($size ? '-' . $size : '')),
                'price_adjustment' => $adjust, 'stock' => $stock, 'low_stock_alerted' => false, 'deleted_at' => null,
            ]);
        }

        // Options a tester added go (soft-deleted, so past orders still show them).
        DB::table('product_variations')->where('product_id', $productId)->whereNotIn('id', $keep)->whereNull('deleted_at')->update(['deleted_at' => now()]);
    }

    /** Seller 1's store categories CAT-1…CAT-5; ones a tester added go. */
    private function storeCategories(User $seller1, array $products): void
    {
        DB::table('store_categories')->where('seller_id', $seller1->id)->delete();

        $make = fn (string $name, ?int $parent, int $sort) => DB::table('store_categories')->insertGetId([
            'seller_id' => $seller1->id, 'parent_id' => $parent, 'name' => $name, 'sort_order' => $sort, 'is_hidden' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $bags = $make('Bags', null, 1);
        $decor = $make('Home Decor', null, 2);
        $lamps = $make('Lamps', $decor, 1);
        $baskets = $make('Baskets', $decor, 2);
        $kitchen = $make('Kitchen & Dining', null, 3);

        foreach ([[$products['P-01'], $bags], [$products['P-03'], $lamps], [$products['P-02'], $baskets], [$products['P-04'], $kitchen]] as [$product, $category]) {
            DB::table('product_store_category')->insert(['product_id' => $product, 'store_category_id' => $category, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->log['Store categories'] = 'Bags, Home Decor > Lamps / Baskets, Kitchen & Dining';
    }

    /** Sales, flash sales and bundles a tester set up would change the sheet's prices. */
    private function clearPromotions(array $sellerIds): void
    {
        foreach (['product_sales', 'flash_sales', 'bundles'] as $table) {
            if (Schema::hasTable($table)) {
                $this->purge($table, DB::table($table)->whereIn('seller_id', $sellerIds)->pluck('id')->all());
            }
        }
    }

    private function vouchers(User $seller1, User $seller2, User $buyer2): void
    {
        $base = ['kind' => 'store','discount_type' => 'fixed', 'max_discount' => null, 'max_uses' => null, 'per_buyer_limit' => 1, 'used_count' => 0,
            'starts_at' => now()->subDay(), 'ends_at' => now()->endOfMonth(), 'ended_at' => null, 'expires_at' => null,
            'display' => 'claimable', 'applies_to' => 'all', 'is_active' => true];

        $rules = [
            'MCH50' => ['seller_id' => $seller1->id, 'discount_value' => 50, 'min_order_amount' => 300, 'max_uses' => 100],
            'MCH10' => ['seller_id' => $seller1->id, 'discount_type' => 'percentage', 'discount_value' => 10, 'max_discount' => 100, 'min_order_amount' => 500, 'max_uses' => 50],
            'RVSECRET' => ['seller_id' => $seller2->id, 'discount_value' => 100, 'min_order_amount' => 1000, 'max_uses' => 20, 'display' => 'code'],
            'MCHOLD' => ['seller_id' => $seller1->id, 'discount_value' => 30, 'min_order_amount' => 200, 'starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()],
            'MCHSOON' => ['seller_id' => $seller1->id, 'discount_value' => 30, 'min_order_amount' => 200, 'starts_at' => now()->addDay(), 'ends_at' => now()->addMonth()],
            'MCHMAX' => ['seller_id' => $seller1->id, 'discount_value' => 20, 'min_order_amount' => 100, 'max_uses' => 1, 'used_count' => 1],
            'PLAT100' => ['seller_id' => null, 'kind' => 'platform', 'discount_value' => 100, 'min_order_amount' => 1000],
            'FREESHIP' => ['seller_id' => null, 'kind' => 'shipping', 'discount_value' => 50, 'min_order_amount' => 300],
        ];

        // Vouchers a tester made for the sheet sellers go too.
        $this->purge('vouchers', DB::table('vouchers')->whereIn('seller_id', [$seller1->id, $seller2->id])->whereNotIn('code', array_keys($rules))->pluck('id')->all());

        foreach ($rules as $code => $rule) {
            $id = $this->upsertId('vouchers', ['code' => $code], array_merge($base, $rule));
            DB::table('voucher_claims')->where('voucher_id', $id)->delete();
            if (Schema::hasTable('voucher_products')) {
                DB::table('voucher_products')->where('voucher_id', $id)->delete();
            }
        }

        // MCHMAX: its one use was Buyer 2's.
        DB::table('voucher_claims')->insert(['voucher_id' => DB::table('vouchers')->where('code', 'MCHMAX')->value('id'), 'user_id' => $buyer2->id, 'created_at' => now(), 'updated_at' => now()]);

        $this->log['Vouchers'] = implode(', ', array_keys($rules));
    }

    private function freshCarts(array $buyerIds): void
    {
        DB::table('saved_carts')->whereIn('user_id', $buyerIds)->delete();
    }

    /* ---------------------------------------------------------------- helpers */

    /** Update the row matching $keys (or insert it) and return its id. */
    private function upsertId(string $table, array $keys, array $values): int
    {
        $columns = array_flip(Schema::getColumnListing($table));
        $values = array_intersect_key($values, $columns);

        $id = DB::table($table)->where($keys)->value('id');

        if ($id) {
            DB::table($table)->where('id', $id)->update($values + (isset($columns['updated_at']) ? ['updated_at' => now()] : []));

            return (int) $id;
        }

        $stamps = array_intersect_key(['created_at' => now(), 'updated_at' => now()], $columns);

        return (int) DB::table($table)->insertGetId($keys + $values + $stamps);
    }

    /**
     * Delete rows with everything that points at them, following the
     * database's foreign keys: a nullable column that would be set to null
     * (or has no cascade) is cleared, anything else is deleted first.
     */
    public function purge(string $table, array $ids, int $depth = 0): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if ($ids === [] || $depth > 8) {
            return;
        }

        foreach ($this->foreignKeysTo($table) as [$child, $column, $nullable, $rule]) {
            if ($child === $table && $column === 'id') {
                continue;
            }

            if ($nullable && $rule !== 'CASCADE') {
                DB::table($child)->whereIn($column, $ids)->update([$column => null]);
            } elseif (Schema::hasColumn($child, 'id')) {
                DB::table($child)->whereIn($column, $ids)->pluck('id')->chunk(500)
                    ->each(fn ($chunk) => $this->purge($child, $chunk->all(), $depth + 1));
            } else {
                DB::table($child)->whereIn($column, $ids)->delete();
            }
        }

        foreach (array_chunk($ids, 500) as $chunk) {
            DB::table($table)->whereIn('id', $chunk)->delete();
        }
    }

    /** @return array<int, array{0:string, 1:string, 2:bool, 3:string}> [child table, column, nullable, on delete] */
    private function foreignKeysTo(string $table): array
    {
        static $cache = [];

        if (isset($cache[$table])) {
            return $cache[$table];
        }

        $keys = $this->declaredForeignKeysTo($table);
        $declared = array_map(fn ($key) => $key[0] . '.' . $key[1], $keys);

        // A row that belongs to a test account goes with it, even where the
        // database would only empty the column (products.seller_id is "set null").
        if ($table === 'users') {
            foreach ($keys as &$key) {
                if ((self::USER_COLUMNS[$key[1]] ?? null) === 'delete') {
                    $key[3] = 'CASCADE';
                }
            }
            unset($key);
        }

        // Columns that point here by name only, without a foreign key (orders.rider_id,
        // sessions.user_id, or ones SQLite lost when a table was rebuilt).
        $byName = $table === 'users' ? self::USER_COLUMNS : [\Illuminate\Support\Str::singular($table) . '_id' => 'delete'];
        foreach ($this->tables() as $child) {
            foreach (Schema::getColumns($child) as $column) {
                $rule = $byName[$column['name']] ?? null;
                if ($rule && !in_array($child . '.' . $column['name'], $declared, true)) {
                    // 'delete' = the row belongs to it; 'null' = it only mentions it.
                    $keys[] = [$child, $column['name'], $rule === 'null' && $column['nullable'], $rule === 'delete' ? 'CASCADE' : 'SET NULL'];
                }
            }
        }

        return $cache[$table] = $keys;
    }

    /** Columns that hold a user id: the row is theirs (delete) or just mentions them (null). */
    private const USER_COLUMNS = [
        'user_id' => 'delete', 'seller_id' => 'delete', 'buyer_id' => 'delete', 'sender_id' => 'delete', 'receiver_id' => 'delete',
        'rider_id' => 'null', 'delivery_rider_id' => 'null', 'created_by' => 'null', 'reviewed_by' => 'null',
    ];

    /** @return string[] */
    private function tables(): array
    {
        static $tables = null;

        return $tables ??= array_map(fn ($t) => $t['name'], Schema::getTables());
    }

    /** @return array<int, array{0:string, 1:string, 2:bool, 3:string}> */
    private function declaredForeignKeysTo(string $table): array
    {
        $keys = [];

        if (DB::getDriverName() === 'sqlite') {
            foreach (DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'") as $t) {
                foreach (DB::select('PRAGMA foreign_key_list("' . $t->name . '")') as $fk) {
                    if ($fk->table === $table) {
                        $info = collect(DB::select('PRAGMA table_info("' . $t->name . '")'))->firstWhere('name', $fk->from);
                        $keys[] = [$t->name, $fk->from, $info && !$info->notnull, strtoupper($fk->on_delete)];
                    }
                }
            }
        } else {
            $rows = DB::select(
                'SELECT k.TABLE_NAME t, k.COLUMN_NAME c, col.IS_NULLABLE n, r.DELETE_RULE d
                 FROM information_schema.KEY_COLUMN_USAGE k
                 JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
                 JOIN information_schema.COLUMNS col ON col.TABLE_SCHEMA = k.TABLE_SCHEMA AND col.TABLE_NAME = k.TABLE_NAME AND col.COLUMN_NAME = k.COLUMN_NAME
                 WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME = ?',
                [$table]
            );
            foreach ($rows as $row) {
                $keys[] = [$row->t, $row->c, $row->n === 'YES', strtoupper($row->d)];
            }
        }

        return $keys;
    }
}
