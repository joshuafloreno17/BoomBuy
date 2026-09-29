<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyerAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'phone',
        'address',
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
