<?php

namespace App\Providers;

use App\Models\Complaint;
use App\Models\Message;
use App\Models\Notification;
use App\Models\PlatformAnnouncement;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Support\Categories;
use App\Support\Inbox;
use App\Support\SupportAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Data the shared partials need (sidebar badges, navbar panels, footer,
 * announcement), prepared here instead of inside the Blade files.
 */
class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Seller, logistics and rider sidebars: their own unread badges.
        foreach (['seller', 'logistics', 'rider'] as $role) {
            View::composer("components.layout.{$role}-sidebar", function ($view) use ($role) {
                $userId = $view->getData()['user']['id'] ?? null;

                $view->with([
                    "{$role}UnreadNotifications" => Inbox::unreadNotifications($userId),
                    "{$role}UnreadMessages" => Inbox::unreadMessages($userId),
                ]);
            });
        }

        // The seller's card shows the shop name.
        View::composer('components.layout.seller-sidebar', function ($view) {
            $user = $view->getData()['user'] ?? [];

            $shopName = DB::table('seller_applications')
                ->where('user_id', $user['id'] ?? null)
                ->value('business_name');

            $view->with('sellerCardName', $shopName ?: ($user['name'] ?? 'Seller'));
        });

        View::composer('components.layout.admin-sidebar', function ($view) {
            $adminId = SupportAccount::id();

            $view->with([
                'adminUnreadNotifications' => Inbox::unreadNotifications($adminId),
                'adminUnreadMessages' => Inbox::unreadMessages($adminId),
                'pendingComplaintsCount' => Complaint::where('status', 'Pending')->count(),
            ]);
        });

        View::composer('partials.announcement-banner', function ($view) {
            $view->with(
                'latestAnnouncement',
                PlatformAnnouncement::where('is_active', true)->orderByDesc('created_at')->first()
            );
        });

        View::composer('partials.buyer-footer', function ($view) {
            $user = session('user');
            $isBuyer = $user && ($user['role'] ?? '') === 'buyer';

            $view->with([
                'bbFooterUser' => $user,
                'bbFooterBuyer' => $isBuyer,
                'bbSupportId' => $isBuyer ? SupportAccount::id() : null,
            ]);
        });

        View::composer('partials.buyer-navbar', fn ($view) => $view->with($this->navbarData($view->getData())));

        View::composer('partials.navbar-cart-preview', fn ($view) => $view->with($this->cartPreviewData()));
    }

    /** Badges plus the latest notifications and chats for the hover panels. */
    private function navbarData(array $data): array
    {
        $user = session()->get('user');

        $nav = [
            'bbNavUser' => $user,
            'bbCartCount' => array_sum(session()->get('cart', [])),
            'bbActive' => $data['activeNav'] ?? null,
            'bbNotificationCount' => 0,
            'bbMessageCount' => 0,
        ];

        if (!$user) {
            return $nav;
        }

        $meId = (int) $user['id'];

        // Newest message per chat partner, plus how many of theirs are unread.
        $recentChats = Message::where('sender_id', $meId)
            ->orWhere('recipient_id', $meId)
            ->latest()
            ->limit(200)
            ->get()
            ->groupBy(fn ($m) => (int) $m->sender_id === $meId ? (int) $m->recipient_id : (int) $m->sender_id)
            ->take(5)
            ->map(fn ($thread, $partnerId) => [
                'partner_id' => $partnerId,
                'last' => $thread->first(),
                'unread' => $thread->where('recipient_id', $meId)->whereNull('read_at')->count(),
            ]);

        return array_merge($nav, [
            'bbNotificationCount' => Inbox::unreadNotifications($meId),
            'bbMessageCount' => Inbox::unreadMessages($meId),
            'bbRecentNotifications' => Notification::where('user_id', $meId)->latest()->limit(6)->get(),
            'bbMeId' => $meId,
            'bbRecentChats' => $recentChats,
            'bbChatPartners' => User::whereIn('id', $recentChats->keys())->get(['id', 'name', 'profile_photo'])->keyBy('id'),
        ]);
    }

    /** The cart's newest five lines for the navbar cart panel. */
    private function cartPreviewData(): array
    {
        $cart = session('cart', []);
        $keys = array_reverse(array_keys($cart)); // newest first
        $shown = array_slice($keys, 0, 5);

        $products = Product::whereIn('id', array_map(fn ($key) => parseCartKey($key)[0], $keys))->get()->keyBy('id');

        $variations = ProductVariation::whereIn(
            'id',
            array_filter(array_map(fn ($key) => parseCartKey($key)[1], $shown))
        )->get()->keyBy('id');

        $lines = [];

        foreach ($shown as $key) {
            [$productId, $variationId] = parseCartKey($key);
            $product = $products->get($productId);

            if (!$product) {
                continue;
            }

            $variation = $variationId ? $variations->get($variationId) : null;

            $lines[] = [
                'name' => $product->name,
                'variation' => $variation ? $variation->variation_type . ': ' . $variation->variation_value : null,
                'quantity' => (int) $cart[$key],
                'price' => (float) $product->price + (float) ($variation->price_adjustment ?? 0),
                'image' => productImageUrl($product->image),
                'icon' => Categories::icon($product->category),
                'url' => route('product.details', Str::slug($product->name) . '-' . $product->id),
            ];
        }

        return [
            'previewCart' => $cart,
            'previewKeys' => $keys,
            'previewLines' => $lines,
            'previewMore' => max(0, count($keys) - count($lines)),
            'previewSubtotal' => cartSubtotalForSeller($cart),
        ];
    }
}
