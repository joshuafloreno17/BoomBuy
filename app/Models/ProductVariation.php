<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    protected $fillable = [
        'product_id',
        'variation_type',
        'variation_value',
        'option2_type',
        'option2_value',
        'price_adjustment',
        'stock',
        'image',
    ];

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

    /** This option's own photos; the first is its chip photo. */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
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

    /** Has a second part (e.g. the Size in Color × Size)? */
    public function hasSecondOption(): bool
    {
        return trim((string) $this->option2_value) !== '';
    }

    /** "Red · M" — what a chip or a cart line shows. */
    public function shortLabel(): string
    {
        return $this->variation_value . ($this->hasSecondOption() ? ' · ' . $this->option2_value : '');
    }

    /** "Color: Red · Size: M" — saved on the order line. */
    public function label(): string
    {
        return $this->variation_type . ': ' . $this->variation_value
            . ($this->hasSecondOption() ? ' · ' . $this->option2_type . ': ' . $this->option2_value : '');
    }

    /** "color" or "color and size", for "Please choose a …". */
    public function kindLabel(): string
    {
        return strtolower($this->variation_type . ($this->hasSecondOption() ? ' and ' . $this->option2_type : ''));
    }

    protected static function booted(): void
    {
        static::saving(function (self $variation) {
            $variation->variation_type = self::normalizeType((string) $variation->variation_type);
            $variation->variation_value = self::normalizeValue((string) $variation->variation_value);

            // The second part goes in both halves or not at all.
            $type2 = self::normalizeValue((string) $variation->option2_type);
            $value2 = self::normalizeValue((string) $variation->option2_value);
            $variation->option2_type = $type2 !== '' && $value2 !== '' ? self::normalizeType($type2) : null;
            $variation->option2_value = $type2 !== '' && $value2 !== '' ? $value2 : null;
        });
    }
}
