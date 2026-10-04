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
     *
     * @throws ActionFailed
     */
    public function create(int $sellerId, array $input, ?UploadedFile $image, array $variations = []): Product
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
     *
     * @throws ActionFailed
     */
    public function update(Product $product, int $sellerId, array $input, ?UploadedFile $image): Product
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
