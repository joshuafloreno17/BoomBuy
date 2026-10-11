<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Services\BuyerOrderService;
use App\Services\ReturnRefundService;
use App\Support\AutoReceive;
use App\Support\CodPolicy;
use App\Support\OrderStatus;
use App\Support\OrderStock;
use App\Support\OrderTimeline;
use App\Support\ParcelRoute;
use App\Support\SellerShop;
use App\Support\Waybill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** A buyer's orders: track, cancel, confirm received, reorder, rate, return. */
class BuyerOrderController extends MobileController
{
    /** Status → step on the 5-step tracker, and a short note (as on the website). */
    private const PROGRESS = [
        'Pending' => [1, 'Waiting for the seller to confirm.'],
        'Confirmed' => [2, 'The seller accepted your order.'],
        'Preparing' => [2, 'Seller is packing your order.'],
        'Ready for Pickup' => [2, 'Packed — a BoomBuy rider will pick it up from the seller.'],
        'Pickup Assigned' => [2, 'A rider is on the way to collect it from the seller.'],
        'Picked Up' => [3, 'Picked up by the rider — heading to the Sorting Center.'],
        'Dropped Off' => [3, 'Seller dropped it off — heading into the Sorting Center.'],
        'In Transit' => [3, 'On its way to the Sorting Center near you.'],
        'At Sorting Center' => [3, 'At the Sorting Center.'],
        'Sorted' => [3, 'Sorted for your area — a rider will be assigned next.'],
        'Assigned for Delivery' => [3, 'Assigned to a rider for delivery.'],
        'Out for Delivery' => [4, 'Rider is on the way — arriving soon.'],
        'Ready to Collect' => [4, 'Ready at the Sorting Center — give the staff your pickup code to collect it.'],
        'Delivery Failed' => [4, 'Delivery attempt failed — it will be rescheduled.'],
    ];

    private const TABS = [
        'to_ship' => ['Pending', 'Confirmed', 'Preparing', 'Ready for Pickup', 'Pickup Assigned'],
        'to_receive' => ['Picked Up', 'Dropped Off', 'At Sorting Center', 'In Transit', 'Sorted', 'Assigned for Delivery', 'Out for Delivery', 'Ready to Collect', 'Delivery Failed'],
        'completed' => OrderStatus::DONE,
        'cancelled' => ['Cancelled'],
    ];

    public const RETURN_REASONS = ['Wrong item received', 'Damaged item', 'Defective product', 'Missing item', "Item doesn't match description", 'Other'];

