<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\StoreCategory;
use App\Models\User;
use App\Support\Categories;
use App\Support\ProductPhotos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Demo catalog with photos: each shop gets its own store categories
 * (e.g. Phones, Chargers & Cables, Audio › Speakers) and five products in
 * each, with Color × Size/Storage options, a photo per color and a gallery.
 *
 * Photos come from database/seeders/catalog/<shop>/ (Pixabay, Content
 * License; some colors are recolored copies of the same photo) and are
 * copied to storage/app/public/products/.
 *
 * Safe to run again: store categories are reused and a product that already
 * exists in that shop (same name) is skipped.
 *
 *   php artisan db:seed --class=CatalogSeeder
 */
class CatalogSeeder extends Seeder
{
    /** Stock for options, in turn: a spread with a few low ones. */
    private const STOCK_SPREAD = [12, 8, 20, 6, 15, 10, 18, 9, 14, 4];

    private const PHONE = ['device_type' => 'Phone', 'os' => 'Android 14', 'sim' => 'Dual SIM (nano)', 'warranty' => '1 year shop warranty'];

    /**
     * seller email => [folder, store categories, products].
     * Store categories: name => [subcategories]. A product's 'in' is "Parent" or "Parent > Child".
     * options: [type => [value, ...]] — a value may be "Name|+price"; for the first type,
     * a value may map to its photo (value => file). 'out' lists sold-out values or "v1/v2" pairs.
     */
    private function catalog(): array
    {
        return [
            'voltique@boombuy.test' => [
                'folder' => 'voltique',
                'categories' => ['Phones' => [], 'Chargers & Cables' => [], 'Audio' => ['Earbuds & Headphones', 'Speakers']],
                'products' => [
                    [
                        'name' => 'Apple iPhone 18 Pro', 'in' => 'Phones', 'price' => 94990, 'weight' => 0.4, 'brand' => 'Apple',
                        'description' => "The newest iPhone 18 Pro with the A20 Pro chip and a 48MP triple camera system with variable aperture. 100% authentic, brand new and sealed from Apple Philippines.\n\n"
                            . "KEY FEATURES\n• 6.3\" Super Retina XDR display — bright, sharp and smooth\n• A20 Pro chip (6-core CPU, 7-core GPU) for fast apps, games and editing\n• 48MP triple rear camera with variable aperture for sharper photos in any light\n• 18MP front camera for clear selfies and video calls\n• IP68 water and dust resistance\n• 5G, Dual SIM (nano + eSIM)\n\n"
                            . "COLORS\nBlack · Silver · Glacier · Burgundy\n\n"
                            . "STORAGE\n256GB · 512GB · 1TB\n\n"
                            . "WHAT'S IN THE BOX\n• iPhone 18 Pro\n• USB-C charging cable\n• Documentation\n\n"
                            . "WARRANTY\n• 1 year Apple Philippines warranty\n• 7-day returns on BoomBuy for damaged, defective or wrong items\n\n"
                            . "NOTE\nPhotos are for reference; the actual color may look slightly different on your screen. Please record an unboxing video when your order arrives.",
                        'specs' => ['device_type' => 'Phone', 'brand' => 'Apple', 'model' => 'iPhone 18 Pro', 'screen' => '6.3" Super Retina XDR', 'processor' => 'A20 Pro (6-core CPU, 7-core GPU)', 'rear_camera' => '48MP + 48MP + 48MP', 'front_camera' => '18MP', 'network' => '5G', 'sim' => 'Dual SIM (nano + eSIM)', 'warranty' => '1 year Apple PH warranty'],
                        'options' => [
                            'Color' => ['Black' => 'iphone-18-pro-black.jpg', 'Silver' => 'iphone-18-pro-silver.jpg', 'Glacier' => 'iphone-18-pro-glacier.jpg', 'Burgundy' => 'iphone-18-pro-burgundy.jpg'],
                            'Storage' => ['256GB', '512GB|+16000', '1TB|+48000'],
                        ],
                        'stock' => [12, 8, 4, 10, 6, 3, 10, 6, 3, 12, 8, 4],
                        'photos' => ['iphone-18-pro-2.jpg'],
                    ],
                    [
                        'name' => 'Nova X5 5G Smartphone', 'in' => 'Phones', 'price' => 12990, 'discount' => 8, 'weight' => 0.4, 'brand' => 'Nova',
                        'description' => "Fast 5G phone with a smooth 120Hz AMOLED screen and a 5,000 mAh battery that lasts all day.\n\n• 64MP triple camera\n• 33W fast charging (charger included)\n• Free clear case and screen protector",
                        'specs' => ['brand' => 'Nova', 'model' => 'X5 5G', 'ram' => '8GB', 'screen' => '6.6" AMOLED, 120Hz', 'processor' => 'Octa-core 2.4GHz', 'rear_camera' => '64MP + 8MP + 2MP', 'front_camera' => '16MP', 'battery' => '5,000 mAh', 'network' => '5G'] + self::PHONE,
                        'options' => [
                            'Color' => ['Midnight Black' => 'nova-x5-midnight.jpg', 'Ocean Blue' => 'nova-x5-ocean.jpg', 'Lavender' => 'nova-x5-lavender.jpg', 'Mint' => 'nova-x5-mint.jpg'],
                            'Storage' => ['128GB', '256GB|+2000', '512GB|+5000'],
                        ],
                        'out' => ['Mint', '512GB'],
                        'photos' => ['nova-x5-2.jpg'],
                    ],
                    [
                        'name' => 'Nova Lite 4G Smartphone', 'in' => 'Phones', 'price' => 6499, 'weight' => 0.35, 'brand' => 'Nova',
                        'description' => "An easy, dependable everyday phone with a big screen and a battery that lasts two days.\n\n• 6.5\" HD+ display\n• 13MP dual camera\n• Face unlock and fingerprint sensor",
                        'specs' => ['brand' => 'Nova', 'model' => 'Lite 4G', 'ram' => '4GB', 'screen' => '6.5" HD+ IPS', 'processor' => 'Octa-core 1.8GHz', 'rear_camera' => '13MP + 2MP', 'front_camera' => '8MP', 'battery' => '5,000 mAh', 'os' => 'Android 13', 'network' => '4G LTE'] + self::PHONE,
                        'options' => [
                            'Color' => ['Black' => 'nova-lite-black.jpg', 'Silver' => 'nova-lite-silver.jpg'],
                            'Storage' => ['64GB', '128GB|+1000'],
                        ],
                    ],
                    [
                        'name' => 'Pulse Pro Max', 'in' => 'Phones', 'price' => 24990, 'discount' => 5, 'weight' => 0.45, 'brand' => 'Pulse',
                        'description' => "Flagship camera phone with a 200MP main sensor and 3x optical zoom.\n\n• 6.8\" AMOLED, 120Hz\n• 12GB RAM for heavy gaming\n• 65W charging: 0 to 100% in 40 minutes",
                        'specs' => ['brand' => 'Pulse', 'model' => 'Pro Max', 'ram' => '12GB', 'screen' => '6.8" AMOLED, 120Hz', 'processor' => 'Octa-core 3.2GHz', 'rear_camera' => '200MP + 12MP + 10MP', 'front_camera' => '32MP', 'battery' => '5,000 mAh', 'network' => '5G'] + self::PHONE,
                        'options' => ['Storage' => ['256GB', '512GB|+6000']],
                        'photos' => ['pulse-pro-max.jpg', 'pulse-pro-max-2.jpg'],
                    ],
                    [
                        'name' => 'Rugged Field Phone IP68', 'in' => 'Phones', 'price' => 9990, 'weight' => 0.5, 'brand' => 'Nova',
                        'description' => "Built for work sites, farms and outdoor trips: dust-proof, waterproof and drop-tested.\n\n• IP68 + 1.5m drop protection\n• 8,000 mAh battery\n• Bright screen you can read in direct sunlight",
                        'specs' => ['brand' => 'Nova', 'model' => 'Rugged R1', 'ram' => '6GB', 'screen' => '6.1" HD+, Gorilla Glass', 'rear_camera' => '48MP', 'front_camera' => '8MP', 'battery' => '8,000 mAh', 'network' => '4G LTE'] + self::PHONE,
                        'options' => ['Storage' => ['64GB', '128GB|+1500']],
                        'photos' => ['rugged-phone.jpg'],
                    ],
                    [
                        'name' => '20W USB-C Fast Charger', 'in' => 'Chargers & Cables', 'price' => 399, 'weight' => 0.12,
                        'description' => "Compact 20W USB-C PD wall charger. Charges an iPhone or Android phone to 50% in about 30 minutes.\n\n• Over-heat and over-current protection\n• Works with any USB-C cable",
                        'options' => ['Color' => ['White' => 'charger-20w-white.jpg', 'Black' => 'charger-20w-black.jpg']],
                        'photos' => ['charger-20w-2.jpg'],
                    ],
                    [
                        'name' => '18W Charger + Cable Bundle', 'in' => 'Chargers & Cables', 'price' => 549, 'discount' => 10, 'weight' => 0.2, 'stock' => 40,
                        'description' => "Everything in one box: an 18W fast wall charger and a 1m charging cable.\n\n• Ideal replacement or spare set\n• Comes in a gift-ready box",
                        'photos' => ['charger-bundle.jpg', 'charger-bundle-2.jpg'],
                    ],
                    [
                        'name' => 'USB-C Braided Cable', 'in' => 'Chargers & Cables', 'price' => 249, 'weight' => 0.08,
                        'description' => "Tough nylon-braided USB-C cable that survives daily bending. Supports fast charging up to 60W and data transfer.\n\n• Aluminum connector heads\n• 10,000+ bend lifespan",
                        'options' => [
                            'Color' => ['Black' => 'cable-black.jpg', 'White' => 'cable-white.jpg', 'Red' => 'cable-red.jpg'],
                            'Length' => ['1m', '2m|+50'],
                        ],
                        'photos' => ['cable-2.jpg'],
                    ],
                    [
                        'name' => '10,000mAh Power Bank', 'in' => 'Chargers & Cables', 'price' => 899, 'weight' => 0.25,
                        'description' => "Slim power bank that charges a phone 2 to 3 times. USB-A and USB-C ports, with LED battery level lights.\n\n• Airline-safe capacity\n• Charges two devices at once",
                        'options' => ['Color' => ['White' => 'powerbank-white.jpg', 'Black' => 'powerbank-black.jpg', 'Pink' => 'powerbank-pink.jpg']],
                        'out' => ['Pink'],
                    ],
                    [
                        'name' => '15W Wireless Charging Pad', 'in' => 'Chargers & Cables', 'price' => 799, 'weight' => 0.15, 'stock' => 25,
                        'description' => "Just set your phone down to charge. Works with Qi phones and earbuds, even through a thin case.\n\n• Soft light shows when it's charging\n• Non-slip base",
                        'photos' => ['wireless-pad.jpg', 'wireless-pad-2.jpg'],
                    ],
                    [
                        'name' => 'Wireless Earbuds X2 (ANC)', 'in' => 'Audio > Earbuds & Headphones', 'price' => 1499, 'discount' => 15, 'weight' => 0.1,
                        'description' => "True wireless earbuds with active noise cancelling for commutes and calls.\n\n• Up to 30 hours with the case\n• Touch controls, Bluetooth 5.3\n• Sweat resistant",
                        'options' => ['Color' => ['White' => 'earbuds-white.jpg', 'Black' => 'earbuds-black.jpg']],
                        'photos' => ['earbuds-2.jpg', 'earbuds-3.jpg'],
                    ],
                    [
                        'name' => 'Over-Ear Bluetooth Headphones', 'in' => 'Audio > Earbuds & Headphones', 'price' => 1899, 'weight' => 0.3,
                        'description' => "Comfortable over-ear headphones with deep bass and soft cushions for long listening.\n\n• 40 hours of playtime\n• Foldable, with a 3.5mm cable for wired use",
                        'options' => ['Color' => ['Black' => 'headphones-black.jpg', 'Brown' => 'headphones-brown.jpg', 'Pink' => 'headphones-pink.jpg']],
                    ],
                    [
                        'name' => 'Wired Earphones with Mic', 'in' => 'Audio > Earbuds & Headphones', 'price' => 199, 'weight' => 0.05, 'stock' => 120,
                        'description' => "Classic in-ear earphones with a built-in mic and play/pause button.\n\n• 3.5mm plug\n• Tangle-resistant cable",
                        'photos' => ['wired-earphones.jpg', 'wired-earphones-2.jpg', 'wired-earphones-3.jpg'],
                    ],
                    [
                        'name' => 'Portable Bluetooth Speaker IPX7', 'in' => 'Audio > Speakers', 'price' => 1299, 'weight' => 0.4,
                        'description' => "Waterproof speaker with loud, clear sound for the beach, the pool or the shower.\n\n• IPX7 waterproof\n• 12 hours of playtime\n• Carry strap included",
                        'options' => ['Color' => ['Red' => 'speaker-red.jpg', 'Blue' => 'speaker-blue.jpg', 'Black' => 'speaker-black.jpg']],
                    ],
                    [
                        'name' => 'Smart Home Speaker', 'in' => 'Audio > Speakers', 'price' => 2999, 'weight' => 0.35,
                        'description' => "Voice-controlled smart speaker: play music, set alarms and control smart lights hands-free.\n\n• Works over Wi-Fi and Bluetooth\n• Room-filling 360° sound",
                        'options' => ['Color' => ['Charcoal' => 'smart-speaker-charcoal.jpg', 'Chalk' => 'smart-speaker-chalk.jpg']],
                        'photos' => ['smart-speaker-2.jpg'],
                    ],
                ],
            ],

            'maisonbelle@boombuy.test' => [
                'folder' => 'maisonbelle',
                'categories' => ['Tops' => ['Blouses', 'T-Shirts'], 'Dresses' => [], 'Bags' => []],
                'products' => [
                    [
                        'name' => 'Linen Button Blouse', 'in' => 'Tops > Blouses', 'price' => 599, 'discount' => 17, 'weight' => 0.25,
                        'description' => "Light, breathable linen-blend blouse that stays cool on hot days. Relaxed fit with a classic collar.\n\n• Linen-cotton blend\n• Machine washable\n• Model wears size S",
                        'options' => [
                            'Color' => ['White' => 'linen-blouse-white.jpg', 'Beige' => 'linen-blouse-beige.jpg', 'Sage' => 'linen-blouse-sage.jpg', 'Dusty Pink' => 'linen-blouse-pink.jpg'],
                            'Size' => ['S', 'M', 'L', 'XL|+50'],
                        ],
                        'out' => ['Sage/XL'],
                    ],
                    [
                        'name' => 'Flowy Peasant Blouse', 'in' => 'Tops > Blouses', 'price' => 549, 'weight' => 0.25,
                        'description' => "Loose V-neck blouse with balloon sleeves. Easy to dress up with heels or down with jeans.\n\n• Soft, drapey fabric\n• Model wears size S",
                        'options' => [
                            'Color' => ['Taupe' => 'peasant-blouse-taupe.jpg', 'Black' => 'peasant-blouse-black.jpg', 'Wine' => 'peasant-blouse-wine.jpg'],
                            'Size' => ['S', 'M', 'L'],
                        ],
                    ],
                    [
                        'name' => 'Basic Cotton Crew Tee', 'in' => 'Tops > T-Shirts', 'price' => 249, 'weight' => 0.2,
                        'description' => "Everyday 100% cotton crew-neck tee. Soft, thick enough not to be see-through, and keeps its shape after washing.\n\n• Unisex regular fit",
                        'options' => [
                            'Color' => ['White' => 'crew-tee-white.jpg', 'Black' => 'crew-tee-black.jpg', 'Grey' => 'crew-tee-grey.jpg', 'Navy' => 'crew-tee-navy.jpg', 'Red' => 'crew-tee-red.jpg'],
                            'Size' => ['S', 'M', 'L', 'XL'],
                        ],
                    ],
                    [
                        'name' => 'Ribbed Crop Top', 'in' => 'Tops > T-Shirts', 'price' => 299, 'weight' => 0.15,
                        'description' => "Stretchy ribbed crop top with a high neck. Pairs well with high-waist jeans or skirts.\n\n• Cotton-spandex rib knit",
                        'options' => [
                            'Color' => ['Black' => 'crop-top-black.jpg', 'White' => 'crop-top-white.jpg', 'Brown' => 'crop-top-brown.jpg'],
                            'Size' => ['S', 'M', 'L'],
                        ],
                    ],
                    [
                        'name' => 'Oversized Graphic Tee', 'in' => 'Tops > T-Shirts', 'price' => 399, 'weight' => 0.25,
                        'description' => "Relaxed oversized tee with a hand-drawn rose print on the back.\n\n• Heavyweight cotton\n• Drop shoulders; size down for a regular fit",
                        'options' => ['Size' => ['M', 'L', 'XL']],
                        'photos' => ['graphic-tee.jpg', 'graphic-tee-2.jpg'],
                    ],
                    [
                        'name' => 'Floral Midi Dress', 'in' => 'Dresses', 'price' => 899, 'discount' => 10, 'weight' => 0.3,
                        'description' => "Flowy midi dress in a bright floral print, perfect for brunches, beach trips and family events.\n\n• Lightweight chiffon\n• Fully lined",
                        'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                        'photos' => ['floral-dress.jpg', 'floral-dress-2.jpg'],
                    ],
                    [
                        'name' => 'Satin Slip Dress', 'in' => 'Dresses', 'price' => 799, 'weight' => 0.25,
                        'description' => "Silky satin slip dress with adjustable straps and a cowl neckline. Made for date nights and parties.\n\n• Midi length\n• Bias cut that flatters every shape",
                        'options' => [
                            'Color' => ['Black' => 'satin-slip-black.jpg', 'Champagne' => 'satin-slip-champagne.jpg', 'Emerald' => 'satin-slip-emerald.jpg'],
                            'Size' => ['S', 'M', 'L'],
                        ],
                        'photos' => ['satin-slip-2.jpg'],
                    ],
                    [
                        'name' => 'Classic A-Line Dress', 'in' => 'Dresses', 'price' => 649, 'weight' => 0.3,
                        'description' => "The little dress that goes everywhere: fitted top, V-neck and a twirly A-line skirt.\n\n• Thin belt included\n• Knee length",
                        'options' => [
                            'Color' => ['Black' => 'aline-dress-black.jpg', 'Wine' => 'aline-dress-wine.jpg'],
                            'Size' => ['S', 'M', 'L'],
                        ],
                        'photos' => ['aline-dress-2.jpg'],
                    ],
                    [
                        'name' => 'Smocked Sundress', 'in' => 'Dresses', 'price' => 699, 'weight' => 0.3,
                        'description' => "Breezy sundress with a smocked top and a full, swishy skirt. Your go-to for summer.\n\n• Cotton voile\n• Midi length",
                        'options' => [
                            'Color' => ['White' => 'sundress-white.jpg', 'Yellow' => 'sundress-yellow.jpg', 'Blue' => 'sundress-blue.jpg'],
                            'Size' => ['S', 'M', 'L'],
                        ],
                        'photos' => ['sundress-2.jpg'],
                    ],
                    [
                        'name' => 'Linen Shirt Dress', 'in' => 'Dresses', 'price' => 999, 'weight' => 0.35,
                        'description' => "Long, relaxed linen dress in a natural beige. Simple, comfortable and easy to style.\n\n• Linen-cotton blend\n• Side pockets",
                        'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                        'photos' => ['shirt-dress-beige.jpg'],
                    ],
                    [
                        'name' => 'Structured Leather Satchel', 'in' => 'Bags', 'price' => 1299, 'discount' => 20, 'weight' => 0.8,
                        'description' => "Roomy satchel that fits a laptop up to 13\". Adjustable shoulder strap and magnetic flap.\n\n• Vegan leather\n• Inner zip pocket",
                        'options' => ['Color' => ['Tan' => 'satchel-tan.jpg', 'Black' => 'satchel-black.jpg', 'Cream' => 'satchel-cream.jpg']],
                    ],
                    [
                        'name' => 'Quilted Mini Crossbody', 'in' => 'Bags', 'price' => 699, 'weight' => 0.4,
                        'description' => "Cute quilted crossbody that holds your phone, wallet and keys.\n\n• Detachable chain strap\n• Gold-tone hardware",
                        'options' => ['Color' => ['Red' => 'crossbody-red.jpg', 'Black' => 'crossbody-black.jpg', 'Pink' => 'crossbody-pink.jpg']],
                    ],
                    [
                        'name' => 'Woven Straw Clutch', 'in' => 'Bags', 'price' => 799, 'weight' => 0.3, 'stock' => 18,
                        'description' => "Hand-woven straw clutch for beach weddings and summer outings.\n\n• Fold-over flap\n• Lined inside",
                        'photos' => ['straw-clutch.jpg', 'straw-clutch-2.jpg'],
                    ],
                    [
                        'name' => 'Vintage Leather Messenger', 'in' => 'Bags', 'price' => 1099, 'weight' => 0.9, 'stock' => 12,
                        'description' => "Rustic brown leather messenger bag that only gets better with age.\n\n• Two front buckle pockets\n• Padded shoulder strap",
                        'photos' => ['messenger.jpg', 'messenger-2.jpg'],
                    ],
                    [
                        'name' => 'Canvas Everyday Tote', 'in' => 'Bags', 'price' => 349, 'weight' => 0.2, 'stock' => 60,
                        'description' => "Plain, sturdy canvas tote for groceries, books or the gym. Reusable and easy to wash.\n\n• Long handles fit over the shoulder",
                        'photos' => ['canvas-tote-natural.jpg'],
                    ],
                ],
            ],
        ];
    }

