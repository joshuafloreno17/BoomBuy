<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Empties the shop for a fresh start: every product and every order goes,
 * with what hangs off them (options, reviews, returns, wishlists, saved
 * carts, order notifications and order-update chat messages). Accounts,
 * shops, vouchers, complaints and normal chats stay.
 *
 * Everything removed is written to storage/app/backups first.
 */
class FreshShop extends Command
{
    protected $signature = 'boombuy:fresh-shop {--force : Skip the confirmation question}';

    protected $description = 'Delete ALL products and orders so the shop starts empty (accounts are kept)';

    /** Notifications about orders, deliveries, returns or parcels. */
    private const ORDER_NOTIFICATION_TYPES = ['order', 'order_status', 'delivery', 'delivery_status', 'return_refund', 'parcel'];

    /** Tables emptied completely, in delete order (children first). */
    private const TABLES = ['return_refund_requests', 'product_reviews', 'order_items', 'orders', 'wishlists', 'product_variations', 'products', 'saved_carts'];

    public function handle(): int
    {
        $orderNotes = DB::table('notifications')->whereIn('type', self::ORDER_NOTIFICATION_TYPES);
        $orderMessages = Message::where('kind', Message::ORDER_UPDATE);

        $this->table(['What', 'Rows to delete'], [
            ['Products', DB::table('products')->count()],
            ['Product options (variations)', DB::table('product_variations')->count()],
            ['Orders', DB::table('orders')->count()],
            ['Order items', DB::table('order_items')->count()],
            ['Reviews', DB::table('product_reviews')->count()],
            ['Return / refund requests', DB::table('return_refund_requests')->count()],
            ['Wishlist entries', DB::table('wishlists')->count()],
            ['Saved carts', DB::table('saved_carts')->count()],
            ['Order notifications', (clone $orderNotes)->count()],
            ['Order-update chat messages', (clone $orderMessages)->count()],
        ]);
        $this->line('Kept: user accounts, shops, vouchers (usage reset to 0), complaints, normal chat messages.');

        if (!$this->option('force') && !$this->confirm('Delete everything listed above? This empties the shop.')) {
            $this->info('Nothing was changed.');

            return self::SUCCESS;
        }

        // Backup first — every row that is about to go.
        $backup = [];
        foreach (self::TABLES as $table) {
            $backup[$table] = DB::table($table)->get();
        }
        $backup['notifications'] = (clone $orderNotes)->get();
        $backup['messages'] = DB::table('messages')->where('kind', Message::ORDER_UPDATE)->get();
        $backup['vouchers_used_count'] = DB::table('vouchers')->pluck('used_count', 'id');

        $path = 'backups/fresh-shop-' . now()->format('Ymd-His') . '.json';
        Storage::disk('local')->put($path, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info('Backup saved: storage/app/' . $path);

        $images = DB::table('products')->whereNotNull('image')->pluck('image')
            ->merge(DB::table('product_variations')->whereNotNull('image')->pluck('image'))
            ->filter()->unique();

        DB::transaction(function () use ($orderNotes, $orderMessages) {
            (clone $orderNotes)->delete();
            (clone $orderMessages)->delete();
            DB::table('vouchers')->update(['used_count' => 0]);

            foreach (self::TABLES as $table) {
                DB::table($table)->delete();
            }
        });

        foreach ($images as $image) {
            Storage::disk('public')->delete($image);
        }

        // New products and orders start again from #1.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            foreach (self::TABLES as $table) {
                DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
            }
        }

        $this->info('Done. The shop is empty — ' . $images->count() . ' product photo(s) removed.');

        return self::SUCCESS;
    }
}
