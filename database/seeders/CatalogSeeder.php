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
                        'photos' => ['pulse-pro-max.jpg'],
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
                    ],
                    [
                        'name' => '18W Charger + Cable Bundle', 'in' => 'Chargers & Cables', 'price' => 549, 'discount' => 10, 'weight' => 0.2, 'stock' => 40,
                        'description' => "Everything in one box: an 18W fast wall charger and a 1m charging cable.\n\n• Ideal replacement or spare set\n• Comes in a gift-ready box",
                        'photos' => ['charger-bundle.jpg'],
                    ],
                    [
                        'name' => 'USB-C Braided Cable', 'in' => 'Chargers & Cables', 'price' => 249, 'weight' => 0.08,
                        'description' => "Tough nylon-braided USB-C cable that survives daily bending. Supports fast charging up to 60W and data transfer.\n\n• Aluminum connector heads\n• 10,000+ bend lifespan",
                        'options' => [
                            'Color' => ['Black' => 'cable-black.jpg', 'White' => 'cable-white.jpg', 'Red' => 'cable-red.jpg'],
                            'Length' => ['1m', '2m|+50'],
                        ],
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
                        'photos' => ['wireless-pad.jpg'],
                    ],
                    [
                        'name' => 'Wireless Earbuds X2 (ANC)', 'in' => 'Audio > Earbuds & Headphones', 'price' => 1499, 'discount' => 15, 'weight' => 0.1,
                        'description' => "True wireless earbuds with active noise cancelling for commutes and calls.\n\n• Up to 30 hours with the case\n• Touch controls, Bluetooth 5.3\n• Sweat resistant",
                        'options' => ['Color' => ['White' => 'earbuds-white.jpg', 'Black' => 'earbuds-black.jpg']],
                    ],
                    [
                        'name' => 'Over-Ear Bluetooth Headphones', 'in' => 'Audio > Earbuds & Headphones', 'price' => 1899, 'weight' => 0.3,
                        'description' => "Comfortable over-ear headphones with deep bass and soft cushions for long listening.\n\n• 40 hours of playtime\n• Foldable, with a 3.5mm cable for wired use",
                        'options' => ['Color' => ['Black' => 'headphones-black.jpg', 'Brown' => 'headphones-brown.jpg', 'Pink' => 'headphones-pink.jpg']],
                    ],
                    [
                        'name' => 'Wired Earphones with Mic', 'in' => 'Audio > Earbuds & Headphones', 'price' => 199, 'weight' => 0.05, 'stock' => 120,
                        'description' => "Classic in-ear earphones with a built-in mic and play/pause button.\n\n• 3.5mm plug\n• Tangle-resistant cable",
                        'photos' => ['wired-earphones.jpg'],
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
                        'options' => [
                            'Color' => ['Black' => 'graphic-tee-black.jpg', 'White' => 'graphic-tee-white.jpg'],
                            'Size' => ['M', 'L', 'XL'],
                        ],
                    ],
                    [
                        'name' => 'Floral Midi Dress', 'in' => 'Dresses', 'price' => 899, 'discount' => 10, 'weight' => 0.3,
                        'description' => "Flowy midi dress in a soft floral print, perfect for brunches, beach trips and family events.\n\n• Lightweight chiffon\n• Fully lined",
                        'options' => [
                            'Color' => ['White' => 'floral-dress-white.jpg', 'Blue' => 'floral-dress-blue.jpg', 'Teal' => 'floral-dress-teal.jpg'],
                            'Size' => ['S', 'M', 'L', 'XL'],
                        ],
                    ],
                    [
                        'name' => 'Satin Slip Dress', 'in' => 'Dresses', 'price' => 799, 'weight' => 0.25,
                        'description' => "Silky satin slip dress with adjustable straps and a cowl neckline. Made for date nights and parties.\n\n• Midi length\n• Bias cut that flatters every shape",
                        'options' => [
                            'Color' => ['Black' => 'satin-slip-black.jpg', 'Champagne' => 'satin-slip-champagne.jpg', 'Emerald' => 'satin-slip-emerald.jpg'],
                            'Size' => ['S', 'M', 'L'],
                        ],
                    ],
                    [
                        'name' => 'Classic A-Line Dress', 'in' => 'Dresses', 'price' => 649, 'weight' => 0.3,
                        'description' => "The little dress that goes everywhere: fitted top, V-neck and a twirly A-line skirt.\n\n• Thin belt included\n• Knee length",
                        'options' => [
                            'Color' => ['Black' => 'aline-dress-black.jpg', 'Wine' => 'aline-dress-wine.jpg'],
                            'Size' => ['S', 'M', 'L'],
                        ],
                    ],
                    [
                        'name' => 'Smocked Sundress', 'in' => 'Dresses', 'price' => 699, 'weight' => 0.3,
                        'description' => "Breezy sundress with a smocked top and a full, swishy skirt. Your go-to for summer.\n\n• Cotton voile\n• Midi length",
                        'options' => [
                            'Color' => ['White' => 'sundress-white.jpg', 'Yellow' => 'sundress-yellow.jpg', 'Blue' => 'sundress-blue.jpg'],
                            'Size' => ['S', 'M', 'L'],
                        ],
                    ],
                    [
                        'name' => 'Linen Shirt Dress', 'in' => 'Dresses', 'price' => 999, 'weight' => 0.35,
                        'description' => "Long, relaxed linen shirt dress. Simple, comfortable and easy to style.\n\n• Linen-cotton blend\n• Side pockets",
                        'options' => [
                            'Color' => ['Beige' => 'shirt-dress-beige.jpg', 'Black' => 'shirt-dress-black.jpg', 'Red' => 'shirt-dress-red.jpg'],
                            'Size' => ['S', 'M', 'L', 'XL'],
                        ],
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
                        'name' => 'Woven Straw Clutch', 'in' => 'Bags', 'price' => 799, 'weight' => 0.3,
                        'description' => "Hand-woven straw clutch for beach weddings and summer outings.\n\n• Fold-over flap\n• Lined inside",
                        'options' => ['Color' => ['White' => 'straw-clutch-white.jpg', 'Gold' => 'straw-clutch-gold.jpg', 'Black' => 'straw-clutch-black.jpg']],
                    ],
                    [
                        'name' => 'Vintage Leather Messenger', 'in' => 'Bags', 'price' => 1099, 'weight' => 0.9,
                        'description' => "Rustic leather messenger bag that only gets better with age.\n\n• Two front buckle pockets\n• Padded shoulder strap",
                        'options' => ['Color' => ['Brown' => 'messenger-brown.jpg', 'Black' => 'messenger-black.jpg']],
                    ],
                    [
                        'name' => 'Canvas Everyday Tote', 'in' => 'Bags', 'price' => 349, 'weight' => 0.2, 'stock' => 60,
                        'description' => "Sturdy olive canvas tote with a long strap, for groceries, books or the gym. Reusable and easy to wash.\n\n• Fits over the shoulder or across the body",
                        'photos' => ['canvas-tote-olive.jpg'],
                    ],
                ],
            ],

            'gentryandco@boombuy.test' => [
                'folder' => 'gentryandco',
                'categories' => ['Shirts' => [], 'Pants & Shorts' => [], 'Outerwear' => []],
                'products' => [
                    [
                        'name' => 'Oxford Button-Down Shirt', 'in' => 'Shirts', 'price' => 899, 'discount' => 10, 'weight' => 0.3,
                        'description' => "Smart long-sleeve Oxford shirt that works for the office and weekends.\n\n• 100% cotton Oxford weave\n• Button-down collar, regular fit",
                        'options' => [
                            'Color' => ['White' => 'oxford-shirt-white.jpg', 'Light Blue' => 'oxford-shirt-light-blue.jpg', 'Pink' => 'oxford-shirt-pink.jpg'],
                            'Size' => ['S', 'M', 'L', 'XL|+50'],
                        ],
                    ],
                    [
                        'name' => 'Classic Polo Shirt', 'in' => 'Shirts', 'price' => 549, 'weight' => 0.25,
                        'description' => "Breathable piqué polo with a clean, classic look.\n\n• Cotton piqué knit\n• Two-button placket, ribbed collar",
                        'options' => [
                            'Color' => ['Black' => 'polo-shirt-black.jpg', 'White' => 'polo-shirt-white.jpg', 'Navy' => 'polo-shirt-navy.jpg', 'Red' => 'polo-shirt-red.jpg'],
                            'Size' => ['S', 'M', 'L', 'XL'],
                        ],
                    ],
                    [
                        'name' => 'Plain Crew Neck T-Shirt', 'in' => 'Shirts', 'price' => 249, 'weight' => 0.2,
                        'description' => "Everyday 100% cotton crew-neck tee. Soft, thick and keeps its shape after washing.\n\n• Regular fit",
                        'options' => [
                            'Color' => ['White' => 'crew-tee-white.jpg', 'Black' => 'crew-tee-black.jpg', 'Grey' => 'crew-tee-grey.jpg', 'Olive' => 'crew-tee-olive.jpg'],
                            'Size' => ['S', 'M', 'L', 'XL'],
                        ],
                    ],
                    [
                        'name' => 'Linen Short Sleeve Shirt', 'in' => 'Shirts', 'price' => 699, 'weight' => 0.25,
                        'description' => "Cool, airy linen-blend shirt made for hot days and beach trips.\n\n• Camp collar\n• Relaxed fit",
                        'options' => [
                            'Color' => ['White' => 'linen-shirt-white.jpg', 'Beige' => 'linen-shirt-beige.jpg', 'Sage' => 'linen-shirt-sage.jpg'],
                            'Size' => ['S', 'M', 'L', 'XL'],
                        ],
                    ],
                    [
                        'name' => 'Barong Tagalog (Jusi)', 'in' => 'Shirts', 'price' => 1899, 'weight' => 0.3,
                        'description' => "Classic jusi barong with hand-finished embroidery for weddings and formal events.\n\n• Wear with a white undershirt\n• Dry clean recommended",
                        'options' => ['Size' => ['S', 'M', 'L', 'XL|+100']],
                        'photos' => ['barong.jpg'],
                    ],
                    [
                        'name' => 'Slim Fit Chino Pants', 'in' => 'Pants & Shorts', 'price' => 799, 'discount' => 15, 'weight' => 0.5,
                        'description' => "Stretch cotton chinos with a modern slim cut.\n\n• Fits office and casual looks\n• Four pockets",
                        'options' => [
                            'Color' => ['Khaki' => 'chino-pants-khaki.jpg', 'Navy' => 'chino-pants-navy.jpg', 'Black' => 'chino-pants-black.jpg'],
                            'Size' => ['30', '32', '34', '36'],
                        ],
                    ],
                    [
                        'name' => 'Dark Wash Slim Jeans', 'in' => 'Pants & Shorts', 'price' => 999, 'weight' => 0.6,
                        'description' => "Dark indigo slim jeans with a bit of stretch for all-day comfort.\n\n• Five-pocket style\n• Zip fly",
                        'options' => ['Size' => ['30', '32', '34', '36']],
                        'photos' => ['jeans.jpg'],
                    ],
                    [
                        'name' => 'Jogger Pants', 'in' => 'Pants & Shorts', 'price' => 499, 'weight' => 0.4,
                        'description' => "Soft fleece joggers with an elastic waist and cuffed ankles.\n\n• Side zip pockets\n• Drawstring waist",
                        'options' => [
                            'Color' => ['Black' => 'jogger-pants-black.jpg', 'Grey' => 'jogger-pants-grey.jpg'],
                            'Size' => ['S', 'M', 'L', 'XL'],
                        ],
                    ],
                    [
                        'name' => 'Cargo Shorts', 'in' => 'Pants & Shorts', 'price' => 449, 'weight' => 0.35,
                        'description' => "Durable cotton twill cargo shorts with plenty of pockets.\n\n• Above-the-knee length\n• Two flap cargo pockets",
                        'options' => [
                            'Color' => ['Khaki' => 'cargo-shorts-khaki.jpg', 'Olive' => 'cargo-shorts-olive.jpg', 'Black' => 'cargo-shorts-black.jpg'],
                            'Size' => ['30', '32', '34', '36'],
                        ],
                    ],
                    [
                        'name' => 'Formal Slacks', 'in' => 'Pants & Shorts', 'price' => 899, 'weight' => 0.45,
                        'description' => "Wrinkle-resistant slacks with a sharp crease for work and events.\n\n• Flat front\n• Hook-and-bar closure",
                        'options' => [
                            'Color' => ['Black' => 'slacks-black.jpg', 'Navy' => 'slacks-navy.jpg'],
                            'Size' => ['30', '32', '34', '36'],
                        ],
                    ],
                    [
                        'name' => 'Denim Jacket', 'in' => 'Outerwear', 'price' => 1299, 'weight' => 0.8,
                        'description' => "Classic blue denim trucker jacket that goes with almost everything.\n\n• Button front\n• Two chest pockets",
                        'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                        'photos' => ['denim-jacket.jpg'],
                    ],
                    [
                        'name' => 'Zip-Up Hoodie', 'in' => 'Outerwear', 'price' => 799, 'discount' => 10, 'weight' => 0.6,
                        'description' => "Cozy fleece-lined hoodie with a full zip, great for aircon rooms and rainy days.\n\n• Kangaroo pockets\n• Drawstring hood",
                        'options' => [
                            'Color' => ['Black' => 'hoodie-black.jpg', 'Grey' => 'hoodie-grey.jpg', 'Navy' => 'hoodie-navy.jpg'],
                            'Size' => ['S', 'M', 'L', 'XL'],
                        ],
                    ],
                    [
                        'name' => 'Bomber Jacket', 'in' => 'Outerwear', 'price' => 1499, 'weight' => 0.7,
                        'description' => "Lightweight bomber jacket with ribbed cuffs and hem.\n\n• Water-repellent shell\n• Inner pocket",
                        'options' => [
                            'Color' => ['Black' => 'bomber-jacket-black.jpg', 'Olive' => 'bomber-jacket-olive.jpg'],
                            'Size' => ['S', 'M', 'L', 'XL'],
                        ],
                    ],
                    [
                        'name' => 'Windbreaker Jacket', 'in' => 'Outerwear', 'price' => 999, 'weight' => 0.4,
                        'description' => "Packable windbreaker that blocks wind and light rain.\n\n• Hood tucks into the collar\n• Zip pockets",
                        'options' => [
                            'Color' => ['Black' => 'windbreaker-black.jpg', 'Navy' => 'windbreaker-navy.jpg'],
                            'Size' => ['S', 'M', 'L', 'XL'],
                        ],
                    ],
                    [
                        'name' => 'Knit Cardigan', 'in' => 'Outerwear', 'price' => 899, 'weight' => 0.5,
                        'description' => "Soft knit cardigan to layer over a tee or polo.\n\n• Button front\n• Ribbed cuffs and hem",
                        'options' => [
                            'Color' => ['Grey' => 'cardigan-grey.jpg', 'Brown' => 'cardigan-brown.jpg'],
                            'Size' => ['S', 'M', 'L', 'XL'],
                        ],
                    ],
                ],
            ],

            'littlehaven@boombuy.test' => [
                'folder' => 'littlehaven',
                'categories' => ['Baby Clothing' => [], 'Feeding' => [], 'Diapering & Bath' => []],
                'products' => [
                    [
                        'name' => 'Cotton Baby Onesie Set', 'in' => 'Baby Clothing', 'price' => 499, 'discount' => 10, 'weight' => 0.3,
                        'description' => "Set of soft cotton onesies with snap buttons for easy diaper changes.\n\n• Gentle on baby's skin\n• Machine washable",
                        'options' => ['Size' => ['0-3M', '3-6M', '6-12M']],
                        'photos' => ['onesie-set.jpg'],
                    ],
                    [
                        'name' => 'Baby Romper', 'in' => 'Baby Clothing', 'price' => 349, 'weight' => 0.15,
                        'description' => "Cute, breathable romper for everyday wear and photo shoots.\n\n• Soft cotton\n• Snap closure",
                        'options' => [
                            'Color' => ['Pink' => 'romper-pink.jpg', 'Blue' => 'romper-blue.jpg', 'Yellow' => 'romper-yellow.jpg'],
                            'Size' => ['0-3M', '3-6M', '6-12M'],
                        ],
                    ],
                    [
                        'name' => 'Baby Mittens & Booties Set', 'in' => 'Baby Clothing', 'price' => 199, 'weight' => 0.1,
                        'description' => "Keeps tiny hands and feet warm and stops scratching.\n\n• Soft cotton knit\n• Fits 0-6 months",
                        'options' => ['Color' => ['Pink' => 'mittens-booties-pink.jpg', 'Blue' => 'mittens-booties-blue.jpg']],
                    ],
                    [
                        'name' => 'Swaddle Blanket', 'in' => 'Baby Clothing', 'price' => 299, 'weight' => 0.2, 'stock' => 40,
                        'description' => "Light muslin swaddle that helps newborns sleep snug and calm.\n\n• 120 × 120 cm\n• Gets softer with every wash",
                        'photos' => ['swaddle.jpg'],
                    ],
                    [
                        'name' => 'Baby Bib Set', 'in' => 'Baby Clothing', 'price' => 249, 'weight' => 0.15, 'stock' => 50,
                        'description' => "Set of waterproof-backed bibs for mealtime and drooling.\n\n• Adjustable snap\n• Easy to wipe clean",
                        'photos' => ['bib-set.jpg'],
                    ],
                    [
                        'name' => 'Anti-Colic Baby Bottle', 'in' => 'Feeding', 'price' => 399, 'weight' => 0.15, 'stock' => 45,
                        'description' => "Anti-colic vent helps reduce gas and fussiness during feeding.\n\n• BPA-free\n• Soft silicone nipple",
                        'photos' => ['baby-bottle.jpg'],
                    ],
                    [
                        'name' => 'Silicone Feeding Set', 'in' => 'Feeding', 'price' => 599, 'discount' => 12, 'weight' => 0.4,
                        'description' => "Suction plate, bowl and spoon set made for baby-led weaning.\n\n• Food-grade silicone\n• Dishwasher and microwave safe",
                        'options' => ['Color' => ['Pink' => 'feeding-set-pink.jpg', 'Blue' => 'feeding-set-blue.jpg', 'Green' => 'feeding-set-green.jpg']],
                    ],
                    [
                        'name' => 'Sippy Cup', 'in' => 'Feeding', 'price' => 249, 'weight' => 0.15,
                        'description' => "Spill-proof sippy cup with easy-grip handles for toddlers.\n\n• Soft spout\n• BPA-free",
                        'options' => ['Color' => ['Pink' => 'sippy-cup-pink.jpg', 'Blue' => 'sippy-cup-blue.jpg']],
                    ],
                    [
                        'name' => 'Electric Breast Pump', 'in' => 'Feeding', 'price' => 2499, 'weight' => 0.8, 'stock' => 15,
                        'description' => "Quiet electric breast pump with adjustable suction levels.\n\n• Rechargeable\n• Comes with a bottle",
                        'photos' => ['breast-pump.jpg'],
                    ],
                    [
                        'name' => 'Bottle Sterilizer', 'in' => 'Feeding', 'price' => 1899, 'weight' => 1.5, 'stock' => 12,
                        'description' => "Steam sterilizer that cleans up to six bottles in minutes.\n\n• Kills 99.9% of germs\n• Auto shut-off",
                        'photos' => ['sterilizer.jpg'],
                    ],
                    [
                        'name' => 'Baby Wipes (80 sheets)', 'in' => 'Diapering & Bath', 'price' => 89, 'weight' => 0.5, 'stock' => 150,
                        'description' => "Gentle, unscented wipes for diaper changes and quick clean-ups.\n\n• Alcohol-free\n• 80 thick sheets",
                        'photos' => ['baby-wipes.jpg'],
                    ],
                    [
                        'name' => 'Diaper Bag Backpack', 'in' => 'Diapering & Bath', 'price' => 1299, 'discount' => 15, 'weight' => 0.8,
                        'description' => "Roomy backpack with insulated bottle pockets and a changing mat.\n\n• Water-resistant\n• Stroller straps",
                        'options' => ['Color' => ['Black' => 'diaper-bag-black.jpg', 'Grey' => 'diaper-bag-grey.jpg', 'Pink' => 'diaper-bag-pink.jpg']],
                    ],
                    [
                        'name' => 'Baby Bathtub', 'in' => 'Diapering & Bath', 'price' => 899, 'weight' => 1.2,
                        'description' => "Sturdy baby tub with a non-slip seat for newborns to toddlers.\n\n• Drain plug\n• Easy to store",
                        'options' => ['Color' => ['Blue' => 'baby-bathtub-blue.jpg', 'Pink' => 'baby-bathtub-pink.jpg']],
                    ],
                    [
                        'name' => 'Hooded Baby Towel', 'in' => 'Diapering & Bath', 'price' => 349, 'weight' => 0.3, 'stock' => 35,
                        'description' => "Soft, absorbent hooded towel that keeps baby warm after a bath.\n\n• Cotton terry\n• 75 × 75 cm",
                        'photos' => ['hooded-towel.jpg'],
                    ],
                    [
                        'name' => 'Changing Pad', 'in' => 'Diapering & Bath', 'price' => 449, 'weight' => 0.4, 'stock' => 25,
                        'description' => "Waterproof changing pad for home or on the go.\n\n• Wipe-clean surface\n• Folds flat",
                        'photos' => ['changing-pad.jpg'],
                    ],
                ],
            ],

            'hearthstone@boombuy.test' => [
                'folder' => 'hearthstone',
                'categories' => ['Kitchen' => [], 'Bedding & Bath' => [], 'Home Decor' => []],
                'products' => [
                    [
                        'name' => 'Non-Stick Frying Pan', 'in' => 'Kitchen', 'price' => 699, 'discount' => 15, 'weight' => 1.0,
                        'description' => "Non-stick pan for eggs, fish and everyday cooking with less oil.\n\n• Works on gas and induction\n• Heat-safe handle",
                        'options' => ['Size' => ['24cm', '28cm|+150']],
                        'photos' => ['frying-pan.jpg'],
                    ],
                    [
                        'name' => 'Stainless Cookware Set', 'in' => 'Kitchen', 'price' => 2999, 'weight' => 4.5, 'stock' => 10,
                        'description' => "Stainless steel pots and pans set with glass lids.\n\n• Even-heating base\n• Dishwasher safe",
                        'photos' => ['cookware-set.jpg'],
                    ],
                    [
                        'name' => 'Ceramic Dinner Plate Set', 'in' => 'Kitchen', 'price' => 899, 'weight' => 3.0,
                        'description' => "Set of 6 ceramic dinner plates for daily meals and get-togethers.\n\n• Microwave and dishwasher safe\n• 27 cm",
                        'options' => ['Color' => ['White' => 'plate-set-white.jpg', 'Beige' => 'plate-set-beige.jpg']],
                    ],
                    [
                        'name' => 'Glass Food Containers', 'in' => 'Kitchen', 'price' => 799, 'weight' => 2.0, 'stock' => 30,
                        'description' => "Glass meal-prep containers with locking lids.\n\n• Oven, microwave and freezer safe\n• Leak-proof",
                        'photos' => ['food-containers.jpg'],
                    ],
                    [
                        'name' => 'Electric Kettle 1.7L', 'in' => 'Kitchen', 'price' => 999, 'weight' => 1.2,
                        'description' => "Fast-boil 1.7L kettle for coffee, tea and instant noodles.\n\n• Auto shut-off\n• Boil-dry protection",
                        'options' => ['Color' => ['Silver' => 'kettle-silver.jpg', 'Black' => 'kettle-black.jpg']],
                    ],
                    [
                        'name' => 'Cotton Bed Sheet Set', 'in' => 'Bedding & Bath', 'price' => 1199, 'discount' => 20, 'weight' => 1.2,
                        'description' => "Soft, breathable cotton fitted sheet with two pillowcases.\n\n• Deep pockets\n• Machine washable",
                        'options' => [
                            'Color' => ['White' => 'bed-sheet-white.jpg', 'Grey' => 'bed-sheet-grey.jpg', 'Blue' => 'bed-sheet-blue.jpg', 'Beige' => 'bed-sheet-beige.jpg'],
                            'Size' => ['Single', 'Double|+200', 'Queen|+400'],
                        ],
                    ],
                    [
                        'name' => 'Memory Foam Pillow', 'in' => 'Bedding & Bath', 'price' => 699, 'weight' => 1.0, 'stock' => 30,
                        'description' => "Contoured memory foam pillow that supports your neck all night.\n\n• Removable, washable cover",
                        'photos' => ['pillow.jpg'],
                    ],
                    [
                        'name' => 'Microfiber Blanket', 'in' => 'Bedding & Bath', 'price' => 599, 'weight' => 1.0,
                        'description' => "Light, super-soft blanket for aircon rooms and movie nights.\n\n• 150 × 200 cm\n• Does not shed",
                        'options' => ['Color' => ['Grey' => 'blanket-grey.jpg', 'Beige' => 'blanket-beige.jpg', 'Pink' => 'blanket-pink.jpg']],
                    ],
                    [
                        'name' => 'Bath Towel Set', 'in' => 'Bedding & Bath', 'price' => 799, 'weight' => 1.2,
                        'description' => "Thick, absorbent cotton towels: bath, hand and face towel.\n\n• Quick-dry\n• Soft after many washes",
                        'options' => ['Color' => ['White' => 'bath-towel-white.jpg', 'Grey' => 'bath-towel-grey.jpg', 'Blue' => 'bath-towel-blue.jpg']],
                    ],
                    [
                        'name' => 'Shower Curtain', 'in' => 'Bedding & Bath', 'price' => 399, 'weight' => 0.5, 'stock' => 25,
                        'description' => "Water-repellent shower curtain with rust-proof hooks included.\n\n• 180 × 180 cm",
                        'photos' => ['shower-curtain.jpg'],
                    ],
                    [
                        'name' => 'Scented Candle', 'in' => 'Home Decor', 'price' => 349, 'weight' => 0.4, 'stock' => 40,
                        'description' => "Soy wax candle in a glass jar with a calming scent.\n\n• Up to 40 hours burn time",
                        'photos' => ['scented-candle.jpg'],
                    ],
                    [
                        'name' => 'Ceramic Flower Vase', 'in' => 'Home Decor', 'price' => 499, 'weight' => 0.8,
                        'description' => "Minimalist ceramic vase for fresh or dried flowers.\n\n• Matte finish",
                        'options' => ['Color' => ['White' => 'vase-white.jpg', 'Beige' => 'vase-beige.jpg']],
                    ],
                    [
                        'name' => 'Wall Clock', 'in' => 'Home Decor', 'price' => 599, 'weight' => 0.7,
                        'description' => "Clean, easy-to-read wall clock with a silent sweep movement.\n\n• No ticking\n• 30 cm",
                        'options' => ['Color' => ['Black' => 'wall-clock-black.jpg', 'White' => 'wall-clock-white.jpg']],
                    ],
                    [
                        'name' => 'LED Table Lamp', 'in' => 'Home Decor', 'price' => 799, 'discount' => 10, 'weight' => 0.8, 'stock' => 20,
                        'description' => "Dimmable LED lamp for reading, studying and bedside light.\n\n• Three light colors\n• USB powered",
                        'photos' => ['table-lamp.jpg'],
                    ],
                    [
                        'name' => 'Throw Pillow Cover', 'in' => 'Home Decor', 'price' => 199, 'weight' => 0.15,
                        'description' => "Textured throw pillow cover to refresh your sofa or bed.\n\n• 45 × 45 cm\n• Hidden zipper",
                        'options' => ['Color' => ['Beige' => 'throw-pillow-beige.jpg', 'Green' => 'throw-pillow-green.jpg', 'Mustard' => 'throw-pillow-mustard.jpg']],
                    ],
                ],
            ],

            'summitactive@boombuy.test' => [
                'folder' => 'summitactive',
                'categories' => ['Fitness' => [], 'Ball Sports' => [], 'Camping & Hiking' => []],
                'products' => [
                    [
                        'name' => 'Yoga Mat', 'in' => 'Fitness', 'price' => 599, 'discount' => 15, 'weight' => 1.0,
                        'description' => "Non-slip 6mm yoga mat for yoga, pilates and home workouts.\n\n• Cushioned and light\n• Carry strap included",
                        'options' => ['Color' => ['Purple' => 'yoga-mat-purple.jpg', 'Blue' => 'yoga-mat-blue.jpg', 'Black' => 'yoga-mat-black.jpg', 'Pink' => 'yoga-mat-pink.jpg']],
                    ],
                    [
                        'name' => 'Dumbbell Set', 'in' => 'Fitness', 'price' => 1999, 'weight' => 10, 'stock' => 12,
                        'description' => "Pair of dumbbells for strength training at home.\n\n• Rubber coating protects your floor\n• Comfortable grip",
                        'options' => ['Weight' => ['2 × 3kg', '2 × 5kg|+600']],
                        'photos' => ['dumbbells.jpg'],
                    ],
                    [
                        'name' => 'Resistance Bands Set', 'in' => 'Fitness', 'price' => 399, 'weight' => 0.4, 'stock' => 40,
                        'description' => "Five resistance levels for strength, stretching and rehab.\n\n• Carry pouch included",
                        'photos' => ['resistance-bands.jpg'],
                    ],
                    [
                        'name' => 'Jump Rope', 'in' => 'Fitness', 'price' => 199, 'weight' => 0.2,
                        'description' => "Adjustable speed rope for cardio and boxing workouts.\n\n• Smooth ball-bearing handles",
                        'options' => ['Color' => ['Black' => 'jump-rope-black.jpg', 'Blue' => 'jump-rope-blue.jpg']],
                    ],
                    [
                        'name' => 'Foam Roller', 'in' => 'Fitness', 'price' => 499, 'weight' => 0.6,
                        'description' => "Textured foam roller to loosen tight muscles after training.\n\n• 33 cm, firm density",
                        'options' => ['Color' => ['Black' => 'foam-roller-black.jpg', 'Blue' => 'foam-roller-blue.jpg']],
                    ],
                    [
                        'name' => 'Basketball (Size 7)', 'in' => 'Ball Sports', 'price' => 799, 'weight' => 0.7, 'stock' => 25,
                        'description' => "Official size 7 basketball with a deep-channel grip for indoor and outdoor courts.",
                        'photos' => ['basketball.jpg'],
                    ],
                    [
                        'name' => 'Volleyball', 'in' => 'Ball Sports', 'price' => 649, 'weight' => 0.4, 'stock' => 25,
                        'description' => "Soft-touch volleyball for the beach, the gym or the barangay court.\n\n• Official size 5",
                        'photos' => ['volleyball.jpg'],
                    ],
                    [
                        'name' => 'Badminton Racket Set', 'in' => 'Ball Sports', 'price' => 899, 'discount' => 10, 'weight' => 0.6, 'stock' => 20,
                        'description' => "Two lightweight rackets with shuttlecocks and a carry bag.\n\n• Ready to play",
                        'photos' => ['badminton-set.jpg'],
                    ],
                    [
                        'name' => 'Soccer Ball', 'in' => 'Ball Sports', 'price' => 599, 'weight' => 0.45, 'stock' => 25,
                        'description' => "Durable stitched soccer ball for training and matches.\n\n• Size 5",
                        'photos' => ['soccer-ball.jpg'],
                    ],
                    [
                        'name' => 'Table Tennis Paddle Set', 'in' => 'Ball Sports', 'price' => 499, 'weight' => 0.4, 'stock' => 20,
                        'description' => "Two paddles and three balls for home or office ping-pong.",
                        'photos' => ['ping-pong-set.jpg'],
                    ],
                    [
                        'name' => '2-Person Camping Tent', 'in' => 'Camping & Hiking', 'price' => 1999, 'discount' => 15, 'weight' => 2.5,
                        'description' => "Easy-setup dome tent for two, with a rain fly.\n\n• Sets up in 10 minutes\n• Carry bag included",
                        'options' => ['Color' => ['Green' => 'tent-green.jpg', 'Orange' => 'tent-orange.jpg']],
                    ],
                    [
                        'name' => 'Hiking Backpack 40L', 'in' => 'Camping & Hiking', 'price' => 1499, 'weight' => 1.2,
                        'description' => "40L backpack for day hikes and weekend trips.\n\n• Padded back and hip belt\n• Rain cover",
                        'options' => ['Color' => ['Black' => 'hiking-backpack-black.jpg', 'Green' => 'hiking-backpack-green.jpg', 'Red' => 'hiking-backpack-red.jpg']],
                    ],
                    [
                        'name' => 'Insulated Water Bottle', 'in' => 'Camping & Hiking', 'price' => 599, 'weight' => 0.4,
                        'description' => "Keeps drinks cold for 24 hours or hot for 12.\n\n• Stainless steel\n• Leak-proof cap",
                        'options' => [
                            'Color' => ['Black' => 'water-bottle-black.jpg', 'White' => 'water-bottle-white.jpg', 'Blue' => 'water-bottle-blue.jpg', 'Pink' => 'water-bottle-pink.jpg'],
                            'Size' => ['500ml', '750ml|+100'],
                        ],
                    ],
                    [
                        'name' => 'Camping Lantern', 'in' => 'Camping & Hiking', 'price' => 449, 'weight' => 0.4, 'stock' => 30,
                        'description' => "Bright LED lantern for camping and brownouts.\n\n• Rechargeable\n• Hanging hook",
                        'photos' => ['lantern.jpg'],
                    ],
                    [
                        'name' => 'Sleeping Bag', 'in' => 'Camping & Hiking', 'price' => 999, 'weight' => 1.2,
                        'description' => "Light sleeping bag for camping and overnight trips.\n\n• Packs small\n• Two-way zipper",
                        'options' => ['Color' => ['Blue' => 'sleeping-bag-blue.jpg', 'Green' => 'sleeping-bag-green.jpg']],
                    ],
                ],
            ],

            'lumierebeauty@boombuy.test' => [
                'folder' => 'lumierebeauty',
                'categories' => ['Skincare' => [], 'Makeup' => [], 'Hair & Body' => []],
                'products' => [
                    [
                        'name' => 'Gentle Facial Cleanser', 'in' => 'Skincare', 'price' => 299, 'weight' => 0.2, 'stock' => 50,
                        'description' => "Mild foaming cleanser that removes dirt and oil without drying.\n\n• For all skin types\n• 150ml",
                        'photos' => ['cleanser.jpg'],
                    ],
                    [
                        'name' => 'Hydrating Moisturizer', 'in' => 'Skincare', 'price' => 449, 'weight' => 0.15, 'stock' => 40,
                        'description' => "Lightweight gel-cream that keeps skin soft and hydrated all day.\n\n• Non-sticky\n• 50ml",
                        'photos' => ['moisturizer.jpg'],
                    ],
                    [
                        'name' => 'Sunscreen SPF 50', 'in' => 'Skincare', 'price' => 399, 'discount' => 10, 'weight' => 0.1, 'stock' => 60,
                        'description' => "Broad-spectrum SPF 50 PA++++ sunscreen with no white cast.\n\n• Water resistant\n• 50ml",
                        'photos' => ['sunscreen.jpg'],
                    ],
                    [
                        'name' => 'Vitamin C Serum', 'in' => 'Skincare', 'price' => 549, 'weight' => 0.1, 'stock' => 35,
                        'description' => "Brightening serum that helps even out skin tone and dark spots.\n\n• 30ml dropper bottle",
                        'photos' => ['serum.jpg'],
                    ],
                    [
                        'name' => 'Sheet Mask Pack (10 pcs)', 'in' => 'Skincare', 'price' => 299, 'weight' => 0.3, 'stock' => 50,
                        'description' => "Ten hydrating sheet masks for a quick at-home glow.",
                        'photos' => ['sheet-mask.jpg'],
                    ],
                    [
                        'name' => 'Matte Liquid Lipstick', 'in' => 'Makeup', 'price' => 249, 'weight' => 0.05,
                        'description' => "Long-wearing matte lipstick that does not transfer.\n\n• Lasts up to 8 hours",
                        'options' => ['Shade' => ['Red' => 'lipstick-red.jpg', 'Nude' => 'lipstick-nude.jpg', 'Pink' => 'lipstick-pink.jpg', 'Brown' => 'lipstick-brown.jpg']],
                    ],
                    [
                        'name' => 'Cushion Foundation', 'in' => 'Makeup', 'price' => 699, 'discount' => 15, 'weight' => 0.1,
                        'description' => "Light, dewy coverage in a handy cushion compact with SPF.\n\n• Refill included",
                        'options' => ['Shade' => ['Light' => 'cushion-light.jpg', 'Medium' => 'cushion-medium.jpg']],
                    ],
                    [
                        'name' => 'Eyeshadow Palette', 'in' => 'Makeup', 'price' => 499, 'weight' => 0.15, 'stock' => 30,
                        'description' => "Everyday palette of matte and shimmer shades.\n\n• Highly pigmented\n• Mirror inside",
                        'photos' => ['eyeshadow.jpg'],
                    ],
                    [
                        'name' => 'Waterproof Mascara', 'in' => 'Makeup', 'price' => 299, 'weight' => 0.05, 'stock' => 45,
                        'description' => "Lengthening, smudge-proof mascara that survives humid days.",
                        'photos' => ['mascara.jpg'],
                    ],
                    [
                        'name' => 'Makeup Brush Set', 'in' => 'Makeup', 'price' => 599, 'weight' => 0.3, 'stock' => 25,
                        'description' => "Soft synthetic brushes for face and eyes, with a case.",
                        'photos' => ['brush-set.jpg'],
                    ],
                    [
                        'name' => 'Shampoo & Conditioner Set', 'in' => 'Hair & Body', 'price' => 499, 'weight' => 1.0, 'stock' => 30,
                        'description' => "Nourishing shampoo and conditioner for smooth, frizz-free hair.\n\n• 2 × 400ml",
                        'photos' => ['shampoo-set.jpg'],
                    ],
                    [
                        'name' => 'Hair Dryer', 'in' => 'Hair & Body', 'price' => 1299, 'discount' => 10, 'weight' => 0.7,
                        'description' => "Ionic hair dryer that dries fast and reduces frizz.\n\n• Two heat and speed settings\n• Cool shot button",
                        'options' => ['Color' => ['Black' => 'hair-dryer-black.jpg', 'Pink' => 'hair-dryer-pink.jpg']],
                    ],
                    [
                        'name' => 'Body Lotion', 'in' => 'Hair & Body', 'price' => 249, 'weight' => 0.45, 'stock' => 50,
                        'description' => "Fast-absorbing lotion that keeps skin moisturized for 24 hours.\n\n• 400ml pump bottle",
                        'photos' => ['body-lotion.jpg'],
                    ],
                    [
                        'name' => 'Body Wash', 'in' => 'Hair & Body', 'price' => 229, 'weight' => 0.5, 'stock' => 50,
                        'description' => "Refreshing body wash with a light, clean scent.\n\n• 500ml",
                        'photos' => ['body-wash.jpg'],
                    ],
                    [
                        'name' => 'Hair Straightener', 'in' => 'Hair & Body', 'price' => 999, 'weight' => 0.5,
                        'description' => "Ceramic flat iron that heats up in 30 seconds.\n\n• Adjustable temperature\n• Auto shut-off",
                        'options' => ['Color' => ['Black' => 'straightener-black.jpg', 'Pink' => 'straightener-pink.jpg']],
                    ],
                ],
            ],

            'pawsandwhiskers@boombuy.test' => [
                'folder' => 'pawsandwhiskers',
                'categories' => ['Dog' => [], 'Cat' => [], 'Pet Essentials' => []],
                'products' => [
                    [
                        'name' => 'Dry Dog Food 3kg', 'in' => 'Dog', 'price' => 699, 'weight' => 3.1, 'stock' => 30,
                        'description' => "Complete, balanced dry food for adult dogs.\n\n• Real meat as the first ingredient\n• 3kg bag",
                        'photos' => ['dog-food.jpg'],
                    ],
                    [
                        'name' => 'Dog Collar', 'in' => 'Dog', 'price' => 199, 'weight' => 0.1,
                        'description' => "Durable nylon collar with a quick-release buckle and a D-ring for the leash.",
                        'options' => [
                            'Color' => ['Red' => 'collar-red.jpg', 'Blue' => 'collar-blue.jpg', 'Black' => 'collar-black.jpg'],
                            'Size' => ['S', 'M', 'L'],
                        ],
                    ],
                    [
                        'name' => 'Dog Leash', 'in' => 'Dog', 'price' => 249, 'weight' => 0.2,
                        'description' => "Strong 1.5m leash with a comfortable handle for daily walks.",
                        'options' => ['Color' => ['Blue' => 'leash-blue.jpg', 'Pink' => 'leash-pink.jpg']],
                    ],
                    [
                        'name' => 'Pet Bed', 'in' => 'Dog', 'price' => 899, 'discount' => 10, 'weight' => 1.0,
                        'description' => "Soft, round bed with raised sides for dogs and cats to curl up in.\n\n• Washable cover",
                        'options' => [
                            'Color' => ['Grey' => 'dog-bed-grey.jpg', 'Brown' => 'dog-bed-brown.jpg'],
                            'Size' => ['S', 'M|+200', 'L|+400'],
                        ],
                    ],
                    [
                        'name' => 'Pet Shampoo', 'in' => 'Dog', 'price' => 299, 'weight' => 0.5, 'stock' => 40,
                        'description' => "Gentle shampoo that cleans and deodorizes without drying the coat.\n\n• 500ml",
                        'photos' => ['dog-shampoo.jpg'],
                    ],
                    [
                        'name' => 'Dry Cat Food 1.5kg', 'in' => 'Cat', 'price' => 499, 'weight' => 1.6, 'stock' => 30,
                        'description' => "Complete dry food for adult cats with taurine for healthy eyes and heart.\n\n• 1.5kg bag",
                        'photos' => ['cat-food.jpg'],
                    ],
                    [
                        'name' => 'Wet Cat Food (12 pouches)', 'in' => 'Cat', 'price' => 449, 'weight' => 1.0, 'stock' => 30,
                        'description' => "Tasty wet food pouches cats love.\n\n• 12 × 85g",
                        'photos' => ['wet-cat-food.jpg'],
                    ],
                    [
                        'name' => 'Cat Litter 10L', 'in' => 'Cat', 'price' => 349, 'weight' => 5.0, 'stock' => 35,
                        'description' => "Clumping litter with strong odor control.\n\n• Low dust",
                        'photos' => ['cat-litter.jpg'],
                    ],
                    [
                        'name' => 'Cat Scratching Post', 'in' => 'Cat', 'price' => 699, 'weight' => 2.0, 'stock' => 15,
                        'description' => "Sisal scratching post that saves your sofa.\n\n• Sturdy base",
                        'photos' => ['scratching-post.jpg'],
                    ],
                    [
                        'name' => 'Cat Teaser Wand', 'in' => 'Cat', 'price' => 149, 'weight' => 0.1, 'stock' => 50,
                        'description' => "Feather wand toy for playtime and exercise.",
                        'photos' => ['teaser-wand.jpg'],
                    ],
                    [
                        'name' => 'Litter Scoop', 'in' => 'Pet Essentials', 'price' => 99, 'weight' => 0.1, 'stock' => 60,
                        'description' => "Sturdy litter scoop with a long handle.",
                        'photos' => ['litter-scoop.jpg'],
                    ],
                    [
                        'name' => 'Stainless Pet Bowl', 'in' => 'Pet Essentials', 'price' => 199, 'weight' => 0.3, 'stock' => 50,
                        'description' => "Rust-proof stainless bowl with a non-slip base.\n\n• Dishwasher safe",
                        'photos' => ['pet-bowl.jpg'],
                    ],
                    [
                        'name' => 'Pet Carrier', 'in' => 'Pet Essentials', 'price' => 1299, 'weight' => 2.0, 'stock' => 12,
                        'description' => "Ventilated hard carrier for vet visits and travel.\n\n• Secure door lock\n• For cats and small dogs",
                        'photos' => ['pet-carrier.jpg'],
                    ],
                    [
                        'name' => 'Grooming Brush', 'in' => 'Pet Essentials', 'price' => 249, 'weight' => 0.15, 'stock' => 40,
                        'description' => "Self-cleaning slicker brush that removes loose fur and tangles.",
                        'photos' => ['grooming-brush.jpg'],
                    ],
                    [
                        'name' => 'Portable Pet Water Bottle', 'in' => 'Pet Essentials', 'price' => 299, 'weight' => 0.2, 'stock' => 35,
                        'description' => "Leak-proof water bottle with a drinking tray for walks and trips.",
                        'photos' => ['travel-bottle.jpg'],
                    ],
                ],
            ],

            'ironclad@boombuy.test' => [
                'folder' => 'ironclad',
                'categories' => ['Hand Tools' => [], 'Power & Electrical' => [], 'Measuring, Safety & Storage' => []],
                'products' => [
                    [
                        'name' => 'Claw Hammer', 'in' => 'Hand Tools', 'price' => 299, 'weight' => 0.7, 'stock' => 30,
                        'description' => "Steel claw hammer with a shock-absorbing grip.\n\n• 16 oz head",
                        'photos' => ['hammer.jpg'],
                    ],
                    [
                        'name' => 'Screwdriver Set', 'in' => 'Hand Tools', 'price' => 399, 'weight' => 0.6, 'stock' => 25,
                        'description' => "Flat and Phillips screwdrivers in common sizes.\n\n• Magnetic tips",
                        'photos' => ['screwdriver-set.jpg'],
                    ],
                    [
                        'name' => 'Adjustable Wrench', 'in' => 'Hand Tools', 'price' => 349, 'weight' => 0.4, 'stock' => 25,
                        'description' => "Chrome vanadium adjustable wrench for nuts and bolts.\n\n• 10 inch",
                        'photos' => ['wrench.jpg'],
                    ],
                    [
                        'name' => 'Pliers Set', 'in' => 'Hand Tools', 'price' => 499, 'weight' => 0.8, 'stock' => 20,
                        'description' => "Combination, long-nose and cutting pliers with insulated grips.",
                        'photos' => ['pliers.jpg'],
                    ],
                    [
                        'name' => 'Hex Key Set', 'in' => 'Hand Tools', 'price' => 199, 'weight' => 0.3, 'stock' => 35,
                        'description' => "Allen keys in metric sizes, for furniture and bikes.\n\n• Holder included",
                        'photos' => ['hex-key-set.jpg'],
                    ],
                    [
                        'name' => 'Utility Knife', 'in' => 'Hand Tools', 'price' => 149, 'weight' => 0.15, 'stock' => 40,
                        'description' => "Retractable cutter with a locking blade.\n\n• Spare blades included",
                        'photos' => ['utility-knife.jpg'],
                    ],
                    [
                        'name' => 'Cordless Drill', 'in' => 'Power & Electrical', 'price' => 2499, 'discount' => 12, 'weight' => 1.8, 'stock' => 10,
                        'description' => "Rechargeable drill-driver for wood, metal and screws.\n\n• Two speeds\n• Battery and charger included",
                        'photos' => ['cordless-drill.jpg'],
                    ],
                    [
                        'name' => 'Portable Air Compressor', 'in' => 'Power & Electrical', 'price' => 1799, 'weight' => 1.5, 'stock' => 10,
                        'description' => "Compact air compressor for car tires, bike tires and balls.\n\n• Digital pressure gauge",
                        'photos' => ['air-compressor.jpg'],
                    ],
                    [
                        'name' => 'LED Work Light', 'in' => 'Power & Electrical', 'price' => 699, 'weight' => 0.8, 'stock' => 15,
                        'description' => "Bright, rechargeable work light with a folding stand.",
                        'photos' => ['work-light.jpg'],
                    ],
                    [
                        'name' => 'Extension Cord', 'in' => 'Power & Electrical', 'price' => 399, 'weight' => 0.6, 'stock' => 30,
                        'description' => "Multi-outlet extension cord with a safety switch.\n\n• 3 meters",
                        'photos' => ['extension-cord.jpg'],
                    ],
                    [
                        'name' => 'Measuring Tape 5m', 'in' => 'Measuring, Safety & Storage', 'price' => 179, 'weight' => 0.25, 'stock' => 40,
                        'description' => "5-meter tape with an auto-lock and belt clip.",
                        'photos' => ['measuring-tape.jpg'],
                    ],
                    [
                        'name' => 'Spirit Level', 'in' => 'Measuring, Safety & Storage', 'price' => 249, 'weight' => 0.4, 'stock' => 20,
                        'description' => "Aluminum spirit level for shelves, frames and tiles.",
                        'photos' => ['spirit-level.jpg'],
                    ],
                    [
                        'name' => 'Safety Goggles', 'in' => 'Measuring, Safety & Storage', 'price' => 149, 'weight' => 0.1, 'stock' => 40,
                        'description' => "Clear, anti-fog goggles that protect your eyes from dust and debris.",
                        'photos' => ['safety-goggles.jpg'],
                    ],
                    [
                        'name' => 'Work Gloves', 'in' => 'Measuring, Safety & Storage', 'price' => 199, 'weight' => 0.15, 'stock' => 40,
                        'description' => "Tough leather work gloves for building, gardening and hauling.",
                        'photos' => ['work-gloves.jpg'],
                    ],
                    [
                        'name' => 'Toolbox', 'in' => 'Measuring, Safety & Storage', 'price' => 599, 'weight' => 1.5, 'stock' => 15,
                        'description' => "Plastic toolbox with a lift-out tray and small-parts compartments.",
                        'photos' => ['toolbox.jpg'],
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