    public function run(): void
    {
        $added = 0;

        // CATALOG_ONLY="Name, Name" seeds just those products (and only their shelves).
        $only = array_filter(array_map('trim', explode(',', (string) getenv('CATALOG_ONLY'))));

        foreach ($this->catalog() as $email => $shop) {
            if ($only) {
                $shop['products'] = array_values(array_filter($shop['products'], fn ($item) => in_array($item['name'], $only, true)));

                if (!$shop['products']) {
                    continue;
                }

                $used = array_map(fn ($item) => explode(' > ', $item['in']), $shop['products']);
                $shop['categories'] = array_filter(
                    $shop['categories'],
                    fn ($parent) => in_array($parent, array_column($used, 0), true),
                    ARRAY_FILTER_USE_KEY
                );
                foreach ($shop['categories'] as $parent => $children) {
                    $shop['categories'][$parent] = array_values(array_filter($children, fn ($child) => in_array([$parent, $child], $used, true)));
                }
            }

            $seller = User::where('email', $email)->where('role', 'seller')->first();

            if (!$seller) {
                $this->command?->warn("No seller {$email}. Run SellerShopsSeeder first.");
                continue;
            }

            $category = Categories::LIST[$this->categorySlug($seller)] ?? null;

            if (!$category) {
                $this->command?->warn("{$email} has no approved category; skipped.");
                continue;
            }

            $shelves = $this->storeCategories($seller, $shop['categories']);

            foreach ($shop['products'] as $item) {
                if (Product::where('seller_id', $seller->id)->where('name', $item['name'])->exists()) {
                    continue;
                }

                $this->addProduct($seller, $category, $shop['folder'], $shelves, $item);
                $added++;
            }
        }

        $this->command?->info("{$added} catalog products added.");
    }

