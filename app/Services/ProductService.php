<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Support\Categories;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * A seller adding or editing a product: their registered category only,
 * no duplicate names in their shop, a real image, options that never bring
 * the price to ₱0.
 */
class ProductService
{
    private const BAD_IMAGE = 'Product image must be a JPG, JPEG, PNG, or WEBP image no larger than 5MB.';

    /** Photos per product, cover included. */
    public const MAX_PHOTOS = 8;

    /** The category slug the seller registered their shop for (null: any). */
    public function registeredCategory(int $sellerId): ?string
    {
        $declared = DB::table('seller_applications')
            ->where('user_id', $sellerId)
            ->orderByDesc('id')
            ->value('business_category');

        return Categories::slug($declared);
    }

    /**
     * @param  array  $input       name, category, price, stock, description
     * @param  array  $variations  [['type', 'value', 'price_adjustment', 'stock'], ...]
     * @param  UploadedFile[]  $photos  extra photos after the cover
     *
     * @throws ActionFailed
     */
    public function create(int $sellerId, array $input, ?UploadedFile $image, array $variations = [], array $photos = []): Product
    {
        [$name, $category, $price, $stock, $description] = $this->fields($sellerId, $input);

        if ($name === '' || $category === '' || $price <= 0 || $stock <= 0 || $description === '') {
            throw new ActionFailed('Please complete all product fields.');
        }

        $categoryName = Categories::LIST[strtolower($category)] ?? null;

        if ($categoryName === null) {
            throw new ActionFailed('Please select a valid product category.');
        }

        $this->ensureUniqueName($sellerId, $name);

        if (!$image) {
            throw new ActionFailed('Please upload a product image.');
        }

        $this->checkImage($image);
        $photos = $this->checkPhotos($photos, 0);

        // An option's extra price can be negative (a cheaper option) but must
        // never bring the final price to ₱0 or below.
        foreach ($variations as $variation) {
            if (trim($variation['type'] ?? '') === '' || trim($variation['value'] ?? '') === '') {
                continue;
            }

            if ($price + (float) ($variation['price_adjustment'] ?? 0) <= 0) {
                throw new ActionFailed('The option "' . trim($variation['value']) . '" would make the price ₱0 or less. Please lower its discount.');
            }
        }

        $product = Product::create([
            'seller_id' => $sellerId,
            'name' => $name,
            'category' => $categoryName,
            'price' => $price,
            'stock' => $stock,
            'description' => $description,
            'image' => $image->store('products', 'public'),
        ]);

        $this->storePhotos($product, $photos);

        $seen = [];

        foreach ($variations as $variation) {
            $type = trim($variation['type'] ?? '');
            $value = trim($variation['value'] ?? '');

            if ($type === '' || $value === '') {
                continue;
            }

            // The same option typed twice on the form is saved once.
            $key = strtolower(ProductVariation::normalizeType($type) . '|' . ProductVariation::normalizeValue($value));

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            ProductVariation::create([
                'product_id' => $product->id,
                'variation_type' => $type,
                'variation_value' => $value,
                'price_adjustment' => (float) ($variation['price_adjustment'] ?? 0),
                'stock' => max(0, (int) ($variation['stock'] ?? 0)),
            ]);
        }

        return $product;
    }

