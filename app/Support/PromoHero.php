<?php

namespace App\Support;

use App\Models\PlatformAnnouncement;
use App\Models\Product;
use App\Models\PromoBanner;
use Illuminate\Support\Str;

/**
 * What the promo hero (landing page + buyer home) shows: the admin's live
 * promo banners first, then automatic slides (announcement, products with a
 * photo, category of the week, the app, selling on BoomBuy), so the carousel
 * always has something to rotate. Side tiles: the first live banner of each,
 * else free shipping and the best current deal.
 *
 * Every slide is a plain array the partial can render:
 *   style, eyebrow, title, text, big, icon, cta, url, image
 */
class PromoHero
{
    /**
     * @param  bool  $withAnnouncement  Buyer home: lead the automatic slides with the latest announcement.
     * @param  bool  $forGuests  Landing page: add the "sell on BoomBuy" slide.
     * @return array{main: array<int, array>, top: array, bottom: array}
     */
    public static function build(bool $withAnnouncement = false, bool $forGuests = false): array
    {
        $banners = PromoBanner::live()->orderBy('sort_order')->orderByDesc('id')->get()->groupBy('placement');

        $main = array_merge(
            ($banners->get('main') ?? collect())->map(fn ($b) => self::fromBanner($b))->values()->all(),
            self::autoMain($withAnnouncement, $forGuests)
        );

        $top = ($banners->get('side_top') ?? collect())->first();
        $bottom = ($banners->get('side_bottom') ?? collect())->first();

        return [
            'main' => $main,
            'top' => $top ? self::fromBanner($top) : self::freeShippingTile(),
            'bottom' => $bottom ? self::fromBanner($bottom) : self::dealsTile(),
        ];
    }

    private static function slide(array $fields): array
    {
        return $fields + ['style' => 'violet', 'eyebrow' => null, 'text' => null, 'big' => null, 'icon' => null, 'image' => null];
    }

    private static function fromBanner(PromoBanner $banner): array
    {
        return self::slide([
            'style' => $banner->style,
            'eyebrow' => $banner->eyebrow,
            'title' => $banner->title,
            'text' => $banner->subtitle,
            'big' => $banner->big_text,
            'cta' => $banner->button_label ?: 'Shop now',
            'url' => $banner->url(),
            'image' => $banner->imageUrl(),
        ]);
    }

    private static function autoMain(bool $withAnnouncement, bool $forGuests): array
    {
        $slides = [];

        if ($withAnnouncement) {
            $announcement = PlatformAnnouncement::where('is_active', true)->orderByDesc('created_at')->first();

            if ($announcement) {
                $slides[] = self::slide([
                    'style' => 'plum',
                    'eyebrow' => 'Announcement',
                    'title' => Str::limit($announcement->title, 60),
                    'text' => Str::limit($announcement->message, 150),
                    'icon' => 'bi-megaphone-fill',
                    'cta' => 'Start shopping',
                    'url' => route('products'),
                ]);
            }
        }

        foreach (self::productSlides() as $slide) {
            $slides[] = $slide;
        }

        if ($category = self::categoryOfTheWeek()) {
            $slides[] = $category;
        }

        // Pointless inside the Android app itself.
        if (!MobileAccess::isApp(request())) {
            $slides[] = self::slide([
                'style' => 'violet',
                'eyebrow' => 'BoomBuy app',
                'title' => 'Shop faster in the BoomBuy app',
                'text' => 'Browse, check out and track your orders on your Android phone.',
                'cta' => 'Get the app',
                'url' => route('app.download'),
                'image' => vasset('images/icon-512.png'),
            ]);
        }

        if ($forGuests) {
            $slides[] = self::slide([
                'style' => 'plum',
                'eyebrow' => 'Sell on BoomBuy',
                'title' => 'Open your shop on BoomBuy',
                'text' => 'Free to register. Reach buyers across the Philippines, with riders who pick up from your door.',
                'icon' => 'bi-shop',
                'cta' => 'Become a seller',
                'url' => route('seller.register'),
            ]);
        }

        if (count($slides) === 0) {
            $slides[] = self::freeShippingTile();
        }

        return $slides;
    }

