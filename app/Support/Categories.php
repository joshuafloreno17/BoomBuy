<?php

namespace App\Support;

/**
 * BoomBuy's product categories — the one list used by the seller and admin
 * product forms and by the compliance check. Forms submit the slug;
 * products.category stores the label.
 */
class Categories
{
    public const LIST = [
        'electronics' => 'Electronics',
        'womens-fashion' => "Women's Fashion",
        'mens-fashion' => "Men's Fashion",
        'kids-baby' => 'Kids & Baby',
        'home-living' => 'Home & Living',
        'sports-outdoors' => 'Sports & Outdoors',
        'beauty-personal-care' => 'Beauty & Personal Care',
        'food-beverages' => 'Food & Beverages',
        'automotive' => 'Automotive',
        'office-school' => 'Office & School',
        'pet-supplies' => 'Pet Supplies',
        'toys-games-hobbies' => 'Toys, Games & Hobbies',
        'jewelry-accessories' => 'Jewelry & Accessories',
        'shoes' => 'Shoes',
        'tools-home-improvement' => 'Tools & Home Improvement',
        'garden-outdoor' => 'Garden & Outdoor',
    ];

    /** Bootstrap Icons per category — the same mapping as the landing page. */
    public const ICONS = [
        'electronics' => 'bi-phone',
        'womens-fashion' => 'bi-handbag',
        'mens-fashion' => 'bi-bag-fill',
        'kids-baby' => 'bi-balloon-heart-fill',
        'home-living' => 'bi-house-door-fill',
        'sports-outdoors' => 'bi-trophy-fill',
        'beauty-personal-care' => 'bi-stars',
        'food-beverages' => 'bi-cup-hot-fill',
        'automotive' => 'bi-car-front-fill',
        'office-school' => 'bi-backpack2-fill',
        'pet-supplies' => 'bi-heart-fill',
        'toys-games-hobbies' => 'bi-controller',
        'jewelry-accessories' => 'bi-gem',
        'shoes' => 'bi-tag-fill',
        'tools-home-improvement' => 'bi-tools',
        'garden-outdoor' => 'bi-flower1',
    ];

    /** Icon for a slug, label or old category; a box when unknown. */
    public static function icon(?string $value): string
    {
        return self::ICONS[self::slug($value) ?? ''] ?? 'bi-box-seam-fill';
    }

    /** Categories from before the 16-category system, mapped to their new slug. */
    private const OLD_ALIASES = [
        'smartphone' => 'electronics',
        'laptop' => 'electronics',
        'audio' => 'electronics',
        'wearable' => 'electronics',
        'accessories' => 'jewelry-accessories',
    ];

    /** Label for a submitted slug (or old category / existing label), or null if invalid. */
    public static function label(?string $input): ?string
    {
        $key = strtolower(trim((string) $input));

        if (isset(self::LIST[$key])) {
            return self::LIST[$key];
        }

        if (isset(self::OLD_ALIASES[$key])) {
            return self::LIST[self::OLD_ALIASES[$key]];
        }

        // Already a label (e.g. an edit form re-submitting "Electronics").
        $slug = array_search($input, self::LIST, true);

        return $slug !== false ? self::LIST[$slug] : null;
    }

    /** Slug for a stored label or old category, for pre-selecting a <select>. */
    public static function slug(?string $value): ?string
    {
        $label = self::label($value);

        return $label !== null ? array_search($label, self::LIST, true) : null;
    }
}
