<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\BuyerAddress;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Support\Categories;
use App\Support\DeliveryFee;
use App\Support\DeviceSpecs;
use App\Support\ParcelRoute;
use App\Support\PhLocations;
use App\Support\ProductPhotos;
use App\Support\SellerShop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Browsing the shop: landing page, home, search and filters, a product, a
 * seller's shop. Same visibility rules as the website (Product::onSale).
 * Open to guests; a signed-in buyer also gets their wishlist and delivery fee.
 */
class CatalogController extends MobileController
{
    private const PER_PAGE = 20;

    private const SORTS = ['newest', 'price_low', 'price_high', 'rating', 'best_selling'];

    public function home(Request $request): JsonResponse
    {
        $buyerId = $this->viewerBuyerId($request);
        $wishlist = $this->wishlistIds($buyerId);

        $visible = fn () => Product::onSale()
            ->withSellableStock()
            ->withSoldCount()
            ->withCount(['reviews', 'variations'])
            ->withAvg('reviews', 'rating');

        $sections = [
            'latest' => $visible()->latest()->take(10)->get(),
            'best_selling' => $visible()->orderByDesc('sold_count')->orderByDesc('id')->take(10)->get(),
            'top_rated' => $visible()->has('reviews')->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count')->take(10)->get(),
            'budget' => $visible()->where('price', '<', 1000)->latest()->take(10)->get(),
        ];

        $shops = SellerShop::many(collect($sections)->flatten()->pluck('seller_id'));

        $activeOrders = DB::table('orders')
            ->where('buyer_id', $buyerId)
            ->whereNotIn('status', array_merge(\App\Support\OrderStatus::DONE, ['Cancelled', 'Returned to Seller', 'Returned']))
            ->whereNull('buyer_refused_at')
            ->count();

        return response()->json([
            'categories' => self::categoryList(),
            'sections' => collect($sections)->map(fn ($products) => $products->map(fn ($p) => $this->productCard($p, $wishlist, $shops))->values()),
            'active_orders' => $activeOrders,
            'free_shipping_min' => DeliveryFee::freeShippingMin(),
        ]);
    }

    /**
     * The website's landing page (ShopController::home) for the app: the
     * hero products (best sellers, topped up with the newest), "Just
     * listed", the first 8 categories with their notes, and the free
     * shipping minimum. Open to guests.
     */
    public function landing(Request $request): JsonResponse
    {
        $wishlist = $this->wishlistIds($this->viewerBuyerId($request));

        $visible = fn () => Product::onSale()
            ->withSellableStock()
            ->withSoldCount()
            ->withCount(['reviews', 'variations'])
            ->withAvg('reviews', 'rating');

        $hero = $visible()->orderByDesc('sold_count')->orderByDesc('id')->take(4)->get()
            ->filter(fn ($p) => (int) $p->sold_count > 0)
            ->values();

        if ($hero->count() < 4) {
            $hero = $hero->concat($visible()->whereNotIn('products.id', $hero->pluck('id'))->latest()->take(4 - $hero->count())->get());
        }

        $justListed = $visible()->latest()->take(8)->get();
        $shops = SellerShop::many($hero->pluck('seller_id')->concat($justListed->pluck('seller_id')));

        $notes = [
            'electronics' => 'Gadgets & devices',
            'womens-fashion' => 'Style & clothing',
            'mens-fashion' => 'Everyday style',
            'kids-baby' => 'For little ones',
            'home-living' => 'Home essentials',
            'sports-outdoors' => 'Gear for moving',
            'beauty-personal-care' => 'Skin, hair & more',
            'food-beverages' => 'Snacks & pantry',
        ];

        return response()->json([
            'hero' => $hero->map(fn ($p) => $this->productCard($p, $wishlist, $shops))->values(),
            'just_listed' => $justListed->map(fn ($p) => $this->productCard($p, $wishlist, $shops))->values(),
            'categories' => collect(array_slice(Categories::LIST, 0, 8, true))
                ->map(fn ($label, $slug) => ['slug' => $slug, 'label' => $label, 'note' => $notes[$slug] ?? null])
                ->values(),
            'all_categories' => self::categoryList(),
            'free_shipping_min' => DeliveryFee::freeShippingMin(),
            'register_url' => route('register'),
            'seller_register_url' => route('seller.register'),
            'rider_apply_url' => route('rider.apply'),
        ]);
    }

