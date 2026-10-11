<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Exceptions\ActionFailed;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Voucher;
use App\Services\PickupService;
use App\Services\ProductService;
use App\Services\ReturnRefundService;
use App\Services\SellerOrderService;
use App\Support\Categories;
use App\Support\CodPolicy;
use App\Support\OrderStatus;
use App\Support\OrderTimeline;
use App\Support\ProductPhotos;
use App\Support\SellerShop;
use App\Support\Waybill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * A seller's shop on the phone: today's numbers, orders (accept, prepare,
 * book the rider pickup, confirm the hand-over, cancel), products (add,
 * edit, stock and prices, archive), return requests and reviews. Same rules
 * as the seller web pages (SellerOrderService, PickupService, ProductService,
 * ReturnRefundService).
 */
class SellerController extends MobileController
{
    /** Tabs => the order statuses each one shows (as on the website). */
    private const TABS = [
        'to_process' => ['Pending', 'Confirmed'],
        'to_ship' => ['Preparing', 'Ready for Pickup', 'Pickup Assigned'],
        'shipped' => ['Picked Up', 'Dropped Off', 'At Sorting Center', 'In Transit', 'Sorted', 'Assigned for Delivery', 'Out for Delivery', 'Ready to Collect', 'Delivery Failed'],
        'completed' => ['Delivered', 'Completed'],
        'cancelled' => ['Cancelled', 'Returning', 'Return Ready', 'Returned to Seller'],
    ];

    public function dashboard(Request $request): JsonResponse
    {
        $sellerId = (int) $this->user($request)->id;

        $items = fn () => DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.seller_id', $sellerId);

        $statuses = $items()->select('orders.id', 'orders.status')->distinct()->get();

        $sales = (float) $items()->whereIn('orders.status', OrderStatus::DONE)->sum(DB::raw('order_items.price * order_items.quantity'));

        $today = $items()
            ->whereIn('orders.status', OrderStatus::DONE)
            ->whereRaw('DATE(COALESCE(orders.delivered_at, orders.updated_at)) = ?', [now()->toDateString()])
            ->selectRaw('COUNT(DISTINCT orders.id) as orders, COALESCE(SUM(order_items.price * order_items.quantity), 0) as revenue')
            ->first();

        $cod = $items()
            ->where('orders.payment_method', CodPolicy::METHOD)
            ->whereNotNull('orders.cod_collected_at')
            ->selectRaw('COALESCE(SUM(CASE WHEN orders.cod_remitted_at IS NULL THEN order_items.price * order_items.quantity ELSE 0 END), 0) as pending, COALESCE(SUM(CASE WHEN orders.cod_remitted_at IS NOT NULL THEN order_items.price * order_items.quantity ELSE 0 END), 0) as released')
            ->first();

        $rating = DB::table('product_reviews')
            ->join('products', 'products.id', '=', 'product_reviews.product_id')
            ->where('products.seller_id', $sellerId)
            ->selectRaw('AVG(product_reviews.rating) as average, COUNT(*) as total')
            ->first();

        $lowStock = Product::where('seller_id', $sellerId)->where('is_archived', false)->withSellableStock()->get()
            ->filter(fn ($p) => (int) $p->sellable_stock <= 5)->count();

        $trend = $items()
            ->whereIn('orders.status', OrderStatus::DONE)
            ->whereDate('orders.created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(orders.created_at) as day, SUM(order_items.price * order_items.quantity) as revenue')
            ->groupBy('day')
            ->pluck('revenue', 'day');

        return response()->json([
            'shop' => SellerShop::one($sellerId, false),
            'stats' => [
                'total_sales' => round(max(0, $sales - $this->voucherDiscounts($sellerId)), 2),
                'total_orders' => $statuses->count(),
                'new_orders' => $statuses->where('status', 'Pending')->count(),
                'to_prepare' => $statuses->whereIn('status', OrderStatus::SELLER_WORKING)->count(),
                'awaiting_pickup' => $statuses->whereIn('status', OrderStatus::AWAITING_PICKUP)->count(),
                'today_orders' => (int) ($today->orders ?? 0),
                'today_revenue' => round((float) ($today->revenue ?? 0), 2),
                'cod_pending' => round((float) ($cod->pending ?? 0), 2),
                'cod_released' => round((float) ($cod->released ?? 0), 2),
                'products' => Product::where('seller_id', $sellerId)->where('is_archived', false)->count(),
                'low_stock' => $lowStock,
                'rating' => $rating && $rating->total ? round((float) $rating->average, 1) : null,
                'reviews' => (int) ($rating->total ?? 0),
                'pending_returns' => DB::table('return_refund_requests')->where('seller_id', $sellerId)->where('status', 'pending')->count(),
            ],
            'sales_trend' => collect(range(6, 0))->map(function ($daysAgo) use ($trend) {
                $day = now()->subDays($daysAgo)->toDateString();

                return ['date' => $day, 'revenue' => round((float) ($trend[$day] ?? 0), 2)];
            })->values(),
        ]);
    }

