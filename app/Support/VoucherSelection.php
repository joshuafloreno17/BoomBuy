<?php

namespace App\Support;

use App\Models\Voucher;
use Illuminate\Support\Collection;

/**
 * The vouchers a buyer applied to the cart (session). Stacking rule
 * (Buyer Modules A2): per checkout at most one store voucher per store,
 * one platform voucher and one shipping voucher. Applying a second
 * voucher of the same kind replaces the first — it never stacks.
 */
class VoucherSelection
{
    public const KEY = 'applied_vouchers';

    /** Older sessions kept one code here. */
    private const LEGACY_KEY = 'applied_voucher';

    /** @return string[] */
    public static function codes(): array
    {
        $codes = (array) session()->get(self::KEY, []);

        if ($legacy = session()->get(self::LEGACY_KEY)) {
            $codes[] = $legacy;
        }

        return array_values(array_unique(array_filter(array_map(fn ($c) => strtoupper(trim((string) $c)), $codes))));
    }

    /** @return Collection<int, Voucher> */
    public static function vouchers(): Collection
    {
        $codes = self::codes();

        return $codes ? Voucher::whereIn('code', $codes)->get() : collect();
    }

    /** The slot a voucher takes: "store:12", "platform" or "shipping". */
    public static function slot(Voucher $voucher): string
    {
        return $voucher->kind() === 'store' ? 'store:' . (int) $voucher->seller_id : $voucher->kind();
    }

    /** Add it, replacing whatever was in the same slot. Returns the code it replaced, if any. */
    public static function add(Voucher $voucher): ?string
    {
        $replaced = null;
        $keep = [];

        foreach (self::vouchers() as $current) {
            if ($current->code === $voucher->code) {
                continue;
            }

            if (self::slot($current) === self::slot($voucher)) {
                $replaced = $current->code;
                continue;
            }

            $keep[] = $current->code;
        }

        $keep[] = $voucher->code;
        session()->put(self::KEY, $keep);
        session()->forget(self::LEGACY_KEY);

        return $replaced;
    }

    public static function remove(?string $code = null): void
    {
        if ($code === null) {
            self::clear();

            return;
        }

        session()->put(self::KEY, array_values(array_diff(self::codes(), [strtoupper(trim($code))])));
        session()->forget(self::LEGACY_KEY);
    }

    public static function clear(): void
    {
        session()->forget([self::KEY, self::LEGACY_KEY]);
    }
}
