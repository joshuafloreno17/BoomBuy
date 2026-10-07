<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Support\Categories;
use App\Support\ProductPhotos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * A seller adding or editing a product: their registered category only,
 * no duplicate names in their shop, real images, options that never bring
 * the price to ₱0.
 *
 * Photos: each option can have its own (up to ProductPhotos::MAX_PER_OPTION),
 * plus photos for all options (up to ProductPhotos::MAX_GENERAL). The cover
 * and each option's chip photo are picked from them (ProductPhotos::sync).
 */
class ProductService
{
    private const BAD_IMAGE = 'Product image must be a JPG, JPEG, PNG, or WEBP image no larger than 5MB.';

    /** Photos for all options, per product (kept for older callers). */
    public const MAX_PHOTOS = ProductPhotos::MAX_GENERAL;

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
     * @param  ?UploadedFile  $image  a photo for all options, placed first (older forms)
     * @param  array  $variations  [['type', 'value', 'price_adjustment', 'stock', 'photos' => UploadedFile[], 'image' => ?UploadedFile], ...]
     * @param  UploadedFile[]  $photos  photos for all options
     * @param  string  $coverRef  the seller's cover pick: "all:N" (Nth photo for all options) or "opt:I:N" (option row I, Nth photo)
     *
     * @throws ActionFailed
     */
    public function create(int $sellerId, array $input, ?UploadedFile $image, array $variations = [], array $photos = [], string $coverRef = ''): Product
    {
        [$name, $category, $price, $stock, $description, $discount] = $this->fields($sellerId, $input);

        if ($name === '' || $category === '' || $price <= 0 || $stock <= 0 || $description === '') {
            throw new ActionFailed('Please complete all product fields.');
        }

        $categoryName = Categories::LIST[strtolower($category)] ?? null;

        if ($categoryName === null) {
            throw new ActionFailed('Please select a valid product category.');
        }

        $this->ensureUniqueName($sellerId, $name);

        $general = $this->files(array_merge([$image], $photos));
        $this->checkCount($general, ProductPhotos::MAX_GENERAL, 'for all options');
        $total = count($general);

        // An option's extra price can be negative (a cheaper option) but must
        // never bring the final price to ₱0 or below.
        foreach ($variations as $i => $variation) {
            if (trim($variation['type'] ?? '') === '' || trim($variation['value'] ?? '') === '') {
                continue;
            }

            // Color × Size: with a second type, every row needs its second value.
            if (trim($variation['type2'] ?? '') !== '' && trim($variation['value2'] ?? '') === '') {
                throw new ActionFailed('Enter the ' . trim($variation['type2']) . ' for "' . trim($variation['value']) . '".');
            }

            if (Product::pricing($price, $discount)['price'] + (float) ($variation['price_adjustment'] ?? 0) <= 0) {
                throw new ActionFailed('The option "' . trim($variation['value']) . '" would make the price ₱0 or less. Please lower its discount.');
            }

            $variations[$i]['photos'] = $this->files(array_merge([$variation['image'] ?? null], (array) ($variation['photos'] ?? [])));
            $this->checkCount($variations[$i]['photos'], ProductPhotos::MAX_PER_OPTION, 'for "' . trim($variation['value']) . '"');
            $total += count($variations[$i]['photos']);
        }

        if ($total === 0) {
            throw new ActionFailed('Please add at least one product photo.');
        }

        $product = Product::create([
            'seller_id' => $sellerId,
            'name' => $name,
            'category' => $categoryName,
            ...Product::pricing($price, $discount),
            'stock' => $stock,
            'description' => $description,
        ]);

        $stored = ['all' => $this->storeFiles($product, null, $general)];

        $seen = [];

        foreach ($variations as $row => $variation) {
            $type = trim($variation['type'] ?? '');
            $value = trim($variation['value'] ?? '');

            if ($type === '' || $value === '') {
                continue;
            }

            $type2 = trim($variation['type2'] ?? '');
            $value2 = trim($variation['value2'] ?? '');

            // The same option typed twice on the form is saved once.
            $key = strtolower(ProductVariation::normalizeType($type) . '|' . ProductVariation::normalizeValue($value) . '|' . ProductVariation::normalizeValue($value2));

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            $option = ProductVariation::create([
                'product_id' => $product->id,
                'variation_type' => $type,
                'variation_value' => $value,
                'option2_type' => $type2 !== '' ? $type2 : null,
                'option2_value' => $type2 !== '' ? $value2 : null,
                'price_adjustment' => (float) ($variation['price_adjustment'] ?? 0),
                'stock' => max(0, (int) ($variation['stock'] ?? 0)),
            ]);

            $stored['opt:' . $row] = $this->storeFiles($product, $option->id, $variation['photos'] ?? []);
        }

        // The photo the seller marked as the Shop cover.
        if (preg_match('/^(all|opt:\d+):(\d+)$/', $coverRef, $m) && isset($stored[$m[1]][(int) $m[2]])) {
            ProductPhotos::setCover($product, $stored[$m[1]][(int) $m[2]]->id);
        }

        ProductPhotos::sync($product);

        return $product->fresh();
    }

