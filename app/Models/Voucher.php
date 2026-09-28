<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'seller_id',
        'code',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function isValidFor(float $subtotal): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        if ($subtotal < $this->min_order_amount) {
            return false;
        }

        return true;
    }

    /**
     * Never more than the subtotal it applies to — a seller's voucher must not
     * discount other sellers' items in the same order.
     */
    public function calculateDiscount(float $subtotal): float
    {
        $discount = $this->discount_type === 'percentage'
            ? round($subtotal * (min((float) $this->discount_value, 100) / 100), 2)
            : (float) $this->discount_value;

        return max(0, min($discount, $subtotal));
    }
}
