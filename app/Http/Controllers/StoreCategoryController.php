<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StoreCategory;
use Illuminate\Support\Facades\DB;

/**
 * Seller → Store Categories: the store's own categories for its shop page
 * (add, rename, reorder, hide/show, delete) and which products are in them.
 * Platform categories stay the admin's; sellers never create or rename those.
 */
class StoreCategoryController extends Controller
{
    public function index()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $categories = StoreCategory::ordered((int) $user['id']);

        // What deleting each one would affect: its own products and its subcategories'.
        $affected = $categories->mapWithKeys(fn ($category) => [$category->id => [
            'subcategories' => $categories->where('parent_id', $category->id)->pluck('name')->values()->all(),
            'products' => $this->productIdsUnder($category)->count(),
        ]]);

        $uncategorized = Product::where('seller_id', $user['id'])
            ->whereDoesntHave('storeCategories')
            ->count();

        return view('pages.seller.store-categories', [
            'user' => $user,
            'categories' => $categories,
            'parents' => $categories->whereNull('parent_id')->values(),
            'affected' => $affected,
            'uncategorized' => $uncategorized,
        ]);
    }

    public function store()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $sellerId = (int) $user['id'];
        $name = $this->validName($sellerId);

        if (!is_string($name)) {
            return $name;
        }

        $parentId = (int) request('parent_id') ?: null;

        if ($parentId) {
            $parent = StoreCategory::where('seller_id', $sellerId)->find($parentId);

            if (!$parent) {
                return back()->withInput()->withErrors(['parent_id' => 'Choose one of your own categories as the parent.']);
            }

            // Two levels at most: a subcategory can't hold subcategories.
            if ($parent->parent_id) {
                return back()->withInput()->withErrors(['parent_id' => 'Store categories have at most 2 levels, so a subcategory can\'t have its own subcategories.']);
            }
        }

        StoreCategory::create([
            'seller_id' => $sellerId,
            'parent_id' => $parentId,
            'name' => $name,
            'sort_order' => (int) StoreCategory::where('seller_id', $sellerId)->where('parent_id', $parentId)->max('sort_order') + 1,
        ]);

        return redirect()->route('seller.store-categories')->with('success', 'Category "' . $name . '" added.');
    }

    public function update($id)
    {
        [$user, $category] = $this->own($id);

        if (!$category) {
            return $user;
        }

        $name = $this->validName((int) $user['id'], $category->id);

        if (!is_string($name)) {
            return $name;
        }

        $category->update(['name' => $name]);

        return redirect()->route('seller.store-categories')->with('success', 'Category renamed to "' . $name . '".');
    }

    /** Move a category to the top, up or down among its siblings. */
    public function move($id)
    {
        [$user, $category] = $this->own($id);

        if (!$category) {
            return $user;
        }

        $siblings = StoreCategory::where('seller_id', $category->seller_id)
            ->where('parent_id', $category->parent_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $from = $siblings->search(fn ($c) => $c->id === $category->id);
        $to = match (request('direction')) {
            'top' => 0,
            'up' => max(0, $from - 1),
            'down' => min($siblings->count() - 1, $from + 1),
            default => $from,
        };

        $moved = $siblings->pull($from);
        $siblings->splice($to, 0, [$moved]);

        DB::transaction(function () use ($siblings) {
            foreach ($siblings->values() as $position => $sibling) {
                $sibling->update(['sort_order' => $position + 1]);
            }
        });

        return redirect()->route('seller.store-categories')->with('success', '"' . $category->name . '" moved.');
    }

    /** Hide from / show on the store page. Its products stay on sale either way. */
    public function toggle($id)
    {
        [$user, $category] = $this->own($id);

        if (!$category) {
            return $user;
        }

        $category->update(['is_hidden' => !$category->is_hidden]);

        return redirect()->route('seller.store-categories')->with('success', $category->is_hidden
            ? '"' . $category->name . '" is hidden from your store page. Its products are still for sale.'
            : '"' . $category->name . '" is shown on your store page again.');
    }

    /**
     * Delete a category (and its subcategories). Its products are never
     * deleted or hidden: they only leave the category (→ Uncategorized).
     */
    public function destroy($id)
    {
        [$user, $category] = $this->own($id);

        if (!$category) {
            return $user;
        }

        $count = $this->productIdsUnder($category)->count();
        $name = $category->name;

        // Subcategories go with it (cascade), and so do their product links.
        $category->delete();

        return redirect()->route('seller.store-categories')->with('success', 'Category "' . $name . '" deleted. '
            . $count . ' ' . \Illuminate\Support\Str::plural('product', $count) . ' left it; products in no other category are now Uncategorized.');
    }

    /** Which store categories one product is in (up to 3). */
    public function assign($productId)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::where('seller_id', $user['id'])->find((int) $productId);

        if (!$product) {
            abort(404);
        }

        $error = self::syncProduct($product, (array) request('store_categories', []));

        if ($error) {
            return back()->withInput()->withErrors(['store_categories' => $error]);
        }

        return back()->with('success', 'Store categories saved.');
    }

    /**
     * Put a product in exactly these store categories of its own seller.
     * Returns an error message, or null when saved.
     */
    public static function syncProduct(Product $product, array $categoryIds): ?string
    {
        $ids = collect($categoryIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->count() > StoreCategory::MAX_PER_PRODUCT) {
            return 'A product can be in at most ' . StoreCategory::MAX_PER_PRODUCT . ' store categories.';
        }

        $own = StoreCategory::where('seller_id', $product->seller_id)->whereIn('id', $ids)->pluck('id');

        if ($own->count() !== $ids->count()) {
            return 'Choose only your own store categories.';
        }

        $product->storeCategories()->sync($own->all());

        return null;
    }

    /** The trimmed name if it is valid and free in this store, else the redirect back. */
    private function validName(int $sellerId, ?int $ignoreId = null)
    {
        $name = preg_replace('/\s+/', ' ', trim((string) request('name')));

        if ($name === '') {
            return back()->withInput()->withErrors(['name' => 'Please enter a category name.']);
        }

        if (mb_strlen($name) > StoreCategory::NAME_MAX) {
            return back()->withInput()->withErrors(['name' => 'A category name can have at most ' . StoreCategory::NAME_MAX . ' characters.']);
        }

        if (StoreCategory::nameTaken($sellerId, $name, $ignoreId)) {
            return back()->withInput()->withErrors(['name' => 'Your store already has a category named "' . $name . '".']);
        }

        return $name;
    }

    /** [$sessionUser, $category] for the seller's own category, or [$redirect/404, null]. */
    private function own($id): array
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return [$user, null];
        }

        $category = StoreCategory::where('seller_id', $user['id'])->find((int) $id);

        if (!$category) {
            abort(404);
        }

        return [$user, $category];
    }

    /** Products in this category or its subcategories. */
    private function productIdsUnder(StoreCategory $category)
    {
        $categoryIds = StoreCategory::where('parent_id', $category->id)->pluck('id')->push($category->id);

        return DB::table('product_store_category')
            ->whereIn('store_category_id', $categoryIds)
            ->distinct()
            ->pluck('product_id');
    }
}
