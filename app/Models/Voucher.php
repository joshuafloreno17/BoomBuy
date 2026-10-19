<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A voucher (Seller Modules S7, Buyer Modules B7):
 *
 *   kind      store (a seller's, seller-funded) · platform · shipping (BoomBuy-funded)
 *   display   claimable (listed with a Claim button) · code_only (typed only)
 *   applies   all of the seller's products · selected ones (voucher_products)
 *   time      starts_at – ends_at (older vouchers: expires_at, a date), ended early with ended_at
 *
 * The minimum spend is checked against what the buyer actually pays for the
 * items it covers (sale prices included). Per buyer, one checkout counts as
 * one use even when it became several orders.
 */
class Voucher extends Model
{
    public const CODE_PATTERN = '/^[A-Z0-9]{4,12}$/';

    protected $fillable = [
        'seller_id',
        'kind',
        'code',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_order_amount',
        'max_uses',
        'per_buyer_limit',
        'used_count',
        'expires_at',
        'starts_at',
        'ends_at',
        'ended_at',
        'display',
        'applies_to',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'voucher_products');
    }

    public function claims()
    {
        return $this->hasMany(VoucherClaim::class);
    }

    public function kind(): string
    {
        return $this->kind ?: ($this->seller_id ? 'store' : 'platform');
    }

    /** Upcoming, Ongoing, Ended (time over, ended early, used up) or Inactive. */
    public function status(): string
    {
        if ($this->ended_at || ($this->ends_at && $this->ends_at->lte(now())) || ($this->expires_at && $this->expires_at->copy()->endOfDay()->isPast())) {
            return 'Ended';
        }

        if (!$this->is_active) {
            return 'Inactive';
        }

        if ($this->starts_at && $this->starts_at->gt(now())) {
            return 'Upcoming';
        }

        return 'Ongoing';
    }

    public function usedUp(): bool
    {
        return $this->max_uses !== null && $this->used_count >= $this->max_uses;
    }

    /** Can it be used right now (time, switched on, uses left)? The minimum spend aside. */
    public function isUsableNow(): bool
    {
        return $this->status() === 'Ongoing' && !$this->usedUp();
    }

    public function isValidFor(float $subtotal): bool
    {
        return $this->isUsableNow() && $subtotal >= (float) $this->min_order_amount;
    }

    /** Why it can't be used for this subtotal (null = it can). */
    public function problemFor(float $subtotal): ?string
    {
        return match (true) {
            $this->status() === 'Upcoming' => 'Voucher ' . $this->code . ' is not available yet. It starts ' . $this->starts_at->format('M d, g:i A') . '.',
            $this->status() !== 'Ongoing' => 'Voucher ' . $this->code . ' has expired.',
            $this->usedUp() => 'Voucher ' . $this->code . ' has reached its usage limit.',
            $subtotal < (float) $this->min_order_amount => 'Voucher ' . $this->code . ' needs a minimum spend of ₱' . number_format((float) $this->min_order_amount, 2) . ' on the items it covers (you have ₱' . number_format($subtotal, 2) . '). Spend ₱' . number_format(round((float) $this->min_order_amount - $subtotal, 2), 2) . ' more to use it.',
            default => null,
        };
    }

    /** Does it cover this product? */
    public function covers(int $sellerId, int $productId): bool
    {
        if ($this->kind() !== 'store') {
            return true;
        }

        if ((int) $this->seller_id !== $sellerId) {
            return false;
        }

        return $this->applies_to !== 'selected' || $this->products()->where('products.id', $productId)->exists();
    }

    /** What the buyer pays for the lines it covers (['seller_id', 'product_id', 'price', 'quantity']). */
    public function eligibleSubtotal(array $lines): float
    {
        $selected = $this->kind() === 'store' && $this->applies_to === 'selected'
            ? $this->products()->pluck('products.id')->map(fn ($id) => (int) $id)->all()
            : null;

        $total = 0.0;

        foreach ($lines as $line) {
            if ($this->kind() === 'store' && (int) $line['seller_id'] !== (int) $this->seller_id) {
                continue;
            }

            if ($selected !== null && !in_array((int) ($line['product_id'] ?? 0), $selected, true)) {
                continue;
            }

            $total += (float) $line['price'] * (int) $line['quantity'];
        }

        return round($total, 2);
    }

    /** Checkouts this buyer used it on (cancelled orders give the use back). */
    public function usesBy(int $buyerId): int
    {
        return (int) DB::table('orders')
            ->where('buyer_id', $buyerId)
            ->where('status', '!=', 'Cancelled')
            ->where(fn ($q) => $q->where('voucher_code', $this->code)
                ->orWhere('platform_voucher_code', $this->code)
                ->orWhere('shipping_voucher_code', $this->code))
            ->selectRaw('COUNT(DISTINCT COALESCE(payment_group, CAST(id AS CHAR))) as uses')
            ->value('uses');
    }

    /** Has this buyer used it as many times as one buyer may? */
    public function usedUpBy(int $buyerId): bool
    {
        return $this->per_buyer_limit !== null && $this->usesBy($buyerId) >= (int) $this->per_buyer_limit;
    }

    public function claimedBy(int $userId): bool
    {
        return $this->claims()->where('user_id', $userId)->exists();
    }

    /**
     * The discount on what it covers: a % (up to its cap) or a fixed amount —
     * never more than the amount it applies to. For a shipping voucher that
     * amount is the shipping fee.
     */
    public function calculateDiscount(float $subtotal): float
    {
        $discount = $this->discount_type === 'percentage'
            ? round($subtotal * (min((float) $this->discount_value, 100) / 100), 2)
            : (float) $this->discount_value;

        if ($this->max_discount !== null && (float) $this->max_discount > 0) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return round(max(0, min($discount, $subtotal)), 2);
    }

    /** "₱50 off, min. spend ₱300" / "10% off up to ₱100" for a voucher card. */
    public function summary(): string
    {
        $off = $this->discount_type === 'percentage'
            ? rtrim(rtrim(number_format((float) $this->discount_value, 2), '0'), '.') . '% off' . ($this->max_discount ? ' up to ' . peso($this->max_discount) : '')
            : peso($this->discount_value) . ' off';

        if ($this->kind() === 'shipping') {
            $off = 'Free shipping ' . ($this->discount_type === 'percentage' ? $off : 'up to ' . peso($this->discount_value));
        }

        return $off . ((float) $this->min_order_amount > 0 ? ', min. spend ' . peso($this->min_order_amount) : '');
    }

    /** Claimable vouchers buyers can see now (a store's, or BoomBuy's when $sellerId is null). */
    public static function claimable(?int $sellerId)
    {
        return self::query()
            ->when($sellerId, fn ($q) => $q->where('seller_id', $sellerId), fn ($q) => $q->whereNull('seller_id'))
            ->where('display', 'claimable')
            ->where('is_active', true)
            ->whereNull('ended_at')
            ->get()
            ->filter(fn (self $voucher) => $voucher->isUsableNow())
            ->values();
    }
}
