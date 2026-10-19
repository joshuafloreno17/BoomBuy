<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyerAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'address',
        'region',
        'province',
        'city_municipality',
        'barangay',
        'street',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /** A buyer's addresses, default first. */
    public static function forUser(int $userId)
    {
        return self::where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();
    }
}