    public function categories(): JsonResponse
    {
        $counts = Product::onSale()
            ->select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->get()
            ->groupBy(fn ($row) => Categories::slug($row->category))
            ->map(fn ($rows) => (int) $rows->sum('total'));

        return response()->json([
            'categories' => collect(self::categoryList())
                ->map(fn ($c) => $c + ['count' => $counts[$c['slug']] ?? 0])
                ->values(),
        ]);
    }

    /** ?search=&category=&min=&max=&rating=&in_stock=1&sort=&page=&seller= */
    public function products(Request $request): JsonResponse
    {
        $buyerId = $this->viewerBuyerId($request);

        $category = (string) $request->query('category');
        $category = isset(Categories::LIST[$category]) ? $category : '';
        $search = trim(mb_substr((string) $request->query('search'), 0, 100));
        $min = is_numeric($request->query('min')) ? max(0, (float) $request->query('min')) : null;
        $max = is_numeric($request->query('max')) ? max(0, (float) $request->query('max')) : null;

        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        $rating = in_array((int) $request->query('rating'), [3, 4], true) ? (int) $request->query('rating') : 0;
        $inStock = $request->boolean('in_stock');
        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'newest';
        $sellerId = (int) $request->query('seller') ?: null;
        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        $query = Product::onSale()
            ->withSellableStock()
            ->withSoldCount()
            ->withCount(['reviews', 'variations'])
            ->withAvg('reviews', 'rating')
            ->when($sellerId, fn ($q) => $q->where('seller_id', $sellerId))
            ->when($category, fn ($q) => $q->whereIn('category', $this->categoryNames($category)))
            ->when(!empty($terms), function ($q) use ($terms) {
                // Each word somewhere in the name, category or description.
                foreach ($terms as $term) {
                    $like = '%' . addcslashes($term, '%_\\') . '%';
                    $q->where(fn ($m) => $m->where('name', 'like', $like)->orWhere('category', 'like', $like)->orWhere('description', 'like', $like));
                }
            })
            ->when($min !== null, fn ($q) => $q->where('price', '>=', $min))
            ->when($max !== null, fn ($q) => $q->where('price', '<=', $max))
            ->when($inStock, fn ($q) => $q->whereRaw(Product::SELLABLE_STOCK_SQL . ' > 0'))
            ->when($rating, fn ($q) => $q->whereRaw('(select avg(rating) from product_reviews where product_reviews.product_id = products.id) >= ?', [$rating]));

        match ($sort) {
            'price_low' => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),
            'best_selling' => $query->orderByDesc('sold_count'),
            default => $query->orderByDesc('created_at'),
        };

        $page = $query->orderByDesc('id')->paginate(self::PER_PAGE);
        $wishlist = $this->wishlistIds($buyerId);
        $shops = SellerShop::many($page->getCollection()->pluck('seller_id'));