    /** The categories the product slides come from, one product each, in this order. */
    private const SLIDE_CATEGORIES = ['electronics', 'womens-fashion', 'shoes', 'mens-fashion', 'beauty-personal-care'];

    /**
     * One product from each of the best-selling categories: its best seller,
     * else its priciest (a phone, not a cable). Only ones with a photo, so a
     * slide never shows an empty frame. Topped up from any category.
     */
    private static function productSlides(): array
    {
        $pick = fn ($query) => $query->onSale()
            ->withSoldCount()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderByDesc('sold_count')
            ->orderByDesc('price')
            ->orderByDesc('id');

        $products = collect(self::SLIDE_CATEGORIES)
            ->map(fn ($slug) => $pick(Product::where('category', Categories::LIST[$slug]))->first())
            ->filter()
            ->take(4)
            ->values();

        if ($products->count() < 3) {
            $products = $products->merge($pick(Product::whereNotIn('id', $products->pluck('id')))->take(3 - $products->count())->get());
        }

        $shops = SellerShop::many($products->pluck('seller_id'));

        return $products->values()->map(function ($product, $i) use ($shops) {
            $sold = (int) ($product->sold_count ?? 0);
            $category = Categories::label($product->category);

            return self::slide([
                'style' => $i % 2 === 0 ? 'violet' : 'lavender',
                'eyebrow' => $sold > 0 ? 'Best seller · ' . number_format($sold) . ' sold' : ($category ? 'Trending in ' . $category : 'Just listed'),
                'title' => Str::limit($product->name, 40),
                'text' => ($shops->get($product->seller_id)['name'] ?? 'BoomBuy seller') . ' · ₱' . number_format($product->price),
                'cta' => 'View product',
                'url' => route('product.details', $product->id),
                'image' => productImageUrl($product->image),
            ]);
        })->all();
    }

    /** One category with products, a different one each week. */
    private static function categoryOfTheWeek(): ?array
    {
        $counts = Product::onSale()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        // Old category names count toward their current slug.
        $bySlug = [];
        foreach ($counts as $name => $total) {
            $slug = Categories::slug($name);
            if ($slug) {
                $bySlug[$slug] = ($bySlug[$slug] ?? 0) + (int) $total;
            }
        }

        if (empty($bySlug)) {
            return null;
        }

        // Rotates through the best-selling half of the categories.
        $slugs = array_values(array_intersect(array_slice(Categories::POPULAR, 0, 8), array_keys($bySlug))) ?: array_keys($bySlug);
        $slug = $slugs[(int) now()->isoWeek() % count($slugs)];
        $label = Categories::LIST[$slug];
        $total = $bySlug[$slug];

        return self::slide([
            'style' => 'lavender',
            'eyebrow' => 'Category of the week',
            'title' => $label,
            'text' => number_format($total) . ' ' . Str::plural('product', $total) . ' from BoomBuy sellers.',
            'icon' => Categories::icon($slug),
            'cta' => 'Browse ' . $label,
            'url' => route('products', ['category' => $slug]),
        ]);
    }

    private static function freeShippingTile(): array
    {
        $min = DeliveryFee::freeShippingMin();

        return self::slide([
            'style' => 'plum',
            'eyebrow' => 'Free delivery',
            'title' => 'Free shipping from ₱' . number_format($min),
            'text' => 'Anywhere in the Philippines, even with COD.',
            'cta' => 'Start shopping',
            'url' => route('products'),
        ]);
    }

    /** "Up to -40%" when products are discounted; otherwise the payment options. */
    private static function dealsTile(): array
    {
        $best = (int) Product::onSale()->where('discount_percent', '>', 0)->whereNotNull('original_price')->max('discount_percent');

        if ($best > 0) {
            return self::slide([
                'style' => 'lavender',
                'eyebrow' => 'Deals',
                'title' => 'On sale today',
                'text' => 'Products with a price cut right now.',
                'big' => '-' . $best . '%',
                'cta' => 'See deals',
                'url' => route('products', ['deals' => 1]),
            ]);
        }

        return self::slide([
            'style' => 'light',
            'eyebrow' => 'Flexible payment',
            'title' => 'Pay your way',
            'text' => 'GCash, Maya, card or cash on delivery.',
            'cta' => 'Start shopping',
            'url' => route('products'),
        ]);
    }
}
