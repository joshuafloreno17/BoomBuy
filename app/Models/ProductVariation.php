<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    protected $fillable = [
        'product_id',
        'variation_type',
        'variation_value',
        'price_adjustment',
        'stock',
        'image',
    ];

    /** Suggested types for the seller's form (free text is still allowed). */
    public const COMMON_TYPES = ['Color', 'Size', 'Style', 'Material', 'Storage', 'Flavor'];

    private const TYPE_ALIASES = [
        'colour' => 'Color',
        'coloer' => 'Color',
        'colr' => 'Color',
        'kulay' => 'Color',
        'sizes' => 'Size',
        'laki' => 'Size',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Always stored as "Color", never "color" / "COLOR " / "coloer", so one
     * product can't end up with two different "types" that mean the same.
     */
    public static function normalizeType(string $type): string
    {
        $clean = strtolower(preg_replace('/\s+/', ' ', trim($type)));

        return self::TYPE_ALIASES[$clean] ?? ucwords($clean);
    }

    public static function normalizeValue(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value));
    }

    protected static function booted(): void
    {
        static::saving(function (self $variation) {
            $variation->variation_type = self::normalizeType((string) $variation->variation_type);
            $variation->variation_value = self::normalizeValue((string) $variation->variation_value);
        });
    }
}
