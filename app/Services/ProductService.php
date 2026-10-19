<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Support\Categories;
use App\Support\DeviceSpecs;
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
        if (!empty($input['draft'])) {
            return $this->createDraft($sellerId, $input, $image, $photos);
        }

        [$name, $category, $price, $stock, $description, $discount] = $this->fields($sellerId, $input);

        // Stock 0 is allowed: the product is listed as Sold out.
        if ($name === '' || $category === '' || $price <= 0 || $stock < 0 || $description === '') {
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
            'specs' => DeviceSpecs::clean($input['specs'] ?? null, $categoryName),
            ...$this->listingDetails($input),
        ]);

        $stored =['all' => $this->storeFiles($product, null, $general)];

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
        // A draft saved again as a draft: whatever is filled in so far.
        if ($product->is_draft && !empty($input['draft'])) {
            return $this->updateDraft($product, $sellerId, $input, $image, $gallery);
        }

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

        // Publishing a draft needs at least one photo, like a new product.
        if ($product->is_draft && $kept->isEmpty() && !$general && !array_filter($optionAdd)) {
            throw new ActionFailed('Please add at least one product photo.');
        }

        $product->update([
            'name' => $name,
            'category' => $categoryName,
            ...Product::pricing($price, $discount),
            'stock' => $stock,
            'description' => $description,
            // Only when the form sent them, so other callers keep the specs.
            ...(array_key_exists('specs', $input) ? ['specs' => DeviceSpecs::clean($input['specs'], $categoryName)] : []),
            ...$this->listingDetails($input),
            // Saved with Publish: a draft goes live.
            'is_draft' => false,
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

    /**
     * Storage sizes for a phone, tablet or laptop, added to every color (or
     * as the only option when the product has none). A color with no second
     * part yet keeps its row — it becomes the first chosen size, with its
     * stock and photos — and gets one new row per other size. A combination
     * that already exists is left alone.
     *
     * @param  array  $sizes  "128GB" => extra price on top of the color's price
     * @param  int  $stock  stock for each new combination
     * @return int  combinations added or converted
     *
     * @throws ActionFailed
     */
    public function addStorage(Product $product, array $sizes, int $stock): int
    {
        if (!DeviceSpecs::appliesTo($product->category)) {
            throw new ActionFailed('Storage options are for Electronics products (phones, tablets, laptops).');
        }

        $chosen = [];

        foreach ($sizes as $size => $extra) {
            if (in_array($size, DeviceSpecs::STORAGE_SIZES, true)) {
                $chosen[$size] = round((float) $extra, 2);
            }
        }

        if (!$chosen) {
            throw new ActionFailed('Tick at least one storage size.');
        }

        $chosen = array_replace(array_flip(DeviceSpecs::sortStorage(array_keys($chosen))), $chosen);
        $stock = max(0, $stock);
        $variations = $product->variations()->orderBy('id')->get();

        $other = $variations->first(fn ($v) => $v->hasSecondOption() && !DeviceSpecs::isStorage($v->option2_type));

        if ($other) {
            throw new ActionFailed('This product already uses ' . $other->option2_type . ' as its second option. Storage can only be added to a product with colors (or no options yet).');
        }

        $tooCheap = fn (float $adjustment) => (float) $product->price + $adjustment <= 0;

        return DB::transaction(function () use ($product, $variations, $chosen, $stock, $tooCheap) {
            $added = 0;

            // No options yet: storage is the only choice.
            if ($variations->isEmpty() || $variations->every(fn ($v) => DeviceSpecs::isStorage($v->variation_type))) {
                $have = $variations->pluck('variation_value')->all();

                foreach ($chosen as $size => $extra) {
                    if (in_array($size, $have, true)) {
                        continue;
                    }

                    if ($tooCheap($extra)) {
                        throw new ActionFailed($size . ' would cost ₱0 or less. Lower its discount.');
                    }

                    ProductVariation::create([
                        'product_id' => $product->id,
                        'variation_type' => DeviceSpecs::STORAGE_TYPE,
                        'variation_value' => $size,
                        'price_adjustment' => $extra,
                        'stock' => $stock,
                    ]);
                    $added++;
                }

                return $added;
            }

            // Colors (or another first part): every color gets every size.
            foreach ($variations->groupBy('variation_value') as $rows) {
                $first = $rows->first();
                $base = $rows->contains(fn ($v) => $v->hasSecondOption()) ? 0.0 : (float) $first->price_adjustment;
                $have = $rows->pluck('option2_value')->filter()->all();
                $plain = $rows->first(fn ($v) => !$v->hasSecondOption());

                foreach ($chosen as $size => $extra) {
                    if (in_array($size, $have, true)) {
                        continue;
                    }

                    $adjustment = $base + $extra;

                    if ($tooCheap($adjustment)) {
                        throw new ActionFailed($first->variation_value . ' · ' . $size . ' would cost ₱0 or less. Lower its discount.');
                    }

                    if ($plain) {
                        // The color's own row becomes its first size (keeps its stock and photos).
                        $plain->update([
                            'option2_type' => DeviceSpecs::STORAGE_TYPE,
                            'option2_value' => $size,
                            'price_adjustment' => $adjustment,
                        ]);
                        $plain = null;
                    } else {
                        ProductVariation::create([
                            'product_id' => $product->id,
                            'variation_type' => $first->variation_type,
                            'variation_value' => $first->variation_value,
                            'option2_type' => DeviceSpecs::STORAGE_TYPE,
                            'option2_value' => $size,
                            'price_adjustment' => $adjustment,
                            'stock' => $stock,
                        ]);
                    }
                    $added++;
                }
            }

            return $added;
        });
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

    /**
     * A draft: only the name is needed; everything else may be filled in
     * later. Buyers never see it (Product::onSale skips drafts).
     *
     * @throws ActionFailed
     */
    private function createDraft(int $sellerId, array $input, ?UploadedFile $image, array $photos): Product
    {
        $name = trim((string) ($input['name'] ?? ''));

        if ($name === '') {
            throw new ActionFailed('Please enter the product name.');
        }

        $registered = $this->registeredCategory($sellerId);
        $categoryName = Categories::label($registered ?? (string) ($input['category'] ?? '')) ?? '';
        $general = $this->files(array_merge([$image], $photos));
        $this->checkCount($general, ProductPhotos::MAX_GENERAL, 'for all options');

        $product = Product::create([
            'seller_id' => $sellerId,
            'name' => $name,
            'category' => $categoryName,
            'price' => is_numeric($input['price'] ?? null) ? round((float) $input['price'], 2) : 0,
            'stock' => is_numeric($input['stock'] ?? null) ? max(0, (int) $input['stock']) : 0,
            'description' => trim((string) ($input['description'] ?? '')),
            ...$this->listingDetails($input),
            'is_draft' => true,
        ]);

        $this->storeFiles($product, null, $general);
        ProductPhotos::sync($product);

        return $product->fresh();
    }

    /** @throws ActionFailed */
    private function updateDraft(Product $product, int $sellerId, array $input, ?UploadedFile $image, array $gallery): Product
    {
        $name = trim((string) ($input['name'] ?? ''));

        if ($name === '') {
            throw new ActionFailed('Please enter the product name.');
        }

        $general = $this->files(array_merge([$image], (array) ($gallery['add'] ?? [])));
        $this->checkCount($general, ProductPhotos::MAX_GENERAL - $product->images()->whereNull('product_variation_id')->count(), 'for all options');

        $product->update([
            'name' => $name,
            'price' => is_numeric($input['price'] ?? null) ? round((float) $input['price'], 2) : $product->price,
            'stock' => is_numeric($input['stock'] ?? null) ? max(0, (int) $input['stock']) : $product->stock,
            'description' => trim((string) ($input['description'] ?? $product->description)),
            ...$this->listingDetails($input),
        ]);

        $this->storeFiles($product, null, $general);
        ProductPhotos::sync($product);

        return $product->fresh();
    }

    /** Brand, condition, shipping weight and size — only the ones the form sent. */
    private function listingDetails(array $input): array
    {
        $details = [];

        if (array_key_exists('brand', $input)) {
            $brand = trim((string) $input['brand']);
            $details['brand'] = $brand !== '' ? mb_substr($brand, 0, 60) : null;
        }

        if (array_key_exists('condition', $input)) {
            $details['condition'] = in_array($input['condition'], ['New', 'Used'], true) ? $input['condition'] : 'New';
        }

        foreach (['weight_kg', 'length_cm', 'width_cm', 'height_cm'] as $field) {
            if (array_key_exists($field, $input)) {
                $details[$field] = is_numeric($input[$field]) && (float) $input[$field] > 0 ? round((float) $input[$field], 2) : null;
            }
        }

        return $details;
    }

    private function ensureUniqueName(int $sellerId, string $name, ?int $exceptId = null): void
    {
        // Drafts don't count: a duplicated product is a draft with the same name until it is renamed.
        $taken = Product::where('name', $name)
            ->where('seller_id', $sellerId)
            ->where('is_draft', false)
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
