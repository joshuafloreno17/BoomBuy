<?php

namespace App\Support;

/**
 * Phones, tablets and laptops: the details a seller fills in (shown as
 * "Specifications" on the product page) and the storage sizes buyers pick
 * from (a Color × Storage option, with its own price and stock).
 */
class DeviceSpecs
{
    /** Only Electronics products can be a device. */
    public const CATEGORY = 'Electronics';

    public const DEVICE_TYPES = ['Phone', 'Tablet', 'Laptop'];

    /** The option type storage is saved under ("Choose a storage"). */
    public const STORAGE_TYPE = 'Storage';

    /** Storage sizes a seller can tick, smallest first. */
    public const STORAGE_SIZES = ['32GB', '64GB', '128GB', '256GB', '512GB', '1TB', '2TB'];

    /** key => [label, placeholder], in the order shown. */
    public const FIELDS = [
        'device_type' => ['Device type', ''],
        'brand' => ['Brand', 'e.g. Apple, Samsung, Xiaomi'],
        'model' => ['Model', 'e.g. iPhone 18 Pro Max'],
        'ram' => ['RAM', 'e.g. 8GB'],
        'screen' => ['Screen', 'e.g. 6.9" OLED, 120Hz'],
        'processor' => ['Processor', 'e.g. A19 Pro'],
        'rear_camera' => ['Rear camera', 'e.g. 48MP + 48MP + 12MP'],
        'front_camera' => ['Front camera', 'e.g. 24MP'],
        'battery' => ['Battery', 'e.g. 4,800 mAh'],
        'os' => ['Operating system', 'e.g. iOS 26, Android 16'],
        'network' => ['Network', ''],
        'sim' => ['SIM', 'e.g. Dual SIM (nano + eSIM)'],
        'warranty' => ['Warranty', 'e.g. 1 year Apple PH warranty'],
    ];

    public const NETWORKS = ['5G', '4G LTE', 'Wi-Fi only'];

    private const MAX_LENGTH = 100;

    public static function appliesTo(?string $category): bool
    {
        return $category === self::CATEGORY || Categories::slug((string) $category) === 'electronics';
    }

    /**
     * The seller's specs, cleaned: known fields only, trimmed, no blanks.
     * Null when nothing is filled in, or the product isn't Electronics.
     */
    public static function clean($input, ?string $category): ?array
    {
        if (!is_array($input) || !self::appliesTo($category)) {
            return null;
        }

        $specs = [];

        foreach (array_keys(self::FIELDS) as $key) {
            $value = preg_replace('/\s+/', ' ', trim((string) ($input[$key] ?? '')));

            if ($value === '') {
                continue;
            }

            if ($key === 'device_type' && !in_array($value, self::DEVICE_TYPES, true)) {
                continue;
            }

            if ($key === 'network' && !in_array($value, self::NETWORKS, true)) {
                continue;
            }

            $specs[$key] = mb_substr($value, 0, self::MAX_LENGTH);
        }

        return $specs ?: null;
    }

    /** [label => value] for the product page, in the order of FIELDS. */
    public static function rows(?array $specs): array
    {
        $rows = [];

        foreach (self::FIELDS as $key => [$label]) {
            if (!empty($specs[$key])) {
                $rows[$label] = $specs[$key];
            }
        }

        return $rows;
    }

    /** "128GB" → 131072 (MB), so 1TB sorts after 512GB. Unknown sizes go last. */
    public static function storageSize(string $value): int
    {
        if (!preg_match('/^\s*(\d+(?:\.\d+)?)\s*(GB|TB)\s*$/i', $value, $m)) {
            return PHP_INT_MAX;
        }

        return (int) round((float) $m[1] * (strtoupper($m[2]) === 'TB' ? 1048576 : 1024));
    }

    /** Storage values smallest first ("64GB", "256GB", "1TB"); anything else keeps its order. */
    public static function sortStorage(iterable $values): array
    {
        $values = array_values(array_unique(array_map('strval', is_array($values) ? $values : iterator_to_array($values))));

        $order = array_flip($values);
        usort($values, fn ($a, $b) => [self::storageSize($a), $order[$a]] <=> [self::storageSize($b), $order[$b]]);

        return $values;
    }

    /**
     * A product's options with storage smallest first (64GB, 256GB, 1TB),
     * each color keeping its place. Options without storage keep their order.
     */
    public static function sortOptions(\Illuminate\Support\Collection $variations): \Illuminate\Support\Collection
    {
        if (!$variations->contains(fn ($v) => self::isStorage($v->option2_type) || self::isStorage($v->variation_type))) {
            return $variations->values();
        }

        $firstOrder = $variations->pluck('variation_value')->unique()->values()->flip();

        return $variations->sortBy(fn ($v) => self::isStorage($v->variation_type)
            ? [0, self::storageSize((string) $v->variation_value), $v->id]
            : [$firstOrder[$v->variation_value], self::storageSize((string) $v->option2_value), $v->id])->values();
    }

    public static function isStorage(?string $type): bool
    {
        return strcasecmp(trim((string) $type), self::STORAGE_TYPE) === 0;
    }
}
