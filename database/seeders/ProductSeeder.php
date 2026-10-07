<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Support\Categories;
use Illuminate\Database\Seeder;

/**
 * Sample products for a demo or system check: four per shop from
 * SellerShopsSeeder, each in that shop's registered category. Some have
 * Color × Size options, some a single option, some a discount.
 *
 * Safe to run again: a product that already exists (same shop, same name)
 * is left alone. No photos — the shop shows the category icon until the
 * seller adds one.
 *
 *   php artisan db:seed --class=SellerShopsSeeder   (if the shops aren't there yet)
 *   php artisan db:seed --class=ProductSeeder
 */
class ProductSeeder extends Seeder
{
    /**
     * category => [name, price, stock (no options), discount %, options, description]
     * options: ['Type' => [values]] or ['Color' => [...], 'Size' => [...]] (every
     * combination); a value may be "Name|+price" for an extra charge.
     */
    private const PRODUCTS = [
        'electronics' => [
            ['Wireless Earbuds Pro', 1899, 0, 15, ['Color' => ['Black', 'White']], 'Bluetooth 5.3 earbuds with noise cancelling, 30-hour battery with the case, and USB-C fast charging.'],
            ['20,000mAh Power Bank', 1299, 40, 0, [], 'Charges two devices at once with 22.5W fast charging. Slim enough for a bag pocket.'],
            ['Smartwatch Fit 2', 2499, 0, 10, ['Color' => ['Midnight Black', 'Rose Pink', 'Silver']], 'Heart rate, sleep and step tracking, 7-day battery, and message alerts from your phone.'],
            ['USB-C Fast Charger 30W', 499, 75, 0, [], 'Compact wall charger for phones and tablets, with a 1-meter USB-C cable.'],
        ],
        'womens-fashion' => [
            ['Floral Wrap Dress', 899, 0, 20, ['Color' => ['Blush', 'Navy'], 'Size' => ['S', 'M', 'L']], 'Lightweight wrap dress with a tie waist. Breathable fabric for warm days.'],
            ['High-Waist Wide Leg Pants', 749, 0, 0, ['Color' => ['Beige', 'Black'], 'Size' => ['S', 'M', 'L', 'XL']], 'Flowy wide-leg pants with an elastic back waist and side pockets.'],
            ['Knit Cardigan', 650, 0, 0, ['Color' => ['Cream', 'Sage', 'Mocha']], 'Soft knit cardigan with button front — easy to layer over dresses and tops.'],
            ['Woven Tote Bag', 549, 25, 10, [], 'Roomy woven tote with an inner zip pocket. Fits a laptop up to 14 inches.'],
        ],
        'mens-fashion' => [
            ['Classic Oxford Shirt', 799, 0, 0, ['Color' => ['White', 'Light Blue'], 'Size' => ['M', 'L', 'XL']], 'Long-sleeve cotton Oxford shirt with a button-down collar. Office or casual.'],
            ['Slim Fit Chino Pants', 899, 0, 15, ['Color' => ['Khaki', 'Navy', 'Olive'], 'Size' => ['30', '32', '34']], 'Stretch cotton chinos with a slim, tapered fit.'],
            ['Basic Crew Neck Tee (3-Pack)', 599, 0, 0, ['Size' => ['S', 'M', 'L', 'XL|+50']], 'Three plain cotton tees in black, white and gray.'],
            ['Leather Belt', 450, 30, 0, [], 'Genuine leather belt with a brushed metal buckle. 3.5 cm wide.'],
        ],
        'kids-baby' => [
            ['Baby Cotton Onesie (5-Pack)', 699, 0, 10, ['Size' => ['0-3 months', '3-6 months', '6-12 months']], 'Soft cotton onesies with snap buttons, gentle on baby skin.'],
            ['Kids Rain Boots', 549, 0, 0, ['Color' => ['Yellow', 'Blue'], 'Size' => ['Size 8', 'Size 10', 'Size 12']], 'Waterproof rubber boots with easy pull-on handles.'],
            ['Sippy Cup with Handles', 249, 60, 0, [], 'Spill-proof sippy cup, BPA-free, for 6 months and up.'],
            ['Baby Carrier Wrap', 1199, 15, 20, [], 'Breathable stretchy wrap for newborns up to 15 kg.'],
        ],
        'home-living' => [
            ['Cotton Bed Sheet Set', 1299, 0, 15, ['Size' => ['Single', 'Double|+200', 'Queen|+400']], 'Fitted sheet, flat sheet and two pillowcases in 100% cotton.'],
            ['Ceramic Dinner Plate Set (6 pcs)', 899, 20, 0, [], 'Matte ceramic plates, microwave and dishwasher safe.'],
            ['Blackout Curtains', 799, 0, 0, ['Color' => ['Gray', 'Navy', 'Beige']], 'Two thermal blackout panels that keep rooms cool and dark.'],
            ['Scented Soy Candle', 299, 50, 10, [], 'Hand-poured soy candle in lavender and vanilla, about 40 hours of burn time.'],
        ],
        'sports-outdoors' => [
            ['Yoga Mat 6mm', 699, 0, 0, ['Color' => ['Purple', 'Teal', 'Black']], 'Non-slip, cushioned TPE yoga mat with a carrying strap.'],
            ['Insulated Water Bottle 1L', 599, 0, 15, ['Color' => ['Black', 'White', 'Sky Blue']], 'Keeps drinks cold for 24 hours or hot for 12. Leak-proof lid.'],
            ['Adjustable Dumbbells (Pair)', 2499, 10, 0, [], 'Two dumbbells adjustable from 2 to 10 kg each.'],
            ['Running Shorts', 449, 0, 0, ['Size' => ['S', 'M', 'L']], 'Quick-dry shorts with an inner liner and a zip pocket.'],
        ],
        'beauty-personal-care' => [
            ['Hydrating Facial Cleanser', 349, 80, 0, [], 'Gentle gel cleanser with hyaluronic acid for all skin types. 150 ml.'],
            ['Matte Lip Tint', 199, 0, 10, ['Shade' => ['Rose', 'Coral', 'Berry', 'Nude']], 'Lightweight, long-wearing lip tint that also works as a blush.'],
            ['Sunscreen SPF 50', 449, 60, 0, [], 'Non-greasy daily sunscreen, PA++++, 50 ml.'],
            ['Hair Serum', 299, 40, 20, [], 'Argan oil serum that tames frizz and adds shine. 100 ml.'],
        ],
        'food-beverages' => [
            ['Premium Barako Coffee Beans 500g', 450, 0, 0, ['Grind' => ['Whole Bean', 'Fine Grind', 'Coarse Grind']], 'Strong, full-bodied Batangas barako coffee, roasted weekly.'],
            ['Dried Mangoes 200g', 159, 120, 10, [], 'Sweet dried mangoes from Cebu.'],
            ['Assorted Polvoron (24 pcs)', 249, 0, 0, ['Flavor' => ['Classic', 'Cookies & Cream', 'Ube']], 'Individually wrapped polvoron — good for pasalubong.'],
            ['Organic Honey 350g', 389, 35, 0, [], 'Raw wildflower honey from local beekeepers.'],
        ],
        'automotive' => [
            ['Car Phone Holder', 349, 70, 0, [], 'Dashboard and air-vent phone holder with one-hand release.'],
            ['Microfiber Car Towels (5 pcs)', 299, 90, 15, [], 'Lint-free towels for washing, drying and polishing.'],
            ['Car Seat Covers', 1899, 0, 10, ['Color' => ['Black', 'Black & Red', 'Gray']], 'Full set of breathable seat covers for 5-seater cars.'],
            ['Tire Pressure Gauge', 249, 45, 0, [], 'Digital gauge, reads up to 150 PSI, with a backlit screen.'],
        ],
        'office-school' => [
            ['A5 Dotted Notebook', 189, 0, 0, ['Color' => ['Black', 'Sage', 'Terracotta']], '160 pages of 100gsm dotted paper with a lay-flat binding.'],
            ['Gel Pens (12 Colors)', 159, 100, 10, [], 'Smooth 0.5 mm gel pens in 12 colors.'],
            ['Ergonomic Office Chair', 4999, 8, 15, [], 'Mesh-back chair with lumbar support and adjustable height.'],
            ['Desk Organizer', 399, 30, 0, [], 'Wooden organizer with five compartments and a phone slot.'],
        ],
        'pet-supplies' => [
            ['Premium Dog Food 3kg', 899, 0, 0, ['Flavor' => ['Chicken', 'Beef', 'Lamb & Rice']], 'Complete nutrition for adult dogs of all sizes.'],
            ['Cat Scratching Post', 699, 15, 10, [], 'Sisal-wrapped scratching post with a hanging toy. 60 cm tall.'],
            ['Pet Bed', 999, 0, 0, ['Size' => ['Small', 'Medium|+200', 'Large|+400']], 'Washable, cushioned bed for cats and dogs.'],
            ['Retractable Leash 5m', 449, 40, 0, [], 'One-button brake and lock, for pets up to 25 kg.'],
        ],
        'toys-games-hobbies' => [
            ['Building Blocks Set (500 pcs)', 999, 25, 20, [], 'Classic building bricks with a storage box. For ages 6 and up.'],
            ['Remote Control Car', 1299, 0, 0, ['Color' => ['Red', 'Blue']], 'Rechargeable 4WD RC car that climbs small slopes.'],
            ['1000-Piece Jigsaw Puzzle', 549, 20, 0, [], 'Landscape puzzle of the Banaue Rice Terraces.'],
            ['Watercolor Paint Set', 399, 35, 10, [], '24 colors with two brushes and a mixing palette.'],
        ],
        'jewelry-accessories' => [
            ['Sterling Silver Pendant Necklace', 1299, 0, 15, ['Length' => ['16 inches', '18 inches']], '925 sterling silver chain with a small heart pendant.'],
            ['Pearl Stud Earrings', 899, 30, 0, [], 'Freshwater pearl studs with sterling silver posts.'],
            ['Minimalist Watch', 1599, 0, 10, ['Color' => ['Gold', 'Silver', 'Rose Gold']], 'Slim stainless steel watch with a mesh strap. Water-resistant.'],
            ['Beaded Bracelet Set', 349, 50, 0, [], 'Set of three stretchy beaded bracelets.'],
        ],
        'shoes' => [
            ['Everyday White Sneakers', 1499, 0, 20, ['Color' => ['White', 'White & Black'], 'Size' => ['38', '39', '40', '41', '42']], 'Clean, comfortable sneakers with a cushioned insole.'],
            ['Running Shoes', 2199, 0, 0, ['Color' => ['Black', 'Gray'], 'Size' => ['40', '41', '42', '43']], 'Lightweight mesh running shoes with good grip.'],
            ['Leather Loafers', 1799, 0, 10, ['Size' => ['40', '41', '42', '43']], 'Genuine leather slip-on loafers for work or events.'],
            ['Comfort Slides', 399, 0, 0, ['Color' => ['Black', 'Beige'], 'Size' => ['37', '39', '41', '43']], 'Soft, water-friendly slides for home or the beach.'],
        ],
        'tools-home-improvement' => [
            ['Cordless Drill Set', 2899, 12, 15, [], '12V cordless drill with two batteries, charger and 20 bits.'],
            ['Tool Box (82 pcs)', 1499, 15, 0, [], 'Household tool set: hammer, pliers, screwdrivers, wrenches and more.'],
            ['LED Bulb 9W (4-Pack)', 299, 0, 0, ['Light' => ['Warm White', 'Daylight']], 'Energy-saving E27 LED bulbs, 900 lumens each.'],
            ['Measuring Tape 5m', 149, 80, 0, [], 'Steel tape with an auto-lock and a belt clip.'],
        ],
        'garden-outdoor' => [
            ['Ceramic Plant Pot', 349, 0, 10, ['Size' => ['Small', 'Medium|+100', 'Large|+250']], 'Glazed ceramic pot with a drainage hole and saucer.'],
            ['Garden Hose 15m', 799, 20, 0, [], 'Kink-resistant hose with a 7-pattern spray nozzle.'],
            ['Solar Garden Lights (6 pcs)', 699, 25, 20, [], 'Weatherproof stake lights that charge by day and turn on at dusk.'],
            ['Organic Potting Mix 5kg', 249, 60, 0, [], 'Ready-to-use soil for herbs, vegetables and houseplants.'],
        ],
    ];