    private function categorySlug(User $seller): ?string
    {
        return DB::table('seller_applications')->where('user_id', $seller->id)
            ->where('status', 'Approved')->orderByDesc('id')->value('business_category');
    }

    /** The shop's store categories (reused if they exist): "Parent" / "Parent > Child" => id. */
    private function storeCategories(User $seller, array $tree): array
    {
        $ids = [];
        $order = 0;

        foreach ($tree as $parent => $children) {
            $top = StoreCategory::firstOrCreate(
                ['seller_id' => $seller->id, 'parent_id' => null, 'name' => $parent],
                ['sort_order' => ++$order]
            );
            $ids[$parent] = $top->id;

            foreach ($children as $n => $child) {
                $ids["{$parent} > {$child}"] = StoreCategory::firstOrCreate(
                    ['seller_id' => $seller->id, 'parent_id' => $top->id, 'name' => $child],
                    ['sort_order' => $n + 1]
                )->id;
            }
        }

        return $ids;
    }

    private function addProduct(User $seller, string $category, string $folder, array $shelves, array $item): void
    {
        $options = $item['options'] ?? [];
        $types = array_keys($options);
        $photoFor = [];   // first-type value => photo file

        // First type: either a list of values or value => photo.
        $values1 = [];
        foreach ($options[$types[0] ?? ''] ?? [] as $key => $value) {
            if (is_string($key)) {
                $values1[] = $key;
                $photoFor[$this->parse($key)[0]] = $value;
            } else {
                $values1[] = $value;
            }
        }
        $values2 = isset($types[1]) ? $options[$types[1]] : [null];

        $out = $item['out'] ?? [];
        $rows = [];
        $n = 0;

        foreach ($values1 as $first) {
            [$v1, $extra1] = $this->parse($first);

            foreach ($values2 as $second) {
                [$v2, $extra2] = $second === null ? [null, 0.0] : $this->parse($second);
                $soldOut = in_array($v1, $out, true) || ($v2 !== null && in_array($v2, $out, true)) || in_array("{$v1}/{$v2}", $out, true);
                $stock = isset($item['stock']) && is_array($item['stock'])
                    ? $item['stock'][$n]
                    : self::STOCK_SPREAD[$n % count(self::STOCK_SPREAD)];
                $n++;

                $rows[] = [
                    'variation_type' => $types[0],
                    'variation_value' => $v1,
                    'option2_type' => $v2 === null ? null : $types[1],
                    'option2_value' => $v2,
                    'price_adjustment' => $extra1 + $extra2,
                    'stock' => $soldOut ? 0 : $stock,
                ];
            }
        }

        $product = Product::create([
            'seller_id' => $seller->id,
            'name' => $item['name'],
            'category' => $category,
            'brand' => $item['brand'] ?? null,
            'condition' => 'New',
            'weight_kg' => $item['weight'] ?? null,
            'specs' => $item['specs'] ?? null,
            ...Product::pricing($item['price'], $item['discount'] ?? 0),
            // With options, buyers order an option: its stock is what counts.
            'stock' => $rows ? array_sum(array_column($rows, 'stock')) : ($item['stock'] ?? 30),
            'description' => $item['description'],
        ]);

        $firstOfValue = [];
        foreach ($rows as $row) {
            $variation = ProductVariation::create(['product_id' => $product->id] + $row);
            $firstOfValue[$row['variation_value']] ??= $variation->id;
        }

        // A photo per color (on its first combination; the others borrow it), then the gallery.
        foreach ($photoFor as $value => $file) {
            $this->addPhoto($product, $folder, $file, $firstOfValue[$value] ?? null);
        }
        foreach ($item['photos'] ?? [] as $file) {
            $this->addPhoto($product, $folder, $file, null);
        }

        ProductPhotos::sync($product);
        $product->storeCategories()->sync([$shelves[$item['in']]]);
    }

    private function addPhoto(Product $product, string $folder, string $file, ?int $variationId): void
    {
        $source = database_path("seeders/catalog/{$folder}/{$file}");

        if (!is_file($source)) {
            $this->command?->warn("Missing photo {$folder}/{$file}");
            return;
        }

        // The content's fingerprint is in the name, so a replaced photo gets a new URL (no stale browser cache).
        $bytes = file_get_contents($source);
        $path = 'products/seed-' . $folder . '-' . pathinfo($file, PATHINFO_FILENAME) . '-' . substr(md5($bytes), 0, 8) . '.jpg';
        Storage::disk('public')->put($path, $bytes);

        ProductImage::create([
            'product_id' => $product->id,
            'product_variation_id' => $variationId,
            'path' => $path,
            'sort_order' => ProductPhotos::nextOrder($product, $variationId),
        ]);
    }

    /** "Name|+price" => [Name, price]. */
    private function parse(string $value): array
    {
        return str_contains($value, '|+')
            ? [explode('|+', $value)[0], (float) explode('|+', $value)[1]]
            : [$value, 0.0];
    }
}
