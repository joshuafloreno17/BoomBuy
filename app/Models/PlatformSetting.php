<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /** All settings, read once per request (a product grid asks for the fees once per card). */
    public static function get(string $key, string $default = ''): string
    {
        $attributes = request()->attributes;

        if (!$attributes->has(self::class)) {
            $attributes->set(self::class, static::pluck('value', 'key')->all());
        }

        return $attributes->get(self::class)[$key] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        request()->attributes->remove(self::class);
    }
}
