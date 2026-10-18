<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A seller's own category for their store page. Level 1 (no parent) or
 * level 2 (under a level-1 category) — never deeper.
 */
class StoreCategory extends Model
{
    public const NAME_MAX = 50;

    /** A product can be in at most this many store categories. */
    public const MAX_PER_PRODUCT = 3;

    protected $fillable = ['seller_id', 'parent_id', 'name', 'sort_order', 'is_hidden'];

    protected $casts = [
        'is_hidden' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_store_category')->withTimestamps();
    }

    /** "Home Decor › Lamps" for a subcategory. */
    public function fullName(): string
    {
        return $this->parent ? $this->parent->name . ' › ' . $this->name : $this->name;
    }

    /** Visible to buyers: not hidden, and its parent (if any) not hidden either. */
    public function isVisible(): bool
    {
        return !$this->is_hidden && !($this->parent?->is_hidden);
    }

    /**
     * The store's categories in display order: each level-1 category
     * followed by its subcategories.
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function ordered(int $sellerId, bool $visibleOnly = false)
    {
        $all = self::where('seller_id', $sellerId)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $list = collect();

        foreach ($all->whereNull('parent_id') as $parent) {
            if ($visibleOnly && $parent->is_hidden) {
                continue;
            }

            $list->push($parent);

            foreach ($all->where('parent_id', $parent->id) as $child) {
                if ($visibleOnly && $child->is_hidden) {
                    continue;
                }

                $child->setRelation('parent', $parent);
                $list->push($child);
            }
        }

        return $list->values();
    }

    /** Is the name already used in this store (case and outer spaces ignored)? */
    public static function nameTaken(int $sellerId, string $name, ?int $ignoreId = null): bool
    {
        return self::where('seller_id', $sellerId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}