    public function run(): void
    {
        $created = 0;
        $missing = [];

        foreach (self::PRODUCTS as $slug => $products) {
            $seller = $this->sellerFor($slug);

            if (!$seller) {
                $missing[] = Categories::LIST[$slug];
                continue;
            }

            foreach ($products as [$name, $price, $stock, $discount, $options, $description]) {
                if (Product::where('seller_id', $seller->id)->where('name', $name)->exists()) {
                    continue;
                }

                $variations = $this->combinations($options);

                $product = Product::create([
                    'seller_id' => $seller->id,
                    'name' => $name,
                    'category' => Categories::LIST[$slug],
                    ...Product::pricing($price, $discount),
                    // With options, buyers order an option: its stock is what counts.
                    'stock' => $variations ? array_sum(array_column($variations, 'stock')) : $stock,
                    'description' => $description,
                ]);

                foreach ($variations as $variation) {
                    ProductVariation::create(['product_id' => $product->id] + $variation);
                }

                $created++;
            }
        }

        $this->command?->info("{$created} sample products added.");

        if ($missing) {
            $this->command?->warn('No shop for: ' . implode(', ', $missing) . '. Run SellerShopsSeeder first.');
        }
    }

    /** The approved seller registered for this category (SellerShopsSeeder's shop first). */
    private function sellerFor(string $slug): ?User
    {
        return User::where('role', 'seller')
            ->where('status', 'Active')
            ->whereIn('id', fn ($q) => $q->select('user_id')->from('seller_applications')
                ->where('status', 'Approved')
                ->where('business_category', $slug))
            ->orderByRaw("CASE WHEN email LIKE '%@boombuy.test' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }

    /** Every option (or Color × Size combination), with its extra price and some stock. */
    private function combinations(array $options): array
    {
        if (!$options) {
            return [];
        }

        $parse = fn (string $value) => str_contains($value, '|+')
            ? [explode('|+', $value)[0], (float) explode('|+', $value)[1]]
            : [$value, 0.0];

        $types = array_keys($options);
        $rows = [];
        $n = 0;

        foreach ($options[$types[0]] as $first) {
            [$value1, $extra1] = $parse($first);

            foreach (isset($types[1]) ? $options[$types[1]] : [null] as $second) {
                [$value2, $extra2] = $second === null ? [null, 0.0] : $parse($second);

                $rows[] = [
                    'variation_type' => $types[0],
                    'variation_value' => $value1,
                    'option2_type' => $second === null ? null : $types[1],
                    'option2_value' => $value2,
                    'price_adjustment' => $extra1 + $extra2,
                    // A spread of stock levels, including a few low ones.
                    'stock' => [12, 8, 20, 4, 15, 10][$n++ % 6],
                ];
            }
        }

        return $rows;
    }
}
