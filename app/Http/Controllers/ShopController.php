<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Categories;
use App\Support\ProductPhotos;
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

        $featuredProducts = Product::onSale()->withCount('reviews')->withAvg('reviews', 'rating')->latest()->take(4)->get();

        // Hero: the best sellers (delivered units) take turns; until there are
        // four of them, the newest products fill the remaining spots.
        $heroProducts = Product::onSale()
            ->withSoldCount()
            ->orderByDesc('sold_count')
            ->orderByDesc('id')
            ->take(4)
            ->get()
            ->filter(fn ($product) => (int) $product->sold_count > 0)
            ->values();

        if ($heroProducts->count() < 4) {
            $heroProducts = $heroProducts->concat(
                Product::onSale()
                    ->whereNotIn('id', $heroProducts->pluck('id'))
                    ->latest()
                    ->take(4 - $heroProducts->count())
                    ->get()
            );
        }

        $featuredShops = \App\Support\SellerShop::many(
            $featuredProducts->pluck('seller_id')->concat($heroProducts->pluck('seller_id'))
        );


        // Guest → Landing Page
        return view('welcome', compact('featuredProducts', 'featuredShops', 'heroProducts'));
    }

    // Category slug => every stored category name that belongs to it (old names included).
    private static function categoryMap(): array
    {
        return [

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
    }

    private const SHOP_PER_PAGE = 24;

    private const SHOP_SORTS = ['newest', 'price_low', 'price_high', 'rating'];

    public function sellerShop(int $seller)
    {
        // Only an Active seller has a shop page; a suspended or deactivated
        // seller's shop is closed along with their listings.
        $hasShop = \App\Models\User::where('id', $seller)
            ->where('role', 'seller')
            ->where('status', 'Active')
            ->exists();

        abort_unless($hasShop, 404);

        return $this->products($seller);
    }

    public function products(?int $sellerId = null)
    {
        $categoryMap = self::categoryMap();

        /*
        |--------------------------------------------------------------------------
        | FILTERS — read from the URL so they survive refresh / back and can be shared
        |--------------------------------------------------------------------------
        */

        $category = (string) request('category');
        $category = isset($categoryMap[$category]) ? $category : '';

        $search = trim(mb_substr((string) request('search'), 0, 100));

        $min = is_numeric(request('min')) ? max(0, (float) request('min')) : null;
        $max = is_numeric(request('max')) ? max(0, (float) request('max')) : null;

        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        $rating = in_array((int) request('rating'), [3, 4], true) ? (int) request('rating') : 0;
        $inStock = request()->boolean('in_stock');
        $sort = in_array(request('sort'), self::SHOP_SORTS, true) ? request('sort') : 'newest';

        // Each word must appear somewhere in the name, category or
        // description — so "iphone pro" finds "iPhone 18 Pro Max" and
        // "electronics" finds every Electronics product.
        $searchTerms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        /*
        |--------------------------------------------------------------------------
        | GET PRODUCTS (one page at a time)
        |--------------------------------------------------------------------------
        */

        $query = Product::onSale()
            ->withSellableStock()
            ->withSoldCount()
            ->when($sellerId, fn ($q) => $q->where('seller_id', $sellerId))
            ->withCount(['reviews', 'variations'])
            ->withAvg('reviews', 'rating')
            // For the card's quick "choose a color/size" popup.
            ->with('variations:id,product_id,variation_type,variation_value,price_adjustment,stock')
            ->when($category, fn ($q) => $q->whereIn('category', $categoryMap[$category]))
            ->when(!empty($searchTerms), function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    // Treat % and _ typed by the buyer as literal text.
                    $like = '%' . addcslashes($term, '%_\\') . '%';

                    $q->where(function ($match) use ($like) {
                        $match->where('name', 'like', $like)
                            ->orWhere('category', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    });
                }
            })
            ->when($min !== null, fn ($q) => $q->where('price', '>=', $min))
            ->when($max !== null, fn ($q) => $q->where('price', '<=', $max))
            // Products with options count their options' stock, not their own.
            ->when($inStock, fn ($q) => $q->whereRaw(Product::SELLABLE_STOCK_SQL . ' > 0'))
            ->when($rating, fn ($q) => $q->whereRaw('(select avg(rating) from product_reviews where product_reviews.product_id = products.id) >= ?', [$rating]));

        match ($sort) {
            'price_low' => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),
            default => $query->orderByDesc('created_at'),
        };

        $paginator = $query->orderByDesc('id')
            ->paginate(self::SHOP_PER_PAGE)
            ->withQueryString();

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

        // Shop names for the cards, in one go.
        $shops = \App\Support\SellerShop::many($paginator->getCollection()->pluck('seller_id'));

        $products = $paginator->getCollection()
            ->map(fn ($product) => [
                'id' => $product->id,
                'slug' => Str::slug($product->name) . '-' . $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'price' => (float) $product->price,
                'stock' => (int) $product->sellable_stock,
                'sold' => (int) $product->sold_count,
                'shop_name' => $shops->get($product->seller_id)['name'] ?? null,
                'shop_url' => $shops->get($product->seller_id)['url'] ?? null,
                // One item at this price already reaches the free-delivery minimum.
                'free_shipping' => (float) $product->price >= \App\Support\DeliveryFee::FREE_SHIPPING_MIN,
                'image' => productImageUrl($product->image),
                'icon' => Categories::icon($product->category),
                'rating' => (int) $product->reviews_count > 0 ? round((float) $product->reviews_avg_rating, 1) : null,
                'reviews' => (int) $product->reviews_count,
                'has_variations' => (int) $product->variations_count > 0,
                'variation_type' => $product->variations->first()->variation_type ?? null,
                'variations' => $product->variations
                    ->map(fn ($v) => [
                        'id' => $v->id,
                        'label' => $v->variation_value,
                        'price' => (float) $product->price + (float) $v->price_adjustment,
                        'stock' => (int) $v->stock,
                    ])
                    ->values()
                    ->all(),
                'in_wishlist' => in_array($product->id, $wishlistedIds),
            ])
            ->all();

        // "Load more": just the next page's cards.
        if (request()->ajax() && request()->boolean('partial')) {
            // The next link must open a normal page if JavaScript ever falls back to it.
            $paginator->appends(['partial' => null]);

            return response()->json([
                'html' => view('partials.shop-product-cards', ['products' => $products, 'showCategory' => $category === ''])->render(),
                'next' => $paginator->nextPageUrl(),
                'shown' => $paginator->lastItem() ?? 0,
                'total' => $paginator->total(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORY CHIPS — the Shop lists every category (empty ones last);
        | a seller's shop only the categories it actually sells in
        |--------------------------------------------------------------------------
        */

        $categoryCounts = Product::onSale()
            ->when($sellerId, fn ($q) => $q->where('seller_id', $sellerId))
            ->select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->get()
            ->groupBy(fn ($row) => Categories::slug($row->category))
            ->map(fn ($rows) => (int) $rows->sum('total'));

        $categories = collect(Categories::LIST)
            ->map(fn ($label, $slug) => [
                'slug' => $slug,
                'label' => $label,
                'icon' => Categories::icon($slug),
                'count' => $categoryCounts[$slug] ?? 0,
            ])
            ->filter(fn ($c) => !$sellerId || $c['count'] > 0 || $c['slug'] === $category)
            ->sortBy(fn ($c) => $c['count'] > 0 ? 0 : 1)
            ->values()
            ->all();

        $filters = [
            'category' => $category,
            'category_label' => $category ? Categories::LIST[$category] : null,
            'search' => $search,
            'min' => $min,
            'max' => $max,
            'rating' => $rating,
            'in_stock' => $inStock,
            'sort' => $sort,
        ];

        return view('pages.products', [
            'products' => $products,
            'paginator' => $paginator,
            'filters' => $filters,
            'categories' => $categories,
            'totalProducts' => array_sum($categoryCounts->all()),
            // On /shop/{seller}: that shop's header instead of "All products".
            'shop' => $sellerId ? \App\Support\SellerShop::one($sellerId) : null,
        ]);
    }

    /**
     * Live suggestions for the navbar search box (JSON).
     *   ?q=        → just the popular categories (shown before typing)
     *   ?q=iph     → matching products (names starting with it first) and
     *                matching categories
     * Same visibility and word rules as the shop's full search.
     */
    public function searchSuggestions()
    {
        $q = trim(mb_substr((string) request('q'), 0, 60));
        $terms = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);

        // A category picked in the search box's dropdown narrows everything to it.
        $categoryNames = self::categoryMap()[(string) request('category')] ?? null;

        $categoryLink = fn (string $slug) => [
            'label' => Categories::LIST[$slug],
            'icon' => Categories::icon($slug),
            'url' => route('products', ['category' => $slug]),
        ];

        $toSuggestion = fn ($product) => [
            'name' => $product->name,
            'price' => '₱' . number_format((float) $product->price, 2),
            'category' => $product->category,
            // Shown when there's no photo (or it fails to load).
            'icon' => Categories::icon($product->category),
            'image' => $product->image
                ? (str_starts_with($product->image, 'http') ? $product->image : asset('storage/' . ltrim($product->image, '/')))
                : null,
            'url' => route('product.details', Str::slug($product->name) . '-' . $product->id),
        ];

        // Nothing typed yet, but a category is chosen: its newest products.
        if (empty($terms) && $categoryNames) {
            return response()->json([
                'products' => Product::onSale()
                    ->whereIn('category', $categoryNames)
                    ->latest()
                    ->limit(5)
                    ->get(['id', 'name', 'price', 'image', 'category'])
                    ->map($toSuggestion),
                'categories' => [],
            ]);
        }

        if (empty($terms)) {

            // Categories with the most products first, topped up from the
            // full list so there are always a few to show.
            $bySize = Product::onSale()
                ->select('category', DB::raw('COUNT(*) as total'))
                ->groupBy('category')
                ->get()
                ->groupBy(fn ($row) => Categories::slug($row->category))
                ->map(fn ($rows) => $rows->sum('total'))
                ->filter(fn ($total, $slug) => $slug !== '')
                ->sortDesc()
                ->keys();

            $popular = $bySize->merge(array_keys(Categories::LIST))->unique()->take(8);

            return response()->json([
                'products' => [],
                'categories' => $popular->map($categoryLink)->values(),
            ]);
        }

        $query = Product::onSale()
            ->when($categoryNames, fn ($inCategory) => $inCategory->whereIn('category', $categoryNames));

        foreach ($terms as $term) {
            $like = '%' . addcslashes($term, '%_\\') . '%';

            $query->where(function ($match) use ($like) {
                $match->where('name', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        $products = $query
            // Names that start with what was typed, then names containing
            // it, then category/description-only matches.
            ->orderByRaw(
                'CASE WHEN name LIKE ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END',
                [addcslashes($q, '%_\\') . '%', '%' . addcslashes($terms[0], '%_\\') . '%']
            )
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'price', 'image', 'category'])
            ->map($toSuggestion);

        $needle = mb_strtolower($q);

        // With a category already chosen, suggesting other categories would only get in the way.
        $categories = $categoryNames ? collect() : collect(Categories::LIST)
            ->filter(fn ($label, $slug) => str_contains(mb_strtolower($label), $needle) || str_contains($slug, $needle))
            ->keys()
            ->take(3)
            ->map($categoryLink)
            ->values();

        return response()->json([
            'products' => $products,
            'categories' => $categories,
        ]);
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

        // Archived/flagged products, and products of suspended or deactivated
        // sellers, are off the storefront — only their own seller or the
        // admin can still open the page.
        if (!$product->isPurchasable()) {

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

        // How many buyers gave each star rating (5 → 1), for the breakdown bars.
        $ratingCounts = collect([5, 4, 3, 2, 1])
            ->mapWithKeys(fn ($stars) => [$stars => $reviews->where('rating', $stars)->count()])
            ->all();

        $visible = fn () => Product::onSale()
            ->where('id', '!=', $product->id);

        // Similar products: same category (old category names included), any seller.
        $categorySlug = Categories::slug($product->category);
        $relatedProducts = $visible()
            ->whereIn('category', self::categoryMap()[$categorySlug] ?? [$product->category])
            ->latest()
            ->take(4)
            ->get();

        // The seller's card and more of their products.
        $shop = $product->seller_id ? \App\Support\SellerShop::one((int) $product->seller_id) : null;

        $moreFromSeller = $product->seller_id
            ? $visible()->where('seller_id', $product->seller_id)->latest()->take(4)->get()
            : collect();

        $deliveryFee = \App\Support\DeliveryFee::baseFee();
        $freeDeliveryMin = \App\Support\DeliveryFee::FREE_SHIPPING_MIN;

        // A logged-in buyer sees the fee to their own default address
        // (by distance from the seller's town); everyone else "from ₱…".
        $deliveryTo = null;
        $deliveryEta = null;
        $viewer = session()->get('user');

        if ($viewer && ($viewer['role'] ?? '') === 'buyer' && $product->seller_id) {
            $buyerAddress = \App\Models\BuyerAddress::where('user_id', $viewer['id'])->where('is_default', true)->value('address')
                ?? DB::table('users')->where('id', $viewer['id'])->value('address');
            $town = \App\Support\PhLocations::locate($buyerAddress);

            if ($town) {
                $zone = \App\Support\ParcelRoute::zone(\App\Support\ParcelRoute::sellerLocation((int) $product->seller_id), $town);
                $deliveryFee = \App\Support\DeliveryFee::zoneFee($zone);
                $deliveryTo = trim(($town['city'] ? $town['city'] . ', ' : '') . str_replace(' (NCR)', '', $town['province']));
                $deliveryEta = \App\Support\ParcelRoute::etaLabel($zone);
            }
        }

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

        // Photo row: each option's own photos, then the photos for all options.
        $variations = $variations->sortBy('id')->values();
        ['photos' => $gallery, 'variationPhotos' => $variationPhotos] = ProductPhotos::gallery($product, $variations);

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
                'gallery',
                'variationPhotos',
                'canMessageSeller',
                'ratingCounts',
                'shop',
                'moreFromSeller',
                'deliveryFee',
                'deliveryTo',
                'deliveryEta',
                'freeDeliveryMin'
            )
        );
    }

    // The old category directory listed categories the shop does not have;
    // the Shop page has the real 16 as chips.
    public function categories()
    {
        return redirect()->route('products');
    }

    // Terms, privacy and return policies — the text the admin saves in
    // Settings, or the built-in defaults.
    public function policies()
    {
        return view('pages.policies');
    }
}