    /**
     * @param  array  $input  name, category, price, stock, description
     * @param  ?UploadedFile  $image  a photo for all options, placed first (older forms)
     * @param  array  $gallery  'add' => UploadedFile[] for all options, 'option_add' => [option id => UploadedFile[]],
     *                          'remove' => photo ids, 'first' => photo id to move first in its group,
     *                          'cover' => photo id to use as the Shop cover
     *
     * @throws ActionFailed
     */
    public function update(Product $product, int $sellerId, array $input, ?UploadedFile $image, array $gallery = []): Product
    {
        [$name, $category, $price, $stock, $description, $discount] = $this->fields($sellerId, $input);

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
        if ($product->variations()->exists() && Product::pricing($price, $discount)['price'] + (float) $product->variations()->min('price_adjustment') <= 0) {
            throw new ActionFailed('At this price one of your options would cost ₱0 or less. Raise the price or change that option\'s extra price first.');
        }

        // ---- photos: check everything before changing anything ----
        $coverBefore = $product->image;
        $remove = array_map('intval', (array) ($gallery['remove'] ?? []));
        $kept = $product->images()->whereNotIn('id', $remove)->get();

        $general = $this->files(array_merge([$image], (array) ($gallery['add'] ?? [])));
        $this->checkCount($general, ProductPhotos::MAX_GENERAL - $kept->whereNull('product_variation_id')->count(), 'for all options');

        $optionIds = $product->variations()->pluck('id')->all();
        $optionAdd = [];

        foreach ((array) ($gallery['option_add'] ?? []) as $optionId => $files) {
            if (!in_array((int) $optionId, $optionIds, true)) {
                continue;
            }

            $optionAdd[(int) $optionId] = $this->files((array) $files);
            $this->checkCount($optionAdd[(int) $optionId], ProductPhotos::MAX_PER_OPTION - $kept->where('product_variation_id', (int) $optionId)->count(), 'for this option');
        }

        $hadPhotos = $product->images()->exists() || $product->image;

        if ($hadPhotos && $kept->isEmpty() && !$general && !array_filter($optionAdd)) {
            throw new ActionFailed('Please keep at least one product photo.');
        }

        $product->update([
            'name' => $name,
            'category' => $categoryName,
            ...Product::pricing($price, $discount),
            'stock' => $stock,
            'description' => $description,
        ]);

        $removedPaths = $product->images()->whereIn('id', $remove)->pluck('path')->all();
        $product->images()->whereIn('id', $remove)->delete();

        // A photo uploaded on the old single "cover" field goes first.
        $this->storeFiles($product, null, $general, $image ? 'first' : 'last');

        foreach ($optionAdd as $optionId => $files) {
            $this->storeFiles($product, $optionId, $files);
        }

        if ($firstId = (int) ($gallery['first'] ?? 0)) {
            $this->moveFirst($product, $firstId);
        }

        // Only a new pick counts: the form always sends the current cover too,
        // and that must not pin an automatic cover.
        $coverId = (int) ($gallery['cover'] ?? 0);

        if ($coverId && $product->images()->whereKey($coverId)->value('path') !== $coverBefore) {
            ProductPhotos::setCover($product, $coverId);
        }

        ProductPhotos::sync($product);
        ProductPhotos::deleteUnused($removedPaths);

        return $product->fresh();
    }

    /** Put one photo first in its group (all options, or its option). */
    public function moveFirst(Product $product, int $imageId): void
    {
        $photo = $product->images()->whereKey($imageId)->first();

        if (!$photo) {
            return;
        }

        $min = (int) ProductImage::where('product_id', $product->id)
            ->where('product_variation_id', $photo->product_variation_id)
            ->min('sort_order');

        $photo->update(['sort_order' => $min - 1]);
    }

    /**
     * Store photos into a group: null = for all options, else an option id.
     *
     * @param  UploadedFile[]  $files
     * @return ProductImage[] the new rows, in the same order
     */
    public function storeFiles(Product $product, ?int $optionId, array $files, string $where = 'last'): array
    {
        if (!$files) {
            return [];
        }

        $order = $where === 'first'
            ? (int) ProductImage::where('product_id', $product->id)->where('product_variation_id', $optionId)->min('sort_order') - count($files) - 1
            : ProductPhotos::nextOrder($product, $optionId) - 1;

        $created = [];

        foreach ($files as $file) {
            $created[] = $product->images()->create([
                'product_variation_id' => $optionId,
                'path' => $file->store('products', 'public'),
                'sort_order' => ++$order,
            ]);
        }

        return $created;
    }

    /**
     * Valid uploaded images only (empty slots dropped).
     *
     * @return UploadedFile[]
     *
     * @throws ActionFailed
     */
    public function files(array $files): array
    {
        $files = array_values(array_filter($files, fn ($f) => $f instanceof UploadedFile));

        foreach ($files as $file) {
            $this->checkImage($file);
        }

        return $files;
    }

    /** @throws ActionFailed */
    private function checkCount(array $files, int $room, string $where): void
    {
        if (count($files) > max(0, $room)) {
            throw new ActionFailed('Too many photos ' . $where . '. Each option can have ' . ProductPhotos::MAX_PER_OPTION
                . ' photos, and the product ' . ProductPhotos::MAX_GENERAL . ' for all options.');
        }
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

        $discount = trim((string) ($input['discount_percent'] ?? ''));

        if ($discount !== '' && (!ctype_digit($discount) || (int) $discount > Product::MAX_DISCOUNT)) {
            throw new ActionFailed('Discount must be a whole number from 0 to ' . Product::MAX_DISCOUNT . '%.');
        }

        return [
            $name,
            $category,
            (float) ($input['price'] ?? 0),
            (int) ($input['stock'] ?? 0),
            trim((string) ($input['description'] ?? '')),
            (int) $discount,
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
