<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * A product's photos live in product_images: each belongs to one option
 * (product_variation_id) or to all options (null). Picked from them:
 *  - each option's chip photo (product_variations.image) = its first photo;
 *  - the cover (products.image) = the photo the seller picked (is_cover), else
 *    the first option's first photo, else the first photo for all options.
 */
class ProductPhotos
{
    /** Photos for all options, per product. */
    public const MAX_GENERAL = 8;

    /** Photos per option. */
    public const MAX_PER_OPTION = 5;

    /**
     * What the picture is, not what the file is called: two uploads of the
     * same photo get the same fingerprint. Uploaded files never change (each
     * upload gets a new name), so the result is cached for good.
     */
    public static function fingerprint(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return Cache::rememberForever('photo-fingerprint:' . $path, function () use ($path) {
            $disk = Storage::disk('public');

            return $disk->exists($path) ? md5($disk->get($path)) : $path;
        });
    }

    public static function url(?string $path): string
    {
        if (!$path) {
            return '';
        }

        return str_starts_with($path, 'http') ? $path : asset('storage/' . ltrim($path, '/'));
    }

    /** Re-pick the cover and each option's chip photo from the product's photos. */
    public static function sync(Product $product): void
    {
        $images = $product->images()->get();

        if ($images->isEmpty()) {
            return; // nothing to pick from (keeps whatever the product had)
        }

        $variations = $product->variations()->orderBy('id')->get();
        $cover = null;

        foreach ($variations as $variation) {
            $first = $images->firstWhere('product_variation_id', $variation->id)?->path;

            if ($variation->image !== $first) {
                $variation->image = $first;
                $variation->save();
            }

            $cover ??= $first;
        }

        $cover = $images->firstWhere('is_cover', true)?->path
            ?? $cover
            ?? $images->firstWhere('product_variation_id', null)?->path
            ?? $images->first()->path;

        if ($product->image !== $cover) {
            $product->image = $cover;
            $product->save();
        }
    }

    /** Delete the files that no product, option or order line points at any more. */
    public static function deleteUnused(iterable $paths): void
    {
        foreach (collect($paths)->filter()->unique() as $path) {
            if (str_starts_with($path, 'http')) {
                continue;
            }

            $used = DB::table('products')->where('image', $path)->exists()
                || DB::table('product_images')->where('path', $path)->exists()
                || DB::table('product_variations')->where('image', $path)->exists()
                || DB::table('order_items')->where('variation_image', $path)->exists();

            if (!$used) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    /**
     * The product page's photo row: each option's photos, then the photos for
     * all options. 'group' is the option id or 'all'; 'fp' lets the page hide
     * a photo for all options that repeats the chosen option's photo.
     *
     * @return array{photos: Collection, variationPhotos: array<int, string>}
     */
    public static function gallery(Product $product, Collection $variations): array
    {
        $images = $product->images()->get();
        $photos = collect();
        $variationPhotos = [];

        // Products saved before photos had owners: cover + option photo only.
        if ($images->isEmpty()) {
            if ($product->image) {
                $photos->push(['url' => self::url($product->image), 'group' => 'all', 'fp' => self::fingerprint($product->image)]);
            }

            foreach ($variations as $variation) {
                if ($variation->image) {
                    $variationPhotos[$variation->id] = self::url($variation->image);
                    $photos->push(['url' => $variationPhotos[$variation->id], 'group' => $variation->id, 'fp' => self::fingerprint($variation->image)]);
                }
            }

            return ['photos' => $photos, 'variationPhotos' => $variationPhotos];
        }

        foreach ($variations as $variation) {
            $seen = [];

            foreach ($images->where('product_variation_id', $variation->id) as $image) {
                $fp = self::fingerprint($image->path);

                if (isset($seen[$fp])) {
                    continue;
                }

                $seen[$fp] = true;
                $photos->push(['url' => self::url($image->path), 'group' => $variation->id, 'fp' => $fp]);
                $variationPhotos[$variation->id] ??= self::url($image->path);
            }
        }

        $seen = [];

        foreach ($images->whereNull('product_variation_id') as $image) {
            $fp = self::fingerprint($image->path);

            if (!isset($seen[$fp])) {
                $seen[$fp] = true;
                $photos->push(['url' => self::url($image->path), 'group' => 'all', 'fp' => $fp]);
            }
        }

        return ['photos' => $photos, 'variationPhotos' => $variationPhotos];
    }

    /** Make one photo the product's cover (the seller's pick wins over the automatic one). */
    public static function setCover(Product $product, int $imageId): void
    {
        if (!$product->images()->whereKey($imageId)->exists()) {
            return;
        }

        $product->images()->where('id', '!=', $imageId)->update(['is_cover' => false]);
        $product->images()->whereKey($imageId)->update(['is_cover' => true]);
    }

    /** Next sort position inside a group (option id, or null for all options). */
    public static function nextOrder(Product $product, ?int $variationId): int
    {
        return (int) ProductImage::where('product_id', $product->id)
            ->where('product_variation_id', $variationId)
            ->max('sort_order') + 1;
    }
}
