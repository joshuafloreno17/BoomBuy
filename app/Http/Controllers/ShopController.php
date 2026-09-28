<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function home()
    {
        $user = session()->get('user');

        // Logged-in Buyer
        if ($user && ($user['role'] ?? '') === 'buyer') {
            return redirect()->route('buyer.dashboard');
        }

        // Logged-in Seller
        if ($user && ($user['role'] ?? '') === 'seller') {
            return redirect()->route('seller.dashboard');
        }

        // Logged-in Rider
        if ($user && ($user['role'] ?? '') === 'rider') {
            return redirect()->route('rider.dashboard');
        }

        // Logged-in Logistics
        if ($user && ($user['role'] ?? '') === 'logistics') {
            return redirect()->route('logistics.dashboard');
        }


        /*
        |--------------------------------------------------------------------------
        | FEATURED PRODUCTS FOR THE LANDING PAGE
        |--------------------------------------------------------------------------
        */

        $featuredProducts = Product::withCount('reviews')->withAvg('reviews', 'rating')->latest()->take(4)->get();


        // Guest → Landing Page
        return view('welcome', compact('featuredProducts'));
    }

    public function products()
    {
        $category = request('category');

        /*
        |--------------------------------------------------------------------------
        | BOOMBUY CATEGORY MAP
        |--------------------------------------------------------------------------
        */

        $categoryMap = [

            'electronics' => [
                'Electronics',
                'Smartphone',
                'Laptop',
                'Audio',
                'Wearable',
                'Accessories',
                'electronics',
                'smartphone',
                'laptop',
                'audio',
                'wearable',
                'accessories',
            ],

            'womens-fashion' => [
                "Women's Fashion",
                'Women',
                "Women's",
                'womens-fashion',
                'women',
            ],

            'mens-fashion' => [
                "Men's Fashion",
                'Men',
                "Men's",
                'mens-fashion',
                'men',
            ],

            'kids-baby' => [
                'Kids & Baby',
                'Kids',
                'Baby',
                'kids-baby',
                'kids',
                'baby',
            ],

            'home-living' => [
                'Home & Living',
                'Home',
                'home-living',
                'home',
            ],

            'sports-outdoors' => [
                'Sports & Outdoors',
                'Sports',
                'sports-outdoors',
                'sports',
            ],

            'beauty-personal-care' => [
                'Beauty & Personal Care',
                'Beauty',
                'beauty-personal-care',
                'beauty',
            ],

            'food-beverages' => [
                'Food & Beverages',
                'Food',
                'food-beverages',
                'food',
            ],

            'automotive' => [
                'Automotive',
                'automotive',
            ],

            'office-school' => [
                'Office & School',
                'Office',
                'School',
                'office-school',
                'office',
                'school',
            ],

            'pet-supplies' => [
                'Pet Supplies',
                'Pets',
                'Pet',
                'pet-supplies',
                'pet',
            ],

            'toys-games-hobbies' => [
                'Toys, Games & Hobbies',
                'Toys',
                'Games',
                'Hobbies',
                'toys-games-hobbies',
                'toys',
            ],

            'jewelry-accessories' => [
                'Jewelry & Accessories',
                'Jewelry',
                'Accessories',
                'jewelry-accessories',
                'jewelry',
                'accessories',
            ],

            'shoes' => [
                'Shoes',
                'shoes',
            ],

            'tools-home-improvement' => [
                'Tools & Home Improvement',
                'Tools',
                'Home Improvement',
                'tools-home-improvement',
                'tools',
            ],

            'garden-outdoor' => [
                'Garden & Outdoor',
                'Garden',
                'Outdoor',
                'garden-outdoor',
                'garden',
            ],

        ];


        /*
        |--------------------------------------------------------------------------
        | GET PRODUCTS
        |--------------------------------------------------------------------------
        */

        $search = trim((string) request('search'));

        $databaseProducts = Product::latest()
            ->where('is_flagged', false)
            ->where('is_archived', false)
            ->when(
                $category && isset($categoryMap[$category]),
                function ($query) use ($category, $categoryMap) {

                    $query->whereIn(
                        'category',
                        $categoryMap[$category]
                    );

                }
            )
            ->when(
                $search !== '',
                function ($query) use ($search) {

                    $query->where(
                        'name',
                        'like',
                        '%' . $search . '%'
                    );

                }
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | FORMAT PRODUCTS + REAL REVIEWS
        |--------------------------------------------------------------------------
        */

        $products = $databaseProducts->map(function ($product) {

            // Get actual reviews for this product
            $reviewData = DB::table('product_reviews')
                ->where('product_id', $product->id)
                ->selectRaw('COUNT(*) as review_count, AVG(rating) as average_rating')
                ->first();

            $reviewCount = (int) ($reviewData->review_count ?? 0);

            $averageRating = $reviewCount > 0
                ? round((float) $reviewData->average_rating, 1)
                : 0;


            return [

                'id' => $product->id,
                'slug' => Str::slug($product->name) . '-' . $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'price' => (float) $product->price,
                'stock' => (int) $product->stock,
                'description' => $product->description,
                'image' => $product->image,
                'icon' => $product->image ?? null,
                'seller_id' => $product->seller_id,

                // REAL rating from product_reviews
                'rating' => $averageRating,

                // REAL review count from product_reviews
                'reviews' => $reviewCount,

            ];

        })->toArray();


        /*
        |--------------------------------------------------------------------------
        | WISHLIST STATE (empty for guests)
        |--------------------------------------------------------------------------
        */

        $sessionUser = session()->get('user');

        $wishlistedIds = ($sessionUser && ($sessionUser['role'] ?? '') === 'buyer')
            ? DB::table('wishlists')
                ->where('user_id', $sessionUser['id'])
                ->pluck('product_id')
                ->toArray()
            : [];


        /*
        |--------------------------------------------------------------------------
        | RETURN SHOP PAGE
        |--------------------------------------------------------------------------
        */

        return view(
            'pages.products',
            compact('products', 'wishlistedIds')
        );
    }

    public function productDetails($id)
    {
        // "123" or "product-name-123" — the id decides, so two products with
        // the same name (from different sellers) can never be mixed up.
        $product = null;

        if (ctype_digit((string) $id)) {

            $product = Product::find((int) $id);

        } elseif (preg_match('/-(\d+)$/', (string) $id, $m)) {

            $candidate = Product::find((int) $m[1]);

            // Only if the name part matches too — so an old link like
            // "iphone-15" isn't mistaken for product #15.
            if ($candidate && Str::slug($candidate->name) . '-' . $candidate->id === $id) {
                $product = $candidate;
            }
        }

        // Old name-only links ("product-name") still work when the name is
        // unique; if two products share it, the link is ambiguous → 404.
        if (!$product && !ctype_digit((string) $id)) {

            $slug = Str::slug($id);

            $matches = Product::all()->filter(fn ($item) => Str::slug($item->name) === $slug);

            $product = $matches->count() === 1 ? $matches->first() : null;
        }

        if (!$product) {
            abort(404);
        }

        // Archived/flagged products are off the storefront — only their own
        // seller or the admin can still open the page.
        if ($product->is_archived || $product->is_flagged) {

            $isOwner = (int) (session('user.id') ?? 0) === (int) $product->seller_id
                && session('user.role') === 'seller';

            if (!$isOwner && !session('admin_logged_in')) {
                abort(404);
            }
        }

        // REAL reviews for this product — no reviews yet means no orders
        // have been delivered and rated for it, which is expected/honest.
        $reviews = DB::table('product_reviews')
            ->join('users', 'users.id', '=', 'product_reviews.buyer_id')
            ->where('product_reviews.product_id', $product->id)
            ->select(
                'product_reviews.rating',
                'product_reviews.review',
                'product_reviews.seller_reply',
                'product_reviews.created_at',
                'users.name as buyer_name'
            )
            ->orderByDesc('product_reviews.created_at')
            ->get();

        $reviewCount = $reviews->count();

        $averageRating = $reviewCount > 0
            ? round($reviews->avg('rating'), 1)
            : 0;

        // A handful of other products from the same category
        $relatedProducts = Product::where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->latest()
            ->take(4)
            ->get();

        // Wishlist state (false for guests)
        $sessionUser = session()->get('user');

        $isWishlisted = ($sessionUser && ($sessionUser['role'] ?? '') === 'buyer')
            ? DB::table('wishlists')
                ->where('user_id', $sessionUser['id'])
                ->where('product_id', $product->id)
                ->exists()
            : false;

        // A buyer can message this product's seller as long as it actually
        // has one (admin-added products have no seller_id) and they aren't
        // somehow viewing their own listing.
        $canMessageSeller = $sessionUser
            && ($sessionUser['role'] ?? '') === 'buyer'
            && !empty($product->seller_id)
            && (int) $product->seller_id !== (int) $sessionUser['id'];

        $variations = \App\Models\ProductVariation::where('product_id', $product->id)->get();

        return view(
            'pages.product-details',
            compact(
                'product',
                'reviews',
                'reviewCount',
                'averageRating',
                'relatedProducts',
                'isWishlisted',
                'variations',
                'canMessageSeller'
            )
        );
    }

    public function categories()
    {
        return view('categories');
    }
}
