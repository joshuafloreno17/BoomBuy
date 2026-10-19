<?php

namespace App\Support;

use App\Models\Voucher;
use Illuminate\Support\Carbon;

/**
 * The voucher form's rules (Seller Modules S7, A26, A27), one message per field:
 *
 *   code         4–12 letters and digits, saved in upper case, unique on BoomBuy
 *   fixed        more than ₱0, and not more than the minimum spend (when one is set)
 *   percentage   a whole 1–99%, with an optional cap of more than ₱0
 *   usage        total at least 1; per buyer at least 1 and not more than the total
 *   time         the end after the start
 */
class VoucherRules
{
    /** @return array{0: array<string, mixed>, 1: array<string, string>} [clean values, errors] */
    public static function check(array $input, ?Voucher $existing = null): array
    {
        $errors = [];
        $clean = [];

        $code = strtoupper(trim((string) ($input['code'] ?? '')));

        if (!preg_match(Voucher::CODE_PATTERN, $code)) {
            $errors['code'] = 'The code must be 4 to 12 letters and numbers, with no spaces or symbols.';
        } elseif (Voucher::whereRaw('UPPER(code) = ?', [$code])->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) {
            $errors['code'] = 'The code ' . $code . ' is already used by another voucher.';
        }

        $clean['code'] = $code;
        $type = ($input['discount_type'] ?? '') === 'percentage' ? 'percentage' : (($input['discount_type'] ?? '') === 'fixed' ? 'fixed' : null);

        if (!$type) {
            $errors['discount_type'] = 'Choose a fixed amount or a percentage.';
        }

        $value = ProductRules::cleanNumber($input['discount_value'] ?? '');
        $min = ProductRules::cleanNumber($input['min_order_amount'] ?? '0') ?: '0';

        if (!is_numeric($min) || (float) $min < 0) {
            $errors['min_order_amount'] = 'The minimum spend must be ₱0 or more.';
        }

        if ($type === 'fixed') {
            if (!is_numeric($value) || (float) $value <= 0) {
                $errors['discount_value'] = 'The amount must be more than ₱0.';
            } elseif (is_numeric($min) && (float) $min > 0 && (float) $value > (float) $min) {
                $errors['discount_value'] = 'The discount can\'t be more than the minimum spend of ₱' . number_format((float) $min, 2) . '.';
            }
        } elseif ($type === 'percentage') {
            if (!preg_match('/^\d+$/', $value) || (int) $value < 1 || (int) $value > 99) {
                $errors['discount_value'] = 'The percentage must be a whole number from 1% to 99%.';
            }
        }

        $cap = ProductRules::cleanNumber($input['max_discount'] ?? '');

        if ($type === 'percentage' && $cap !== '' && (!is_numeric($cap) || (float) $cap <= 0)) {
            $errors['max_discount'] = 'The cap must be more than ₱0 (or leave it empty for no cap).';
        }

        // Blank = no limit; a number must be at least 1.
        $total = ProductRules::cleanNumber($input['max_uses'] ?? '');
        $perBuyer = ProductRules::cleanNumber($input['per_buyer_limit'] ?? '');

        if ($total !== '' && (!preg_match('/^\d+$/', $total) || (int) $total < 1)) {
            $errors['max_uses'] = 'The total usage limit must be at least 1.';
        }

        if ($perBuyer !== '' && (!preg_match('/^\d+$/', $perBuyer) || (int) $perBuyer < 1)) {
            $errors['per_buyer_limit'] = 'The limit per buyer must be at least 1.';
        } elseif ($perBuyer !== '' && preg_match('/^\d+$/', $total) && (int) $total >= 1 && (int) $perBuyer > (int) $total) {
            $errors['per_buyer_limit'] = 'The limit per buyer can\'t be more than the total usage limit.';
        }

        $start = self::time($input['starts_at'] ?? null) ?? now()->seconds(0);
        // Older forms send an expiry date (the voucher works all of that day).
        $end = self::time($input['ends_at'] ?? null)
            ?? (filled($input['expires_at'] ?? null) ? self::time($input['expires_at'])?->endOfDay()->seconds(0) : null);

        if ($end && $end->lte($start)) {
            $errors['ends_at'] = 'The end must be after the start.';
        }

        $clean += [
            'discount_type' => $type,
            'discount_value' => is_numeric($value) ? round((float) $value, 2) : null,
            'max_discount' => $type === 'percentage' && is_numeric($cap) && (float) $cap > 0 ? round((float) $cap, 2) : null,
            'min_order_amount' => is_numeric($min) ? round((float) $min, 2) : 0,
            'max_uses' => $total !== '' ? (int) $total : null,
            'per_buyer_limit' => $perBuyer !== '' ? (int) $perBuyer : null,
            'starts_at' => $start,
            'ends_at' => $end,
            'display' => ($input['display'] ?? 'claimable') === 'code_only' ? 'code_only' : 'claimable',
            'applies_to' => ($input['applies_to'] ?? 'all') === 'selected' ? 'selected' : 'all',
        ];

        return [$clean, $errors];
    }

    public static function time(mixed $value): ?Carbon
    {
        try {
            return filled($value) ? Carbon::parse((string) $value)->seconds(0) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
