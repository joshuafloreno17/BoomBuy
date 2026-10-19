<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * "Remind me" on an upcoming flash sale: a notification a few minutes
 * before the slot starts, linking to the product. Sent on page visits
 * (like the other sweeps) and by the scheduler.
 */
class FlashReminders
{
    /** How long before the start the reminder is sent. */
    public const MINUTES_BEFORE = 5;

    public static function set(int $userId, int $flashSaleId): bool
    {
        $sale = DB::table('flash_sales')->where('id', $flashSaleId)->first();

        if (!$sale || now()->gte(Carbon::parse($sale->starts_at))) {
            return false;
        }

        DB::table('flash_sale_reminders')->insertOrIgnore([
            'flash_sale_id' => $flashSaleId, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return true;
    }

    public static function has(int $userId, int $flashSaleId): bool
    {
        return DB::table('flash_sale_reminders')->where('flash_sale_id', $flashSaleId)->where('user_id', $userId)->exists();
    }

    /** @return int reminders sent now */
    public static function sweep(): int
    {
        $due = DB::table('flash_sale_reminders as r')
            ->join('flash_sales as f', 'f.id', '=', 'r.flash_sale_id')
            ->join('products as p', 'p.id', '=', 'f.product_id')
            ->whereNull('r.notified_at')
            ->where('f.starts_at', '<=', now()->addMinutes(self::MINUTES_BEFORE))
            ->where('f.ends_at', '>', now())
            ->limit(200)
            ->get(['r.id', 'r.user_id', 'f.product_id', 'f.flash_price', 'f.starts_at', 'p.name']);

        $sent = 0;

        foreach ($due as $reminder) {
            // Once per reminder, even if two pages sweep at the same moment.
            $claimed = DB::table('flash_sale_reminders')
                ->where('id', $reminder->id)
                ->whereNull('notified_at')
                ->update(['notified_at' => now(), 'updated_at' => now()]);

            if (!$claimed) {
                continue;
            }

            createNotification(
                (int) $reminder->user_id,
                'Flash sale starting soon',
                $reminder->name . ' goes on flash sale at ₱' . number_format((float) $reminder->flash_price, 2)
                    . ' at ' . Carbon::parse($reminder->starts_at)->format('g:i A') . '.',
                'flash_reminder',
                (int) $reminder->product_id
            );
            $sent++;
        }

        return $sent;
    }

    public static function productUrl(int $productId): string
    {
        $name = DB::table('products')->where('id', $productId)->value('name');

        return route('product.details', $name ? Str::slug($name) . '-' . $productId : $productId);
    }
}