    // ---- Orders --------------------------------------------------------

    /** ?tab=all|to_process|to_ship|shipped|completed|cancelled&q= */
    public function orders(Request $request): JsonResponse
    {
        $sellerId = (int) $this->user($request)->id;
        $tab = (string) $request->query('tab', 'all');
        $search = trim((string) $request->query('q', ''));

        $mine = fn () => DB::table('orders')->whereExists(fn ($q) => $q->select(DB::raw(1))
            ->from('order_items')->whereColumn('order_items.order_id', 'orders.id')->where('order_items.seller_id', $sellerId));

        $orders = $mine()
            ->when(isset(self::TABS[$tab]), fn ($q) => $q->whereIn('orders.status', self::TABS[$tab]))
            ->when($search !== '', function ($query) use ($search, $sellerId) {
                $number = ltrim($search, '#');
                $like = '%' . addcslashes($search, '%_\\') . '%';

                $query->where(function ($q) use ($number, $like, $sellerId) {
                    if (ctype_digit($number)) {
                        $q->orWhere('orders.id', (int) $number);
                    }

                    $q->orWhere('orders.shipping_name', 'like', $like)
                        ->orWhereExists(fn ($i) => $i->select(DB::raw(1))->from('order_items')
                            ->whereColumn('order_items.order_id', 'orders.id')
                            ->where('order_items.seller_id', $sellerId)
                            ->where('order_items.product_name', 'like', $like));
                });
            })
            ->orderByDesc('orders.created_at')
            ->orderByDesc('orders.id')
            ->limit(100)
            ->get();

        $counts = $mine()->select('orders.status', DB::raw('COUNT(*) as total'))->groupBy('orders.status')->pluck('total', 'status');
        $items = DB::table('order_items')->whereIn('order_id', $orders->pluck('id'))->where('seller_id', $sellerId)->get()->groupBy('order_id');

        return response()->json([
            'orders' => $orders->map(fn ($o) => $this->orderJson($o, $items->get($o->id, collect())))->values(),
            'counts' => collect(self::TABS)->map(fn ($s) => (int) collect($s)->sum(fn ($st) => $counts[$st] ?? 0))
                ->put('all', (int) $counts->sum()),
        ]);
    }

    public function order(Request $request, int $id): JsonResponse
    {
        $sellerId = (int) $this->user($request)->id;
        $order = DB::table('orders')->where('id', $id)->first();
        $items = $order ? DB::table('order_items')->where('order_id', $id)->where('seller_id', $sellerId)->get() : collect();

        if (!$order || $items->isEmpty()) {
            return $this->fail('Order not found.', 404);
        }

        return response()->json(['order' => $this->orderJson($order, $items, true)]);
    }