        return response()->json([
            'products' => $page->getCollection()->map(fn ($p) => $this->productCard($p, $wishlist, $shops))->values(),
            'page' => $page->currentPage(),
            'has_more' => $page->hasMorePages(),
            'total' => $page->total(),
        ]);
    }

    public function product(Request $request, int $id): JsonResponse
    {
        $buyerId = $this->viewerBuyerId($request);
        $product = Product::withSoldCount()->find($id);

        if (!$product || !$product->isPurchasable()) {
            return $this->fail('This product is no longer available.', 404);
        }

        $variations = DeviceSpecs::sortOptions(ProductVariation::where('product_id', $product->id)->orderBy('id')->get());
        ['photos' => $gallery, 'variationPhotos' => $variationPhotos] = ProductPhotos::gallery($product, $variations);

        $reviews = DB::table('product_reviews')
            ->join('users', 'users.id', '=', 'product_reviews.buyer_id')
            ->where('product_reviews.product_id', $product->id)
            ->orderByDesc('product_reviews.created_at')
            ->get(['product_reviews.rating', 'product_reviews.review', 'product_reviews.seller_reply', 'product_reviews.created_at', 'users.name as buyer_name']);

        // The fee to the buyer's own default address, by distance from the seller's town.
        $deliveryFee = DeliveryFee::baseFee();
        $deliveryTo = null;
        $deliveryEta = null;
        $address = BuyerAddress::where('user_id', $buyerId)->where('is_default', true)->value('address')
            ?? DB::table('users')->where('id', $buyerId)->value('address');
        $town = PhLocations::locate($address);

        if ($town && $product->seller_id) {
            $zone = ParcelRoute::zone(ParcelRoute::sellerLocation((int) $product->seller_id), $town);
            $deliveryFee = DeliveryFee::zoneFee($zone);
            $deliveryTo = trim(($town['city'] ? $town['city'] . ', ' : '') . str_replace(' (NCR)', '', $town['province']));
            $deliveryEta = ParcelRoute::etaLabel($zone);
        }

        $more = Product::onSale()
            ->withSellableStock()
            ->withCount(['reviews', 'variations'])
            ->withAvg('reviews', 'rating')
            ->where('id', '!=', $product->id)
            ->whereIn('category', $this->categoryNames(Categories::slug($product->category)) ?: [$product->category])
            ->latest()
            ->take(6)
            ->get();

        $wishlist = $this->wishlistIds($buyerId);
        $stock = $variations->isNotEmpty() ? (int) $variations->sum('stock') : (int) $product->stock;

        return response()->json([
            'product' => [
                'id' => (int) $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'description' => $product->description,
                // Phones, tablets, laptops: [{label, value}] for a Specifications list.
                'specs' => collect(DeviceSpecs::rows($product->specs))->map(fn ($value, $label) => ['label' => $label, 'value' => $value])->values(),
                'price' => (float) $product->price,
                'original_price' => $product->isDiscounted() ? (float) $product->original_price : null,
                'discount' => $product->isDiscounted() ? (int) $product->discount_percent : null,
                'stock' => $stock,
                'sold' => (int) ($product->sold_count ?? 0),
                'image' => productImageUrl($product->image),
                'photos' => collect($gallery)->map(fn ($p) => ['url' => $p['url'], 'variation_id' => is_int($p['group']) ? $p['group'] : null])->values(),
                'option_types' => $variations->isNotEmpty()
                    ? array_values(array_filter([$variations->first()->variation_type, $variations->first()->hasSecondOption() ? $variations->first()->option2_type : null]))
                    : [],
                'variations' => $variations->map(fn ($v) => [
                    'id' => (int) $v->id,
                    'label' => $v->shortLabel(),
                    'option1' => $v->variation_value,
                    'option2' => $v->hasSecondOption() ? $v->option2_value : null,
                    'price' => round((float) $product->price + (float) $v->price_adjustment, 2),
                    'stock' => (int) $v->stock,
                    'image' => $variationPhotos[$v->id] ?? null,
                ])->values(),
                'rating' => $reviews->count() ? round((float) $reviews->avg('rating'), 1) : null,
                'review_count' => $reviews->count(),
                'reviews' => $reviews->take(20)->map(fn ($r) => [
                    'buyer_name' => $r->buyer_name,
                    'rating' => (int) $r->rating,
                    'review' => $r->review,
                    'seller_reply' => $r->seller_reply,
                    'created_at' => self::iso($r->created_at),
                ])->values(),
                'in_wishlist' => in_array($product->id, $wishlist),
                'delivery' => [
                    'fee' => (float) $deliveryFee,
                    'to' => $deliveryTo,
                    'eta' => $deliveryEta,
                    'free_shipping_min' => DeliveryFee::freeShippingMin(),
                ],
                'shop' => $product->seller_id ? $this->shopJson((int) $product->seller_id) : null,
            ],
            'related' => $more->map(fn ($p) => $this->productCard($p, $wishlist))->values(),
        ]);
    }

    public function shop(Request $request, int $id): JsonResponse
    {
        $isOpen = DB::table('users')->where('id', $id)->where('role', 'seller')->where('status', 'Active')->exists();

        if (!$isOpen) {
            return $this->fail('This shop is not available.', 404);
        }

        return response()->json(['shop' => $this->shopJson($id)]);
    }

    private function shopJson(int $sellerId): ?array
    {
        $shop = SellerShop::one($sellerId);

        return $shop ? [
            'id' => $shop['id'],
            'name' => $shop['name'],
            'photo' => $shop['photo'],
            'location' => $shop['location'],
            'since' => $shop['since'],
            'description' => $shop['description'],
            'products' => $shop['products'],
            'rating' => $shop['rating'],
            'reviews' => $shop['reviews'],
        ] : null;
    }

    /** Every stored category name that belongs to a shop category (old names included). */
    private function categoryNames(?string $slug): array
    {
        if (!$slug) {
            return [];
        }

        return DB::table('products')
            ->distinct()
            ->pluck('category')
            ->filter(fn ($name) => Categories::slug($name) === $slug)
            ->values()
            ->all();
    }
}