    /** ?tab=all|to_ship|to_receive|completed|cancelled|returns */
    public function index(Request $request): JsonResponse
    {
        $buyerId = (int) $this->user($request)->id;
        $tab = (string) $request->query('tab', 'all');

        $orders = DB::table('orders')
            ->where('buyer_id', $buyerId)
            ->when(isset(self::TABS[$tab]), fn ($q) => $q->whereIn('status', self::TABS[$tab]))
            ->when($tab === 'returns', fn ($q) => $q->whereIn('id', DB::table('return_refund_requests')->where('buyer_id', $buyerId)->select('order_id')))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $counts = DB::table('orders')->where('buyer_id', $buyerId)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return response()->json([
            'orders' => $this->ordersJson($orders, $buyerId, false),
            'counts' => collect(self::TABS)->map(fn ($statuses) => (int) collect($statuses)->sum(fn ($s) => $counts[$s] ?? 0)),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $buyerId = (int) $this->user($request)->id;
        $order = DB::table('orders')->where('id', $id)->where('buyer_id', $buyerId)->first();

        if (!$order) {
            return $this->fail('Order not found.', 404);
        }

        return response()->json([
            'order' => $this->ordersJson(collect([$order]), $buyerId, true)[0],
            'cancel_reasons' => CodPolicy::CANCEL_REASONS,
            'return_reasons' => self::RETURN_REASONS,
            'refund_methods' => ReturnRefundService::REFUND_METHODS,
            'last_refund_to' => DB::table('return_refund_requests')
                ->where('buyer_id', $buyerId)
                ->whereNotNull('refund_method')
                ->orderByDesc('id')
                ->first(['refund_method', 'refund_account_name', 'refund_account_number']),
        ]);
    }

    public function cancel(Request $request, int $id, BuyerOrderService $orders): JsonResponse
    {
        $buyerId = (int) $this->user($request)->id;

        return $this->act(
            fn () => $orders->cancel($buyerId, $id, (string) $request->input('reason'), (string) $request->input('details')),
            fn () => $this->fresh($id, $buyerId)
        );
    }

    public function received(Request $request, int $id, BuyerOrderService $orders): JsonResponse
    {
        $buyerId = (int) $this->user($request)->id;

        return $this->act(fn () => $orders->markReceived($buyerId, $id), fn () => $this->fresh($id, $buyerId));
    }

    /** Puts what can still be bought back in the cart (as the website's Reorder). */
    public function reorder(Request $request, int $id): JsonResponse
    {
        $buyerId = (int) $this->user($request)->id;

        if (!DB::table('orders')->where('id', $id)->where('buyer_id', $buyerId)->exists()) {
            return $this->fail('Order not found.', 404);
        }

        $cart = CartController::load($buyerId);
        $added = 0;
        $skipped = 0;

        foreach (DB::table('order_items')->where('order_id', $id)->get() as $item) {
            $product = Product::find($item->product_id);

            if (!$product || !$product->isPurchasable() || (empty($item->variation_label) && $product->variations()->exists())) {
                $skipped++;
                continue;
            }

            $variationId = 0;
            $stock = (int) $product->stock;

            if (!empty($item->variation_label)) {
                $variation = (!empty($item->variation_id)
                    ? ProductVariation::where('id', $item->variation_id)->where('product_id', $product->id)->first()
                    : null) ?? OrderStock::findByLabel((int) $product->id, (string) $item->variation_label);

                if (!$variation) {
                    $skipped++;
                    continue;
                }

                $variationId = $variation->id;
                $stock = (int) $variation->stock;
            }

            if ($stock <= 0) {
                $skipped++;
                continue;
            }

            $key = $product->id . ':' . $variationId;
            $cart[$key] = min(($cart[$key] ?? 0) + (int) $item->quantity, $stock);
            $added++;
        }

        if ($added === 0) {
            return $this->fail('None of the items from this order are available to reorder right now.');
        }

        CartController::save($buyerId, $cart);

        return response()->json([
            'message' => $added . ' item(s) added to your cart.' . ($skipped ? ' ' . $skipped . ' item(s) could not be added (out of stock or no longer available).' : ''),
        ]);
    }

    /** product_id, rating 1–5, review. */
    public function review(Request $request, int $id): JsonResponse
    {
        $buyerId = (int) $this->user($request)->id;
        $productId = (int) $request->input('product_id');
        $order = DB::table('orders')->where('id', $id)->where('buyer_id', $buyerId)->first();

        if (!$order) {
            return $this->fail('Order not found.', 404);
        }

        if (!in_array($order->status, OrderStatus::DONE, true) || empty($order->buyer_received_at)) {
            return $this->fail('You can only review products after receiving the order.');
        }

        if (!DB::table('order_items')->where('order_id', $id)->where('product_id', $productId)->exists()) {
            return $this->fail('Product not found in this order.');
        }

        $rating = (int) $request->input('rating');
        $review = trim((string) $request->input('review'));

        if ($rating < 1 || $rating > 5) {
            return $this->fail('Please select a rating from 1 to 5 stars.');
        }

        $existing = DB::table('product_reviews')->where('buyer_id', $buyerId)->where('order_id', $id)->where('product_id', $productId)->first();

        $values = ['rating' => $rating, 'review' => $review !== '' ? mb_substr($review, 0, 2000) : null, 'updated_at' => now()];

        if ($existing) {
            DB::table('product_reviews')->where('id', $existing->id)->update($values);
        } else {
            DB::table('product_reviews')->insert($values + ['buyer_id' => $buyerId, 'order_id' => $id, 'product_id' => $productId, 'created_at' => now()]);
        }

        return response()->json(['message' => $existing ? 'Your review has been updated!' : 'Thank you! Your product review has been submitted.'] + $this->fresh($id, $buyerId));
    }

    /** multipart: order_item_id, request_type (Return|Refund), reason, message, evidence, refund_method, refund_account_name, refund_account_number. */
    public function requestReturn(Request $request, int $id, BuyerOrderService $orders): JsonResponse
    {
        $buyerId = (int) $this->user($request)->id;

        return $this->act(fn () => $orders->requestReturn(
            $buyerId,
            $id,
            (int) $request->input('order_item_id'),
            (string) $request->input('request_type'),
            (string) $request->input('reason'),
            (string) $request->input('message'),
            $request->file('evidence'),
            [
                'method' => $request->input('refund_method'),
                'account_name' => $request->input('refund_account_name'),
                'account_number' => $request->input('refund_account_number'),
            ]
        ), fn () => $this->fresh($id, $buyerId));
    }

    public function cancelReturn(Request $request, int $id, ReturnRefundService $returns): JsonResponse
    {
        return $this->act(fn () => $returns->cancelByBuyer((int) $this->user($request)->id, $id));
    }

    public function escalateReturn(Request $request, int $id, ReturnRefundService $returns): JsonResponse
    {
        return $this->act(fn () => $returns->escalate((int) $this->user($request)->id, $id, (string) $request->input('why')));
    }

    // ------------------------------------------------------------------

    private function fresh(int $orderId, int $buyerId): array
    {
        $order = DB::table('orders')->where('id', $orderId)->where('buyer_id', $buyerId)->first();

        return $order ? ['order' => $this->ordersJson(collect([$order]), $buyerId, true)[0]] : [];
    }

    private function ordersJson($orders, int $buyerId, bool $full): array
    {
        $ids = $orders->pluck('id')->map(fn ($id) => (int) $id)->all();
        $items = DB::table('order_items')->whereIn('order_id', $ids)->orderBy('id')->get()->groupBy('order_id');
        $products = Product::whereIn('id', $items->flatten()->pluck('product_id')->filter()->unique())->get(['id', 'image'])->keyBy('id');
        $shops = SellerShop::many($items->flatten()->pluck('seller_id'));
        $reviews = DB::table('product_reviews')->where('buyer_id', $buyerId)->whereIn('order_id', $ids)->get()->keyBy(fn ($r) => $r->order_id . ':' . $r->product_id);
        $returns = DB::table('return_refund_requests')->where('buyer_id', $buyerId)->whereIn('order_id', $ids)->orderByDesc('id')->get()->groupBy('order_id');
        $timelines = $full ? OrderTimeline::forOrders($ids) : collect();
        $riders = DB::table('users')
            ->whereIn('id', $orders->map(fn ($o) => $o->delivery_rider_id ?: $o->rider_id)->filter()->unique())
            ->get(['id', 'name', 'phone', 'profile_photo'])
            ->keyBy('id');

        return $orders->map(function ($order) use ($items, $products, $shops, $reviews, $returns, $timelines, $riders, $full) {
            $lines = $items->get($order->id, collect());
            $first = $lines->first();
            $done = in_array($order->status, OrderStatus::DONE, true);
            [$step, $note] = $done
                ? [5, $order->status === 'Completed' ? 'Received. Enjoy your order!' : 'Delivered — please confirm you received it.']
                : (empty($order->buyer_refused_at) ? (self::PROGRESS[$order->status] ?? [0, null]) : [0, 'You refused this parcel — it is going back to the seller.']);

            $returnUntil = !empty($order->buyer_received_at) ? Carbon::parse($order->buyer_received_at)->addDays(AutoReceive::RETURN_WINDOW_DAYS) : null;
            $openReturnItems = $returns->get($order->id, collect())->whereIn('status', ReturnRefundService::OPEN)->pluck('order_item_id')->map(fn ($i) => (int) $i)->all();

            $json = [
                'id' => (int) $order->id,
                'waybill' => Waybill::number((int) $order->id),
                'status' => $order->status,
                'step' => $step,
                'note' => $note,
                'total' => round((float) $order->total_amount, 2),
                'item_count' => (int) $lines->sum('quantity'),
                'title' => $first ? $first->product_name . ($lines->count() > 1 ? ' + ' . ($lines->count() - 1) . ' more' : '') : 'Order #' . $order->id,
                'image' => productImageUrl(($first->variation_image ?? null) ?: ($products->get($first->product_id ?? 0)->image ?? null)),
                'shop_name' => $first ? ($shops->get((int) $first->seller_id)['name'] ?? null) : null,
                'payment_method' => $order->payment_method,
                'created_at' => self::iso($order->created_at),
                'eta' => $step > 0 && $step < 5 ? ParcelRoute::etaLabel($order->delivery_zone ?? null, $order->created_at) : null,
                'can' => [
                    'cancel' => CodPolicy::buyerCanCancel($order),
                    'receive' => $order->status === 'Delivered' && empty($order->buyer_received_at),
                    'return' => $done && $returnUntil && $returnUntil->isFuture(),
                    'review' => $done && !empty($order->buyer_received_at),
                ],
            ];

            if (!$full) {
                return $json;
            }

            $riderId = $order->delivery_rider_id ?: $order->rider_id;
            $rider = $riderId ? $riders->get($riderId) : null;

            return $json + [
                'subtotal' => round((float) $lines->sum(fn ($i) => (float) $i->price * (int) $i->quantity), 2),
                'discount' => round((float) $order->discount_amount, 2),
                'voucher_code' => $order->voucher_code,
                'delivery_fee' => round((float) $order->delivery_fee, 2),
                'fulfillment' => $order->fulfillment ?? 'delivery',
                'pickup_code' => ($order->fulfillment ?? '') === 'pickup' ? $order->pickup_code : null,
                'pickup_center' => ($order->fulfillment ?? '') === 'pickup' && $order->destination_center_id
                    ? optional(\App\Models\SortingCenter::find($order->destination_center_id), fn ($c) => ['name' => $c->name, 'address' => $c->address ?: $c->town])
                    : null,
                'shipping_name' => $order->shipping_name,
                'shipping_phone' => $order->shipping_phone,
                'shipping_address' => $order->shipping_address,
                'cancellation_reason' => $order->cancellation_reason,
                'return_until' => $returnUntil?->toIso8601String(),
                'rider' => $rider ? ['name' => $rider->name, 'phone' => $rider->phone, 'photo' => self::photoUrl($rider->profile_photo)] : null,
                'has_delivery_proof' => !empty($order->delivery_proof ?? null),
                'items' => $lines->map(fn ($i) => [
                    'id' => (int) $i->id,
                    'product_id' => (int) $i->product_id,
                    'name' => $i->product_name,
                    'option' => $i->variation_label,
                    'image' => productImageUrl(($i->variation_image ?? null) ?: ($products->get($i->product_id)->image ?? null)),
                    'price' => round((float) $i->price, 2),
                    'quantity' => (int) $i->quantity,
                    'shop_name' => $shops->get((int) $i->seller_id)['name'] ?? null,
                    'seller_id' => (int) $i->seller_id,
                    'review' => optional($reviews->get($order->id . ':' . $i->product_id), fn ($r) => ['rating' => (int) $r->rating, 'review' => $r->review]),
                    'return_open' => in_array((int) $i->id, $openReturnItems, true),
                ])->values(),
                'timeline' => collect($timelines->get($order->id, []))->map(fn ($s) => [
                    'status' => $s->status,
                    'title' => $s->title,
                    'detail' => $s->detail,
                    'at' => self::iso($s->at),
                ])->reverse()->values(),
                'returns' => $returns->get($order->id, collect())->map(fn ($r) => [
                    'id' => (int) $r->id,
                    'order_item_id' => (int) $r->order_item_id,
                    'type' => $r->request_type,
                    'reason' => $r->reason,
                    'status' => $r->status,
                    'status_label' => ReturnRefundService::LABELS[$r->status] ?? ucfirst(str_replace('_', ' ', $r->status)),
                    'refund_amount' => round((float) $r->refund_amount, 2),
                    'seller_note' => $r->seller_note,
                    'can_cancel' => in_array($r->status, ['pending', 'disputed', 'approved'], true),
                    'can_escalate' => $r->status === 'rejected' && empty($r->escalated_at)
                        && Carbon::parse($r->updated_at)->addDays(ReturnRefundService::DISPUTE_DAYS)->isFuture(),
                    'created_at' => self::iso($r->created_at),
                ])->values(),
            ];
        })->values()->all();
    }
}
