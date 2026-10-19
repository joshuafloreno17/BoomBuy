<?php

namespace App\Support;

use App\Models\ProductVariation;
use Illuminate\Http\UploadedFile;

/**
 * The Add/Edit Product form's limits (Seller Modules A16, A17, A20, A25),
 * one message per field so the seller sees exactly what to fix:
 *
 *   name 5–120 · description 20–3,000 · price ₱1.00–₱1,000,000.00 (up to
 *   2 decimals) · stock 0–99,999 (whole numbers) · weight more than 0 kg ·
 *   size (optional) more than 0 cm each · 1 to 9 photos.
 */
class ProductRules
{
    public const NAME_MIN = 5;
    public const NAME_MAX = 120;
    public const DESCRIPTION_MIN = 20;
    public const DESCRIPTION_MAX = 3000;
    public const PRICE_MIN = 1;
    public const PRICE_MAX = 1000000;
    public const STOCK_MAX = 99999;
    public const WEIGHT_MAX = 999;

    /** "1,000,000.00" → "1000000.00"; anything else as typed. */
    public static function cleanNumber(mixed $value): string
    {
        return str_replace([',', ' ', '₱'], '', trim((string) $value));
    }

    /** Is this a price the form accepts? (null = yes) */
    public static function priceError(mixed $value, string $label = 'Price'): ?string
    {
        $price = self::cleanNumber($value);

        if ($price === '') {
            return 'Please enter the ' . strtolower($label) . '.';
        }

        // 199.999 is never rounded to 200.00: three decimals are refused.
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $price)) {
            return $label . ' must be a number with up to 2 decimal places, from ₱1.00 to ₱1,000,000.00.';
        }

        if ((float) $price < self::PRICE_MIN || (float) $price > self::PRICE_MAX) {
            return $label . ' must be from ₱1.00 to ₱1,000,000.00.';
        }

        return null;
    }

    public static function stockError(mixed $value, string $label = 'Stock'): ?string
    {
        $stock = self::cleanNumber($value);

        if ($stock === '') {
            return 'Please enter the ' . strtolower($label) . '.';
        }

        if (!preg_match('/^\d+$/', $stock) || (int) $stock > self::STOCK_MAX) {
            return $label . ' must be a whole number from 0 to 99,999.';
        }

        return null;
    }

    /**
     * Every problem with a product about to be published, keyed by field.
     *
     * @param  int  $photos  photos the product will have after saving (all of them)
     * @param  ?int  $generalPhotos  of those, the photos for all options (at most 9); null = same as $photos
     * @param  bool  $hasOptions  stock is kept per option, so the product's own stock isn't asked
     * @return array<string, string>
     */
    public static function publishErrors(array $input, int $photos, bool $hasOptions = false, ?int $generalPhotos = null): array
    {
        $errors = [];

        $name = trim((string) ($input['name'] ?? ''));
        $length = mb_strlen($name);

        if ($name === '') {
            $errors['name'] = 'Please enter the product name.';
        } elseif ($length < self::NAME_MIN || $length > self::NAME_MAX) {
            $errors['name'] = 'The product name must be ' . self::NAME_MIN . ' to ' . self::NAME_MAX . ' characters (now ' . $length . ').';
        }

        $description = trim((string) ($input['description'] ?? ''));
        $length = mb_strlen($description);

        if ($description === '') {
            $errors['description'] = 'Please enter the product description.';
        } elseif ($length < self::DESCRIPTION_MIN || $length > self::DESCRIPTION_MAX) {
            $errors['description'] = 'The description must be ' . self::DESCRIPTION_MIN . ' to 3,000 characters (now ' . number_format($length) . ').';
        }

        if (trim((string) ($input['category'] ?? '')) === '') {
            $errors['category'] = 'Please choose the platform category.';
        }

        if ($error = self::priceError($input['price'] ?? '')) {
            $errors['price'] = $error;
        }

        if (!$hasOptions && ($error = self::stockError($input['stock'] ?? ''))) {
            $errors['stock'] = $error;
        }

        $weight = self::cleanNumber($input['weight_kg'] ?? '');

        if ($weight === '') {
            $errors['weight_kg'] = 'Please enter the shipping weight in kg.';
        } elseif (!is_numeric($weight) || (float) $weight <= 0 || (float) $weight > self::WEIGHT_MAX) {
            $errors['weight_kg'] = 'Weight must be a number more than 0 kg (up to ' . self::WEIGHT_MAX . ' kg).';
        }

        // Size is optional, but every part given must be more than 0 cm.
        $size = array_map(fn ($f) => self::cleanNumber($input[$f] ?? ''), ['length_cm', 'width_cm', 'height_cm']);

        if (implode('', $size) !== '') {
            foreach ($size as $part) {
                if ($part === '' || !is_numeric($part) || (float) $part <= 0) {
                    $errors['dimensions'] = 'Enter the length, width and height as numbers more than 0 cm.';
                    break;
                }
            }
        }

        $limit = trim((string) ($input['purchase_limit'] ?? ''));

        if ($limit !== '' && (!ctype_digit($limit) || (int) $limit < 1)) {
            $errors['purchase_limit'] = 'The limit per buyer must be a whole number of at least 1 (or leave it blank).';
        }

        if (isset($input['condition']) && !in_array($input['condition'], ['New', 'Used'], true)) {
            $errors['condition'] = 'Choose New or Used.';
        }

        if ($photos < 1) {
            $errors['photos'] = 'Please add at least 1 product photo (up to ' . ProductPhotos::MAX_GENERAL . ').';
        } elseif (($generalPhotos ?? $photos) > ProductPhotos::MAX_GENERAL) {
            $errors['photos'] = 'A product can have at most ' . ProductPhotos::MAX_GENERAL . ' photos.';
        }

        return $errors;
    }

    /** Uploaded files in a list (empty slots dropped). */
    public static function uploads(array $files): array
    {
        return array_values(array_filter($files, fn ($f) => $f instanceof UploadedFile));
    }

    /** Rows the seller started (any name, price, stock or SKU typed). */
    public static function filledRows(array $rows): array
    {
        return array_filter($rows, fn ($row) => is_array($row) && collect(['value', 'value2', 'value3', 'price', 'sku'])
            ->contains(fn ($field) => trim((string) ($row[$field] ?? '')) !== ''));
    }

    /**
     * Problems with the option rows of a product (Seller Modules A7, A17):
     * at most 2 variation types, 1 to 20 options per type, no empty or
     * repeated option names (case ignored), a valid price and stock on every
     * row, and SKUs used only once.
     *
     * @param  array  $rows  [i => [type, value, type2, value2, (type3, value3), price, price_adjustment, stock, sku]]
     * @param  ?int  $productId  the product being edited (its own SKUs are fine)
     * @return array<string, string>  keyed "variations" or "variations.{i}.{field}"
     */
    public static function variationErrors(array $rows, ?int $productId = null): array
    {
        $rows = self::filledRows($rows);
        $errors = [];

        if (!$rows) {
            return [];
        }

        $first = reset($rows);

        if (trim((string) ($first['type3'] ?? '')) !== '' || collect($rows)->contains(fn ($r) => trim((string) ($r['value3'] ?? '')) !== '')) {
            return ['variations' => 'A product can have at most 2 variation types (for example Color and Size).'];
        }

        $hasSecond = trim((string) ($first['type2'] ?? '')) !== '';
        $values = [1 => [], 2 => []];
        $combos = [];
        $skus = [];

        foreach ($rows as $i => $row) {
            $value = trim((string) ($row['value'] ?? ''));
            $value2 = trim((string) ($row['value2'] ?? ''));

            if ($value === '') {
                $errors["variations.$i.value"] = 'Enter a name for this option.';
            }

            if ($hasSecond && $value2 === '') {
                $errors["variations.$i.value2"] = 'Enter the ' . trim((string) $first['type2']) . ' for ' . ($value !== '' ? '"' . $value . '"' : 'this option') . '.';
            }

            // "Red" and "red" are the same colour: a buyer couldn't tell them apart.
            if ($value !== '') {
                $values[1][mb_strtolower($value)][] = $i;
            }

            if ($value2 !== '') {
                $values[2][mb_strtolower($value2)] = true;
            }

            $combo = mb_strtolower($value . '|' . $value2);

            if ($value !== '' && isset($combos[$combo])) {
                $errors["variations.$i.value"] = 'This option is listed twice.';
            }

            $combos[$combo] = true;

            if (array_key_exists('price', $row) && ($error = self::priceError($row['price']))) {
                $errors["variations.$i.price"] = $error;
            }

            if ($error = self::stockError($row['stock'] ?? '')) {
                $errors["variations.$i.stock"] = $error;
            }

            $sku = strtoupper(trim((string) ($row['sku'] ?? '')));

            if ($sku !== '') {
                if (isset($skus[$sku])) {
                    $errors["variations.$i.sku"] = 'SKU ' . $sku . ' is already used by another option.';
                } elseif (self::skuTaken($sku, $productId)) {
                    $errors["variations.$i.sku"] = 'SKU ' . $sku . ' is already used by another product.';
                }

                $skus[$sku] = true;
            }
        }

        // Case-insensitive repeats of the first option ("Red", "red" in Color).
        foreach ($values[1] as $indexes) {
            $names = collect($indexes)->map(fn ($i) => trim((string) $rows[$i]['value']))->unique();

            if ($names->count() > 1) {
                foreach ($indexes as $i) {
                    $errors["variations.$i.value"] = '"' . $names->implode('" and "') . '" are the same option. Use one name.';
                }
            }
        }

        foreach ([1 => count($values[1]), 2 => count($values[2])] as $type => $count) {
            if ($count > ProductVariation::MAX_OPTIONS_PER_TYPE) {
                $errors['variations'] = 'Each variation type can have at most ' . ProductVariation::MAX_OPTIONS_PER_TYPE . ' options.';
            }
        }

        return $errors;
    }

    /** Is the SKU used by an option of another product (case ignored)? */
    public static function skuTaken(string $sku, ?int $exceptProductId = null, ?int $exceptVariationId = null): bool
    {
        return ProductVariation::whereRaw('UPPER(sku) = ?', [strtoupper(trim($sku))])
            ->when($exceptProductId, fn ($q) => $q->where('product_id', '!=', $exceptProductId))
            ->when($exceptVariationId, fn ($q) => $q->where('id', '!=', $exceptVariationId))
            ->exists();
    }
}
