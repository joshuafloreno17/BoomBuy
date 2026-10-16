<?php

namespace App\Support;

use App\Models\PlatformAnnouncement;
use App\Models\Product;
use App\Models\PromoBanner;
use Illuminate\Support\Str;

/**
 * What the promo hero (landing page + buyer home) shows: the admin's live
 * promo banners first; any spot without one gets an automatic slide instead,
 * so the hero is never empty.
 *
 * Every slide is a plain array the partial can render:
 *   style, eyebrow, title, text, big, cta, url, image
 */
class PromoHero
{
    /** @return array{main: array<int, array>, top: array, bottom: array} */
    public static function build(bool $withAnnouncement = false): array
    {
        $banners = PromoBanner::live()->orderBy('sort_order')->orderByDesc('id')->get()->groupBy('placement');

        $main = ($banners->get('main') ?? collect())->map(fn ($b) => self::fromBanner($b))->values()->all();

        if (empty($main)) {
            $main = self::autoMain($withAnnouncement);
        }

        $top = ($banners->get('side_top') ?? collect())->first();
        $bottom = ($banners->get('side_bottom') ?? collect())->first();

        return [
            'main' => $main,
            'top' => $top ? self::fromBanner($top) : self::freeShippingTile(),
            'bottom' => $bottom ? self::fromBanner($bottom) : self::dealsTile(),
        ];
    }

    private static function fromBanner(PromoBanner $banner): array
    {
        return [
            'style' => $banner->style,
            'eyebrow' => $banner->eyebrow,
            'title' => $banner->title,
            'text' => $banner->subtitle,
            'big' => $banner->big_text,
            'cta' => $banner->button_label ?: 'Shop now',
            'url' => $banner->url(),
            'image' => $banner->imageUrl(),
        ];
    }

    /** No admin banner live: the announcement, then products that have a photo, else free shipping. */
    private static function autoMain(bool $withAnnouncement): array
    {
        $slides = [];

        if ($withAnnouncement) {
            $announcement = PlatformAnnouncement::where('is_active', true)->orderByDesc('created_at')->first();

            if ($announcement) {
                $slides[] = [
                    'style' => 'plum',
                    'eyebrow' => 'Announcement',
                    'title' => Str::limit($announcement->title, 60),
                    'text' => Str::limit($announcement->message, 150),
                    'big' => null,
                    'cta' => 'Start shopping',
                    'url' => route('products'),
                    'image' => null,
                ];
            }
        }

        // Best sellers first, then the newest — only ones with a photo, so the slide never shows an empty frame.
        $products = Product::onSale()
            ->withSoldCount()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderByDesc('sold_count')
            ->orderByDesc('id')
            ->take(3)
            ->get();

        $shops = SellerShop::many($products->pluck('seller_id'));

        foreach ($products as $i => $product) {
            $sold = (int) ($product->sold_count ?? 0);

            $slides[] = [
                'style' => $i % 2 === 0 ? 'violet' : 'lavender',
                'eyebrow' => $sold > 0 ? 'Best seller · ' . number_format($sold) . ' sold' : 'Just listed',
                'title' => Str::limit($product->name, 40),
                'text' => trim(($shops->get($product->seller_id)['name'] ?? 'BoomBuy seller') . ' · ₱' . number_format($product->price)),
                'big' => null,
                'cta' => 'View product',
                'url' => route('product.details', $product->id),
                'image' => productImageUrl($product->image),
            ];
        }

        if (count($slides) === 0) {
            $slides[] = self::freeShippingTile();
        }

        return $slides;
    }

    private static function freeShippingTile(): array
    {
        $min = DeliveryFee::freeShippingMin();

        return [
            'style' => 'plum',
            'eyebrow' => 'Free delivery',
            'title' => 'Free shipping from ₱' . number_format($min),
            'text' => 'Anywhere in the Philippines, even with COD.',
            'big' => null,
            'cta' => 'Start shopping',
            'url' => route('products'),
            'image' => null,
        ];
    }

    /** "Up to -40%" when products are discounted; otherwise the payment options. */
    private static function dealsTile(): array
    {
        $best = (int) Product::onSale()->where('discount_percent', '>', 0)->whereNotNull('original_price')->max('discount_percent');

        if ($best > 0) {
            return [
                'style' => 'lavender',
                'eyebrow' => 'Deals',
                'title' => 'On sale today',
                'text' => 'Products with a price cut right now.',
                'big' => '-' . $best . '%',
                'cta' => 'See deals',
                'url' => route('products', ['deals' => 1]),
                'image' => null,
            ];
        }

        return [
            'style' => 'light',
            'eyebrow' => 'Flexible payment',
            'title' => 'Pay your way',
            'text' => 'GCash, Maya, card or cash on delivery.',
            'big' => null,
            'cta' => 'Start shopping',
            'url' => route('products'),
            'image' => null,
        ];
    }
}
