<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductVariation;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Seller ID ni Jairo Banaag sa users table
        $sellerId = 12;

        $productsByCategory = [
            'Electronics' => [
                'Wireless Bluetooth Earbuds',
                'Portable Power Bank 20000mAh',
                'Smart LED Light Bulb',
                'USB-C Fast Charger',
                'Wireless Mouse',
            ],
            "Women's Fashion" => [
                'Floral Summer Dress',
                'High-Waist Denim Jeans',
                'Oversized Knit Sweater',
                'Satin Blouse',
                'Pleated Midi Skirt',
            ],
            "Men's Fashion" => [
                'Slim Fit Polo Shirt',
                'Classic Denim Jacket',
                'Cargo Shorts',
                'Long Sleeve Flannel Shirt',
                'Plain Cotton T-Shirt',
            ],
            'Kids & Baby' => [
                'Baby Onesie Set',
                'Kids Cartoon Backpack',
                'Toddler Sneakers',
                'Baby Feeding Bottle Set',
                'Kids Pajama Set',
            ],
            'Home & Living' => [
                'Ceramic Coffee Mug Set',
                'Cotton Throw Blanket',
                'Scented Candle Set',
                'Storage Organizer Box',
                'Non-Stick Cooking Pan',
            ],
            'Sports & Outdoors' => [
                'Yoga Mat',
                'Adjustable Dumbbell Set',
                'Camping Tent for 2',
                'Sports Water Bottle',
                'Resistance Bands Set',
            ],
            'Beauty & Personal Care' => [
                'Vitamin C Facial Serum',
                'Moisturizing Lip Balm',
                'Hair Dryer',
                'Facial Cleansing Brush',
                'Sunscreen SPF 50',
            ],
            'Food & Beverages' => [
                'Ground Coffee Beans 500g',
                'Assorted Herbal Tea Pack',
                'Instant Noodles Bundle',
                'Trail Mix Snack Pack',
                'Bottled Fruit Juice Pack',
            ],
            'Automotive' => [
                'Car Phone Mount Holder',
                'Microfiber Car Cleaning Cloth',
                'Tire Pressure Gauge',
                'Car Air Freshener Set',
                'Portable Car Vacuum Cleaner',
            ],
            'Office & School' => [
                'Ballpoint Pen Set',
                'Spiral Notebook Pack',
                'Desk Organizer Tray',
                'Backpack for Students',
                'Sticky Notes Set',
            ],
            'Pet Supplies' => [
                'Dog Chew Toy',
                'Cat Scratching Post',
                'Pet Feeding Bowl Set',
                'Pet Grooming Brush',
                'Adjustable Pet Leash',
            ],
            'Toys, Games & Hobbies' => [
                'Building Blocks Set',
                'Remote Control Car',
                'Puzzle 1000 Pieces',
                'Board Game for Family',
                'Plush Stuffed Toy',
            ],
            'Jewelry & Accessories' => [
                'Stainless Steel Bracelet',
                'Pearl Stud Earrings',
                'Leather Wallet',
                'Fashion Sunglasses',
                'Minimalist Wristwatch',
            ],
            'Shoes' => [
                'Running Shoes',
                'Casual Canvas Sneakers',
                'Slip-On Sandals',
                'Formal Leather Shoes',
                'Slip Resistant Work Shoes',
            ],
            'Tools & Home Improvement' => [
                'Cordless Drill Set',
                'Adjustable Wrench Set',
                'LED Work Light',
                'Tool Storage Box',
                'Measuring Tape 5m',
            ],
            'Garden & Outdoor' => [
                'Garden Hand Trowel Set',
                'Watering Can 5L',
                'Outdoor Solar Garden Light',
                'Potted Artificial Plant',
                'Garden Gloves Pair',
            ],
        ];

        // Hindi lahat ng kategorya kailangan ng variant sa totoong buhay
        // (hal. kape, gamit sa opisina) — dito lang ito ilalagay kung
        // makatuwiran.
        $sizeCategories = [
            "Women's Fashion",
            "Men's Fashion",
            'Kids & Baby',
            'Shoes',
        ];

        $colorCategories = [
            'Electronics',
            'Home & Living',
            'Toys, Games & Hobbies',
            'Jewelry & Accessories',
        ];

        $sizes = [
            ['value' => 'S', 'price_adjustment' => 0],
            ['value' => 'M', 'price_adjustment' => 0],
            ['value' => 'L', 'price_adjustment' => 0],
            ['value' => 'XL', 'price_adjustment' => 50],
        ];

        $colors = [
            ['value' => 'Black', 'price_adjustment' => 0],
            ['value' => 'White', 'price_adjustment' => 0],
            ['value' => 'Blue', 'price_adjustment' => 0],
            ['value' => 'Red', 'price_adjustment' => 30],
        ];

        foreach ($productsByCategory as $categoryName => $products) {
            foreach ($products as $productName) {
                $product = Product::firstOrCreate(
                    [
                        'name' => $productName,
                        'category' => $categoryName,
                    ],
                    [
                        'seller_id' => $sellerId,
                        'description' => $productName . ' - quality product available at BoomBuy.',
                        'price' => rand(99, 2999) + 0.00,
                        'stock' => rand(10, 100),
                        // Placeholder path lang ito. Palitan/upload actual image
                        // dito o sa admin panel pagkatapos mag-seed.
                        'image' => 'products/placeholder.jpg',
                    ]
                );

                if (in_array($categoryName, $sizeCategories)) {
                    $this->seedVariations($product, 'Size', $sizes);
                } elseif (in_array($categoryName, $colorCategories)) {
                    $this->seedVariations($product, 'Color', $colors);
                }
            }
        }
    }

    private function seedVariations(Product $product, string $type, array $options): void
    {
        foreach ($options as $option) {
            ProductVariation::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'variation_type' => $type,
                    'variation_value' => $option['value'],
                ],
                [
                    'price_adjustment' => $option['price_adjustment'],
                    'stock' => rand(5, 30),
                ]
            );
        }
    }
}