    /** status: Confirmed | Preparing | Cancelled (with "reason"). */
    public function updateStatus(Request $request, int $id, SellerOrderService $orders): JsonResponse
    {
        $sellerId = (int) $this->user($request)->id;

        return $this->act(
            fn () => $orders->updateStatus($sellerId, $id, (string) $request->input('status'), (string) $request->input('reason')),
            fn () => $this->freshOrder($id, $sellerId)
        );
    }

    /** pickup_date: one of pickup_days (Y-m-d). */
    public function requestPickup(Request $request, int $id, PickupService $pickups): JsonResponse
    {
        $sellerId = (int) $this->user($request)->id;

        return $this->act(fn () => $pickups->request($sellerId, $id, (string) $request->input('pickup_date')), fn () => $this->freshOrder($id, $sellerId));
    }

    public function confirmPickup(Request $request, int $id, SellerOrderService $orders): JsonResponse
    {
        $sellerId = (int) $this->user($request)->id;

        return $this->act(fn () => $orders->confirmRiderPickup($sellerId, $id), fn () => $this->freshOrder($id, $sellerId));
    }

    public function restockOrder(Request $request, int $id, SellerOrderService $orders): JsonResponse
    {
        $sellerId = (int) $this->user($request)->id;

        return $this->act(fn () => $orders->restock($sellerId, $id), fn () => $this->freshOrder($id, $sellerId));
    }

    // ---- Products ------------------------------------------------------

    /** ?filter=active (default) | archived &q= */
    public function products(Request $request): JsonResponse
    {
        $sellerId = (int) $this->user($request)->id;
        $archived = $request->query('filter') === 'archived';
        $search = trim((string) $request->query('q', ''));

        $products = Product::where('seller_id', $sellerId)
            ->where('is_archived', $archived)
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%'))
            ->withSellableStock()
            ->withSoldCount()
            ->withCount(['reviews', 'variations'])
            ->withAvg('reviews', 'rating')
            ->latest()
            ->get();

        return response()->json([
            'products' => $products->map(fn ($p) => $this->productCard($p) + [
                'is_archived' => (bool) $p->is_archived,
                'is_flagged' => (bool) $p->is_flagged,
                'flag_reason' => $p->flag_reason,
            ])->values(),
            'registered_category' => app(ProductService::class)->registeredCategory($sellerId),
            'categories' => self::categoryList(),
        ]);
    }

    public function product(Request $request, int $id): JsonResponse
    {
        $product = $this->ownProduct($request, $id);

        if (!$product) {
            return $this->fail('Product not found.', 404);
        }

        return response()->json(['product' => $this->productJson($product)]);
    }

    /**
     * multipart: name, category (slug), price, discount_percent, stock,
     * description, photos[] and, for options, variations[i][type|value|type2|value2|price_adjustment|stock].
     */
    public function storeProduct(Request $request, ProductService $products): JsonResponse
    {
        $sellerId = (int) $this->user($request)->id;

        try {
            $product = $products->create(
                $sellerId,
                $request->only('name', 'category', 'price', 'discount_percent', 'stock', 'description', 'specs'),
                null,
                array_values((array) $request->input('variations', [])),
                (array) $request->file('photos', []),
                'all:0'
            );
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage());
        }

