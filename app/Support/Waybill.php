<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * The parcel's waybill number, printed on the shipping label and in its QR
 * code: "BB" + 10 random digits ("BB4829105736"), saved on the order
 * (orders.waybill_no) when it is placed. Random, so it doesn't give away how
 * many orders there are and can't be guessed from the next one. Sorting Center
 * staff can scan or type it; the old order-based form ("BB-000066") is still
 * read, for labels printed before.
 */
class Waybill
{
    /** Order id => waybill no., for this request. */
    private static function cache(): \ArrayObject
    {
        if (!app()->bound('waybill.cache')) {
            app()->instance('waybill.cache', new \ArrayObject());
        }

        return app('waybill.cache');
    }

    /** The order's waybill no.; an order made without one gets it now. */
    public static function number(int $orderId): string
    {
        $cache = self::cache();

        if (!isset($cache[$orderId])) {
            $cache[$orderId] = DB::table('orders')->where('id', $orderId)->value('waybill_no') ?: self::assign($orderId);
        }

        return $cache[$orderId];
    }

    /** Look up many at once (lists), so each row doesn't ask again. */
    public static function prime(iterable $orderIds): void
    {
        $cache = self::cache();
        $missing = collect($orderIds)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0 && !isset($cache[$id]))->unique()->values();

        if ($missing->isEmpty()) {
            return;
        }

        foreach (DB::table('orders')->whereIn('id', $missing)->whereNotNull('waybill_no')->pluck('waybill_no', 'id') as $id => $no) {
            $cache[(int) $id] = $no;
        }
    }

    /** "BB4829105736" → "BB 4829 1057 36": easier to read off a label. */
    public static function pretty(string $number): string
    {
        return preg_match('/^BB(\d{4})(\d{4})(\d{2})$/', $number, $m) ? "BB {$m[1]} {$m[2]} {$m[3]}" : $number;
    }

    /** A new random waybill no. that no order has yet. */
    public static function generate(): string
    {
        do {
            $number = 'BB' . random_int(1, 9) . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (DB::table('orders')->where('waybill_no', $number)->exists());

        return $number;
    }

    /** Give the order its waybill no. if it has none; returns it. */
    public static function assign(int $orderId): string
    {
        $existing = DB::table('orders')->where('id', $orderId)->value('waybill_no');

        if ($existing) {
            return $existing;
        }

        $number = self::generate();
        DB::table('orders')->where('id', $orderId)->whereNull('waybill_no')->update(['waybill_no' => $number]);

        // Another request may have set it first: theirs wins.
        return DB::table('orders')->where('id', $orderId)->value('waybill_no') ?: $number;
    }

    /**
     * The order id for a waybill no. ("BB4829105736", "bb 4829 1057 36",
     * "4829105736"), the old form ("BB-000066"), an order no. ("#66", "66"),
     * or the label's QR link (".../logistics/scan/BB4829105736"), which is
     * what a phone scanner reads; null when it isn't one.
     */
    public static function parse(?string $code): ?int
    {
        $code = trim((string) $code);

        if (preg_match('#/logistics/scan/([^/?\#\s]+)/?(?:[?\#].*)?$#i', $code, $m)) {
            $code = rawurldecode($m[1]);
        }

        $code = strtoupper(trim($code));
        $compact = preg_replace('/[\s-]+/', '', $code);

        // The random waybill no. (10 digits, never starting with 0).
        if (preg_match('/^(?:BB)?([1-9]\d{9})$/', $compact, $m)) {
            $id = DB::table('orders')->where('waybill_no', 'BB' . $m[1])->value('id');

            return $id ? (int) $id : null;
        }

        // The old order-based form, or the order no.
        if (preg_match('/^(?:BB-?|#)?0*(\d{1,9})$/', $code, $m)) {
            return (int) $m[1] ?: null;
        }

        return null;
    }
}