    /**
     * @param  array  $input  name, category, price, stock, description
     * @param  array  $gallery  'add' => UploadedFile[], 'remove' => image ids, 'cover' => image id to swap in as the cover
     *
     * @throws ActionFailed
     */
    public function update(Product $product, int $sellerId, array $input, ?UploadedFile $image, array $gallery = []): Product
    {
        [$name, $category, $price, $stock, $description] = $this->fields($sellerId, $input);

        if ($name === '' || $category === '' || $price <= 0 || $stock < 0 || $description === '') {
            throw new ActionFailed('Please complete all product fields.');
        }

        // Old categories (Smartphone, Laptop…) are converted when an existing
        // product is edited.
        $categoryName = Categories::label($category);

        if ($categoryName === null) {
            throw new ActionFailed('Please select a valid BoomBuy category.');
        }

        $this->ensureUniqueName($sellerId, $name, $product->id);

        // A cheaper option (negative extra price) must still cost more than ₱0
        // at the new base price.
        if ($product->variations()->exists() && $price + (float) $product->variations()->min('price_adjustment') <= 0) {
            throw new ActionFailed('At this price one of your options would cost ₱0 or less. Raise the price or change that option\'s extra price first.');
        }

        $remove = array_map('intval', (array) ($gallery['remove'] ?? []));
        $kept = $product->images()->whereNotIn('id', $remove)->count();
        $photos = $this->checkPhotos((array) ($gallery['add'] ?? []), $kept);

        $oldImage = $product->image;
        $imagePath = $oldImage;

        if ($image) {
            $this->checkImage($image);
            $imagePath = $image->store('products', 'public');
        }

        $product->update([
            'name' => $name,
            'category' => $categoryName,
            'price' => $price,
            'image' => $imagePath,
            'stock' => $stock,
            'description' => $description,
        ]);

        // The replaced photo is no longer used anywhere.
        if ($oldImage && $oldImage !== $imagePath && !str_starts_with($oldImage, 'http')) {
            Storage::disk('public')->delete($oldImage);
        }

        $this->updateGallery($product, $remove, $photos, (int) ($gallery['cover'] ?? 0));

        return $product;
    }

    /**
     * Trimmed fields, with the category locked to the one the shop registered for.
     *
     * @throws ActionFailed
     */
    private function fields(int $sellerId, array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $category = trim((string) ($input['category'] ?? ''));

        $registered = $this->registeredCategory($sellerId);

        if ($registered) {
            if ($category !== '' && Categories::slug($category) !== $registered) {
                throw new ActionFailed('You can only sell ' . Categories::LIST[$registered] . ' products — the category your shop is registered for.');
            }

            $category = $registered;
        }

        return [
            $name,
            $category,
            (float) ($input['price'] ?? 0),
            (int) ($input['stock'] ?? 0),
            trim((string) ($input['description'] ?? '')),
        ];
    }

    private function ensureUniqueName(int $sellerId, string $name, ?int $exceptId = null): void
    {
        $taken = Product::where('name', $name)
            ->where('seller_id', $sellerId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();

        if ($taken) {
            throw new ActionFailed('You already have a product with this name.');
        }
    }

    /**
     * Valid extra photos (empty slots dropped), within MAX_PHOTOS with the cover
     * and the $existing extra photos.
     *
     * @return UploadedFile[]
     *
     * @throws ActionFailed
     */
    private function checkPhotos(array $photos, int $existing): array
    {
        $photos = array_values(array_filter($photos, fn ($p) => $p instanceof UploadedFile));

        if (1 + $existing + count($photos) > self::MAX_PHOTOS) {
            throw new ActionFailed('A product can have up to ' . self::MAX_PHOTOS . ' photos (the cover and ' . (self::MAX_PHOTOS - 1) . ' more).');
        }

        foreach ($photos as $photo) {
            $this->checkImage($photo);
        }

        return $photos;
    }

    /** @param UploadedFile[] $photos */
    private function storePhotos(Product $product, array $photos): void
    {
        $order = (int) $product->images()->max('sort_order');

        foreach ($photos as $photo) {
            $product->images()->create([
                'path' => $photo->store('products', 'public'),
                'sort_order' => ++$order,
            ]);
        }
    }

    /** Remove ticked photos, add new ones, and optionally make one of the extra photos the cover. */
    private function updateGallery(Product $product, array $remove, array $photos, int $coverId): void
    {
        foreach ($product->images()->whereIn('id', $remove)->get() as $old) {
            if (!str_starts_with($old->path, 'http')) {
                Storage::disk('public')->delete($old->path);
            }
            $old->delete();
        }

        $this->storePhotos($product, $photos);

        // The chosen photo and the cover swap places.
        $chosen = $coverId ? $product->images()->whereKey($coverId)->first() : null;

        if ($chosen && $product->image) {
            [$cover, $chosen->path] = [$chosen->path, $product->image];
            $chosen->save();
            $product->update(['image' => $cover]);
        }
    }

    /** The file's real content is checked, not its name; store() names it from the content too. */
    private function checkImage(UploadedFile $image): void
    {
        if (!$image->isValid()) {
            throw new ActionFailed('The uploaded image is invalid.');
        }

        if (!isValidProductImage($image)) {
            throw new ActionFailed(self::BAD_IMAGE);
        }
    }
}