        return response()->json(['message' => $product->name . ' has been added to your store!', 'product' => $this->productJson($product)]);
    }

    /** multipart: the product fields, photos[] to add, remove_photos[] (photo ids). */
    public function updateProduct(Request $request, int $id, ProductService $products): JsonResponse
    {
        $sellerId = (int) $this->user($request)->id;
        $product = $this->ownProduct($request, $id);

        if (!$product) {
            return $this->fail('Product not found.', 404);
        }

        try {
            $product = $products->update(
                $product,
                $sellerId,
                $request->only('name', 'category', 'price', 'discount_percent', 'stock', 'description', 'specs'),
                null,
                [
                    'add' => (array) $request->file('photos', []),
                    'remove' => (array) $request->input('remove_photos', []),
                ]
            );
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage());
        }

        return response()->json(['message' => 'Product updated successfully!', 'product' => $this->productJson($product->fresh())]);
    }

    /** variations: [{id, stock, price_adjustment}, ...] — this product's options only. */
    public function updateVariations(Request $request, int $id): JsonResponse
    {
        $product = $this->ownProduct($request, $id);

        if (!$product) {
            return $this->fail('Product not found.', 404);
        }

        $rows = collect((array) $request->input('variations', []))->keyBy(fn ($row) => (int) ($row['id'] ?? 0));
        $basePrice = (float) $product->price;

        foreach ($rows as $row) {
            if (!is_numeric($row['stock'] ?? null) || (int) $row['stock'] < 0) {
                return $this->fail('Stock can’t be below 0.');
            }

            if (!is_numeric($row['price_adjustment'] ?? null) || $basePrice + (float) $row['price_adjustment'] <= 0) {
                return $this->fail('An option can’t end up costing ₱0 or less.');
            }
        }

        $changed = 0;

        foreach (ProductVariation::where('product_id', $product->id)->whereIn('id', $rows->keys())->get() as $variation) {
            $row = $rows[$variation->id];
            $stock = (int) $row['stock'];
            $price = round((float) $row['price_adjustment'], 2);

            if ($stock === (int) $variation->stock && $price === round((float) $variation->price_adjustment, 2)) {
                continue;
            }

            $variation->stock = $stock;
            $variation->price_adjustment = $price;
            $variation->save();
            $changed++;
        }

        ProductPhotos::sync($product);

        return response()->json([
            'message' => $changed ? 'Saved changes to ' . $changed . ' option(s).' : 'Nothing changed.',
            'product' => $this->productJson($product->fresh()),
        ]);
    }

    public function archiveProduct(Request $request, int $id): JsonResponse
    {
        return $this->setArchived($request, $id, true);
    }

    public function unarchiveProduct(Request $request, int $id): JsonResponse
    {
        return $this->setArchived($request, $id, false);
    }

    // ---- Returns and reviews --------------------------------------------

    public function returns(Request $request): JsonResponse
    {
        $sellerId = (int) $this->user($request)->id;

        $requests = DB::table('return_refund_requests')
            ->join('orders', 'orders.id', '=', 'return_refund_requests.order_id')
            ->join('order_items', 'order_items.id', '=', 'return_refund_requests.order_item_id')
            ->where('return_refund_requests.seller_id', $sellerId)
            ->select('return_refund_requests.*', 'orders.shipping_name', 'order_items.product_name', 'order_items.variation_label', 'order_items.quantity')
            ->orderByRaw("CASE WHEN return_refund_requests.status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('return_refund_requests.created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'returns' => $requests->map(fn ($r) => [
                'id' => (int) $r->id,
                'order_id' => (int) $r->order_id,
                'waybill' => Waybill::number((int) $r->order_id),
                'buyer_name' => $r->shipping_name,
                'product_name' => $r->product_name,
                'option' => $r->variation_label,
                'quantity' => (int) $r->quantity,
                'type' => $r->request_type,
                'reason' => $r->reason,
                'message' => $r->message,
                'has_evidence' => !empty($r->evidence),
                'refund_amount' => round((float) $r->refund_amount, 2),
                'status' => $r->status,
                'status_label' => ReturnRefundService::LABELS[$r->status] ?? ucfirst(str_replace('_', ' ', $r->status)),
                'seller_note' => $r->seller_note,
                'can_decide' => $r->status === 'pending',
                'can_restock' => $r->request_type === 'Return' && (!empty($r->returned_at) || $r->status === 'returned') && empty($r->restocked_at),
                'created_at' => self::iso($r->created_at),
            ])->values(),
        ]);
    }

    public function approveReturn(Request $request, int $id, ReturnRefundService $returns): JsonResponse
    {
        return $this->act(fn () => $returns->approve((int) $this->user($request)->id, $id));
    }

    public function rejectReturn(Request $request, int $id, ReturnRefundService $returns): JsonResponse
    {
        return $this->act(fn () => $returns->reject((int) $this->user($request)->id, $id, (string) $request->input('note')));
    }

    public function restockReturn(Request $request, int $id, ReturnRefundService $returns): JsonResponse
    {
        return $this->act(fn () => $returns->restock((int) $this->user($request)->id, $id));
    }

    public function reviews(Request $request): JsonResponse
    {
        $sellerId = (int) $this->user($request)->id;

        $reviews = DB::table('product_reviews')
            ->join('users', 'users.id', '=', 'product_reviews.buyer_id')
            ->join('products', 'products.id', '=', 'product_reviews.product_id')
            ->where('products.seller_id', $sellerId)
            ->select('product_reviews.*', 'users.name as buyer_name', 'products.name as product_name', 'products.image as product_image')
            ->orderByDesc('product_reviews.created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'reviews' => $reviews->map(fn ($r) => [
                'id' => (int) $r->id,
                'product_name' => $r->product_name,
                'product_image' => productImageUrl($r->product_image),
                'buyer_name' => $r->buyer_name,
                'rating' => (int) $r->rating,
                'review' => $r->review,
                'seller_reply' => $r->seller_reply,
                'created_at' => self::iso($r->created_at),
            ])->values(),
        ]);
    }

    public function replyReview(Request $request, int $id): JsonResponse
    {
        $reply = trim((string) $request->input('reply'));

        if ($reply === '') {
            return $this->fail('Please write a reply before submitting.');
        }

        $owned = DB::table('product_reviews')
            ->join('products', 'products.id', '=', 'product_reviews.product_id')
            ->where('product_reviews.id', $id)
            ->where('products.seller_id', $this->user($request)->id)
            ->exists();

        if (!$owned) {
            return $this->fail('Review not found.', 404);
        }

        DB::table('product_reviews')->where('id', $id)->update([
            'seller_reply' => mb_substr($reply, 0, 1000),
            'seller_replied_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Reply posted.']);
    }

    // ------------------------------------------------------------------

    private function ownProduct(Request $request, int $id): ?Product
    {
        return Product::where('id', $id)->where('seller_id', $this->user($request)->id)->first();
    }

    private function setArchived(Request $request, int $id, bool $archived): JsonResponse
    {
        $product = $this->ownProduct($request, $id);

        if (!$product) {
            return $this->fail('Product not found.', 404);
        }

        $product->update(['is_archived' => $archived]);

        return response()->json([
            'message' => $product->name . ($archived ? ' has been archived and removed from the storefront.' : ' has been restored to the storefront.'),
        ]);
    }

    private function freshOrder(int $orderId, int $sellerId): array
    {
        $order = DB::table('orders')->where('id', $orderId)->first();

        return $order ? ['order' => $this->orderJson($order, DB::table('order_items')->where('order_id', $orderId)->where('seller_id', $sellerId)->get(), true)] : [];
    }

    private function orderJson(object $order, $items, bool $full = false): array
    {
        $unclaimedPickup = $order->status === 'Ready for Pickup' && empty($order->rider_id);

        $json = [
            'id' => (int) $order->id,
            'waybill' => Waybill::number((int) $order->id),
            'status' => $order->status,
            'buyer_name' => $order->shipping_name,
            'payment_method' => $order->payment_method,
            'seller_total' => round((float) $items->sum(fn ($i) => (float) $i->price * (int) $i->quantity), 2),
            'item_count' => (int) $items->sum('quantity'),
            'title' => ($first = $items->first()) ? $first->product_name . ($items->count() > 1 ? ' + ' . ($items->count() - 1) . ' more' : '') : 'Order #' . $order->id,
            'created_at' => self::iso($order->created_at),
            'pickup_date' => $order->pickup_date ?? null,
            // What the seller can do next (the same steps as the web page).
            'can' => [
                'accept' => $order->status === 'Pending',
                'prepare' => $order->status === 'Confirmed',
                'request_pickup' => $order->status === 'Preparing',
                'confirm_pickup' => !empty($order->rider_id) && in_array($order->status, ['Pickup Assigned', 'Picked Up'], true) && empty($order->seller_confirmed_pickup_at),
                'cancel' => in_array($order->status, ['Pending', 'Confirmed', 'Preparing'], true) || $unclaimedPickup,
                'restock' => $order->status === 'Returned to Seller' && empty($order->restocked_at),
            ],
        ];

        if (!$full) {
            return $json;
        }

        $products = Product::whereIn('id', $items->pluck('product_id'))->get(['id', 'image'])->keyBy('id');
        $rider = !empty($order->rider_id) ? DB::table('users')->where('id', $order->rider_id)->first(['name', 'phone']) : null;
        $history = CodPolicy::buyerHistory((int) $order->buyer_id);

        return $json + [
            'shipping_phone' => $order->shipping_phone,
            'shipping_address' => $order->shipping_address,
            'cancellation_reason' => $order->cancellation_reason,
            'pickup_rider' => $rider ? ['name' => $rider->name, 'phone' => $rider->phone] : null,
            'pickup_days' => collect(PickupService::dayChoices())->map(fn ($label, $date) => ['date' => $date, 'label' => $label])->values(),
            'buyer_history' => $history,
            'items' => $items->map(fn ($i) => [
                'name' => $i->product_name,
                'option' => $i->variation_label,
                'image' => productImageUrl(($i->variation_image ?? null) ?: ($products->get($i->product_id)->image ?? null)),
                'price' => round((float) $i->price, 2),
                'quantity' => (int) $i->quantity,
            ])->values(),
            'timeline' => collect(OrderTimeline::forOrders([(int) $order->id])->get($order->id, []))->map(fn ($s) => [
                'status' => $s->status,
                'title' => $s->title,
                'detail' => $s->detail,
                'at' => self::iso($s->at),
            ])->reverse()->values(),
        ];
    }

    private function productJson(Product $product): array
    {
        $variations = $product->variations()->orderBy('id')->get();
        $images = $product->images()->get();

        return [
            'id' => (int) $product->id,
            'name' => $product->name,
            'category' => Categories::slug($product->category),
            'category_label' => $product->category,
            'description' => $product->description,
            'regular_price' => $product->regularPrice(),
            'discount_percent' => (int) $product->discount_percent,
            'price' => (float) $product->price,
            'stock' => (int) $product->stock,
            'is_archived' => (bool) $product->is_archived,
            'is_flagged' => (bool) $product->is_flagged,
            'flag_reason' => $product->flag_reason,
            'image' => productImageUrl($product->image),
            'photos' => $images->whereNull('product_variation_id')->map(fn ($img) => ['id' => (int) $img->id, 'url' => productImageUrl($img->path)])->values(),
            'variations' => $variations->map(fn ($v) => [
                'id' => (int) $v->id,
                'label' => $v->shortLabel(),
                'price_adjustment' => round((float) $v->price_adjustment, 2),
                'price' => round((float) $product->price + (float) $v->price_adjustment, 2),
                'stock' => (int) $v->stock,
            ])->values(),
        ];
    }

    private function voucherDiscounts(int $sellerId): float
    {
        $codes = Voucher::where('seller_id', $sellerId)->pluck('code');

        return $codes->isEmpty() ? 0.0 : (float) DB::table('orders')
            ->whereIn('voucher_code', $codes)
            ->whereIn('status', OrderStatus::DONE)
            ->sum('discount_amount');
    }
}
