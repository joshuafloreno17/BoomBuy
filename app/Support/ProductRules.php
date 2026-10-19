<?php

namespace App\Support;

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
}
