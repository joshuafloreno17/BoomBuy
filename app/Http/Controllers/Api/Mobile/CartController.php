<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Exceptions\CheckoutFailed;
use App\Models\BuyerAddress;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Voucher;
use App\Services\PlaceOrderService;
use App\Support\BuyerProfile;
use App\Support\CheckoutPlan;
use App\Support\CodPolicy;
use App\Support\DeliveryFee;
use App\Support\ParcelRoute;
use App\Support\PhLocations;
use App\Support\SellerShop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The buyer's cart and checkout. The cart is the same one the website keeps
 * for the account (saved_carts), keyed "productId:variationId" => quantity,
 * so a buyer sees one cart on the phone and on the computer.
 */
class CartController extends MobileController
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->cartJson((int) $this->user($request)->id));
    }

    /** product_id, variation_id (0/absent when it has no options), quantity. */
    public function add(Request $request): JsonResponse
    {
        $buyerId = (int) $this->user($request)->id;
        $product = Product::find((int) $request->input('product_id'));

        if (!$product) {
            return $this->fail('Product not found.', 404);
        }

        if (!$product->isPurchasable()) {
            return $this->fail('This product is no longer available.');
        }

        if ($product->sellerOnVacation()) {
            return $this->fail(Product::VACATION_MESSAGE);
        }

        [$variation, $error] = $product->resolveVariation((int) $request->input('variation_id', 0));

        if ($error) {
            return $this->fail($error);
        }

        $stock = $variation ? (int) $variation->stock : (int) $product->stock;

        if ($stock <= 0) {
            return $this->fail('This product is currently out of stock.');
        }

        $cart = $this->load($buyerId);
        $key = $product->id . ':' . ($variation->id ?? 0);
        $current = (int) ($cart[$key] ?? 0);

        if ($current >= $stock) {
            return $this->fail('You cannot add more than the available stock.');
        }

        $cart[$key] = min($current + max(1, (int) $request->input('quantity', 1)), $stock);
        $this->save($buyerId, $cart);

        return response()->json(['message' => 'Added to cart.', 'cart_count' => array_sum($cart)]);
    }

    /** key, quantity (0 removes the line). */
    public function update(Request $request): JsonResponse
    {
        $buyerId = (int) $this->user($request)->id;
        $key = (string) $request->input('key');
        $quantity = (int) $request->input('quantity');
        $cart = $this->load($buyerId);

        if (!isset($cart[$key])) {
            return $this->fail('That item is no longer in your cart.', 404);
        }

        if ($quantity <= 0) {
            unset($cart[$key]);
            $this->save($buyerId, $cart);

            return response()->json(['message' => 'Removed from cart.'] + $this->cartJson($buyerId));
        }

        [$productId, $variationId] = parseCartKey($key);
        $stock = $variationId
            ? (int) (ProductVariation::where('id', $variationId)->value('stock') ?? 0)
            : (int) (Product::where('id', $productId)->value('stock') ?? 0);

        $message = null;

        if ($quantity > $stock) {
            $quantity = max(1, $stock);
            $message = 'Only ' . $stock . ' left in stock.';
        }

        $cart[$key] = $quantity;
        $this->save($buyerId, $cart);

        return response()->json(['message' => $message] + $this->cartJson($buyerId));
    }

    public function remove(Request $request): JsonResponse
    {
        $buyerId = (int) $this->user($request)->id;
        $cart = $this->load($buyerId);
        unset($cart[(string) $request->input('key')]);
        $this->save($buyerId, $cart);

        return response()->json(['message' => 'Removed from cart.'] + $this->cartJson($buyerId));
    }

    /**
     * The checkout totals before placing: items (cart keys, or a Buy Now
     * line), voucher_code, address, fulfillment. Same split, fees and voucher
     * math the order will use.
     */
    public function checkoutPreview(Request $request): JsonResponse
    {
        $buyer = $this->user($request);
        $buyerId = (int) $buyer->id;
        $lines = $this->checkoutLines($request, $buyerId);

        if (is_string($lines)) {
            return $this->fail($lines);
        }

        [$planLines, $display] = $lines;

        $voucherError = null;
        $voucher = $this->voucher($request, $buyerId, $planLines, $voucherError);

        $addresses = BuyerAddress::forUser($buyerId);
        $default = $addresses->firstWhere('is_default', true);
        $address = trim((string) $request->input('address')) ?: ($default->address ?? (string) $buyer->address);
        $phone = $default->phone ?? (string) $buyer->phone;

        $location = PhLocations::locate($address);
        $pickupCenter = $location ? ParcelRoute::pickupPointFor($location['province'], $location['city']) : null;
        $fulfillment = $request->input('fulfillment') === 'pickup' && $pickupCenter ? 'pickup' : 'delivery';
        $plan = CheckoutPlan::build($planLines, $voucher, $location, $fulfillment);
        $shops = SellerShop::many(collect($planLines)->pluck('seller_id'));
        $cod = CodPolicy::status($buyerId);

        return response()->json([
            'items' => $display,
            'orders' => collect($plan['orders'])->map(fn ($o) => [
                'seller_id' => (int) $o['seller_id'],
                'shop_name' => $shops->get($o['seller_id'])['name'] ?? 'BoomBuy Seller',
                'subtotal' => $o['subtotal'],
                'discount' => $o['discount'],
                'delivery_fee' => $o['delivery_fee'],
                'total' => $o['total'],
                'eta' => $o['eta'],
            ])->values(),
            'subtotal' => $plan['subtotal'],
            'discount' => $plan['discount'],
            'delivery_fee' => $plan['delivery_fee'],
            'total' => $plan['total'],
            'voucher' => $voucher ? ['code' => $voucher->code] : null,
            'voucher_error' => $voucherError,
            'address' => $address,
            'phone' => $phone,
            'located' => $location ? trim(($location['city'] ? $location['city'] . ', ' : '') . str_replace(' (NCR)', '', $location['province'])) : null,
            'addresses' => $addresses->map(fn ($a) => AccountController::addressJson($a))->values(),
            'fulfillment' => $fulfillment,
            'pickup_center' => $pickupCenter ? ['name' => $pickupCenter->name, 'address' => $pickupCenter->address ?: $pickupCenter->town] : null,
            'payment_methods' => CodPolicy::PAYMENT_METHODS,
            'cod_blocked' => $cod['blocked'],
            'cod_available_at' => $cod['available_at']?->toIso8601String(),
            'missing_info' => BuyerProfile::missing($buyerId),
            'free_shipping_min' => DeliveryFee::freeShippingMin(),
        ]);
    }

    /** The same fields as the preview, plus phone and payment. */
    public function placeOrder(Request $request, PlaceOrderService $orders): JsonResponse
    {
        $buyerId = (int) $this->user($request)->id;

        if ($missing = BuyerProfile::missing($buyerId)) {
            return $this->fail('Complete your account before checking out — missing: ' . implode(', ', array_map('lcfirst', $missing)) . '.', 422, ['code' => 'missing_info']);
        }

        $isBuyNow = (int) $request->input('buy_now.product_id') > 0;
        $cart = $isBuyNow ? $this->buyNowCart($request) : $this->selectedCart($request, $buyerId);

        if (is_string($cart)) {
            return $this->fail($cart);
        }

        $voucherCode = strtoupper(trim((string) $request->input('voucher_code'))) ?: null;

        try {
            $orderIds = $orders->place(
                $this->sessionUser($request),
                $cart,
                $request->only('address', 'phone', 'payment', 'fulfillment'),
                $voucherCode
            );
        } catch (CheckoutFailed $e) {
            return $this->fail($e->getMessage(), 422, ['voucher_dropped' => $e->voucherDropped, 'back_to_cart' => $e->backToCart]);
        }

        // Only the lines just ordered leave the cart.
        if (!$isBuyNow) {
            $this->save($buyerId, array_diff_key($this->load($buyerId), $cart));
        }

        return response()->json([
            'message' => count($orderIds) > 1
                ? count($orderIds) . ' orders placed — one per seller, each shipped separately.'
                : 'Order placed successfully!',
            'order_ids' => $orderIds,
        ]);
    }

    public function vouchers(Request $request): JsonResponse
    {
        $buyerId = (int) $this->user($request)->id;
        $shopIds = collect(array_keys($this->load($buyerId)))
            ->map(fn ($key) => parseCartKey($key)[0])
            ->pipe(fn ($ids) => Product::whereIn('id', $ids)->pluck('seller_id'))
            ->unique();

        $vouchers = Voucher::query()
            ->where(fn ($q) => $q->whereNull('seller_id')->orWhereIn('seller_id', $shopIds))
            ->where('is_active', true)
            ->get()
            ->filter(fn ($v) => $v->isValidFor(PHP_FLOAT_MAX) && !$v->usedUpBy($buyerId))
            ->take(30);

        $shops = SellerShop::many($vouchers->pluck('seller_id')->filter());

        return response()->json([
            'vouchers' => $vouchers->map(fn ($v) => [
                'code' => $v->code,
                'discount_type' => $v->discount_type,
                'discount_value' => (float) $v->discount_value,
                'min_order_amount' => (float) $v->min_order_amount,
                'shop_name' => $v->seller_id ? ($shops->get($v->seller_id)['name'] ?? null) : 'BoomBuy',
                'expires_at' => $v->expires_at?->toDateString(),
            ])->values(),
        ]);
    }

    // ------------------------------------------------------------------

    /** @return array<string,int> */
    public static function load(int $buyerId): array
    {
        $saved = DB::table('saved_carts')->where('user_id', $buyerId)->value('items');
        $items = $saved ? json_decode($saved, true) : [];

        return is_array($items) ? array_filter($items, fn ($q) => (int) $q > 0) : [];
    }

    public static function save(int $buyerId, array $cart): void
    {
        ksort($cart);

        DB::table('saved_carts')->updateOrInsert(
            ['user_id' => $buyerId],
            ['items' => json_encode((object) $cart), 'updated_at' => now()]
        );
    }

    private function cartJson(int $buyerId): array
    {
        $cart = $this->load($buyerId);
        $products = Product::whereIn('id', collect(array_keys($cart))->map(fn ($k) => parseCartKey($k)[0]))->get()->keyBy('id');
        $variations = ProductVariation::whereIn('id', collect(array_keys($cart))->map(fn ($k) => parseCartKey($k)[1])->filter())->get()->keyBy('id');
        $shops = SellerShop::many($products->pluck('seller_id'));
        $lines = [];

        foreach ($cart as $key => $quantity) {
            [$productId, $variationId] = parseCartKey($key);
            $product = $products->get($productId);

            // A deleted product or option leaves the cart (as on the website).
            if (!$product || ($variationId && !$variations->has($variationId))) {
                continue;
            }

            $variation = $variationId ? $variations->get($variationId) : null;
            $stock = $variation ? (int) $variation->stock : (int) $product->stock;
            $price = $product->unitPrice($variation);
            $available = $product->isPurchasable();

            $lines[] = [
                'key' => (string) $key,
                'product_id' => (int) $product->id,
                'variation_id' => $variation ? (int) $variation->id : null,
                'name' => $product->name,
                'option' => $variation?->shortLabel(),
                'image' => productImageUrl(($variation->image ?? null) ?: $product->image),
                'price' => round($price, 2),
                'quantity' => (int) $quantity,
                'stock' => $stock,
                'subtotal' => round($price * (int) $quantity, 2),
                'seller_id' => (int) $product->seller_id,
                'shop_name' => $shops->get($product->seller_id)['name'] ?? 'BoomBuy Seller',
                'available' => $available && $stock > 0,
                'problem' => !$available ? 'No longer available' : ($stock <= 0 ? 'Out of stock' : ($stock < (int) $quantity ? 'Only ' . $stock . ' left' : null)),
            ];
        }

        return [
            'items' => $lines,
            'count' => (int) array_sum(array_column($lines, 'quantity')),
            'subtotal' => round(array_sum(array_column($lines, 'subtotal')), 2),
        ];
    }

    /** Selected cart lines, or why checkout can't start. */
    private function selectedCart(Request $request, int $buyerId): array|string
    {
        $cart = $this->load($buyerId);

        if (!$cart) {
            return 'Your cart is empty.';
        }

        $keys = array_map('strval', (array) $request->input('items', []));

        if ($keys) {
            $cart = array_intersect_key($cart, array_flip($keys));
        }

        return $cart ?: 'Please select at least one item to check out.';
    }

    private function buyNowCart(Request $request): array|string
    {
        $product = Product::find((int) $request->input('buy_now.product_id'));

        if (!$product || !$product->isPurchasable()) {
            return 'This product is no longer available.';
        }

        if ($product->sellerOnVacation()) {
            return Product::VACATION_MESSAGE;
        }

        [$variation, $error] = $product->resolveVariation((int) $request->input('buy_now.variation_id', 0));

        if ($error) {
            return $error;
        }

        return [$product->id . ':' . ($variation->id ?? 0) => max(1, (int) $request->input('buy_now.quantity', 1))];
    }

    /** [plan lines, lines for the screen], or why checkout can't start. */
    private function checkoutLines(Request $request, int $buyerId): array|string
    {
        $cart = (int) $request->input('buy_now.product_id') > 0 ? $this->buyNowCart($request) : $this->selectedCart($request, $buyerId);

        if (is_string($cart)) {
            return $cart;
        }

        $planLines = [];
        $display = [];

        foreach ($cart as $key => $quantity) {
            [$productId, $variationId] = parseCartKey($key);
            $product = Product::find($productId);

            if (!$product) {
                return 'One of the products could not be found.';
            }

            $variation = $variationId ? ProductVariation::find($variationId) : null;
            $price = $product->unitPrice($variation);

            $planLines[] = ['seller_id' => $product->seller_id, 'price' => $price, 'quantity' => (int) $quantity];
            $display[] = [
                'key' => (string) $key,
                'name' => $product->name,
                'option' => $variation?->shortLabel(),
                'image' => productImageUrl(($variation->image ?? null) ?: $product->image),
                'price' => round($price, 2),
                'quantity' => (int) $quantity,
                'seller_id' => (int) $product->seller_id,
            ];
        }

        return [$planLines, $display];
    }

    /** The voucher typed in, if it applies to these lines; otherwise why not. */
    private function voucher(Request $request, int $buyerId, array $lines, ?string &$error): ?Voucher
    {
        $code = strtoupper(trim((string) $request->input('voucher_code')));

        if ($code === '') {
            return null;
        }

        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            $error = 'Invalid voucher code.';

            return null;
        }

        // A seller's voucher only counts that seller's lines.
        $subtotal = collect($lines)
            ->filter(fn ($l) => !$voucher->seller_id || (int) $l['seller_id'] === (int) $voucher->seller_id)
            ->sum(fn ($l) => $l['price'] * $l['quantity']);

        if ($subtotal <= 0) {
            $error = 'This voucher only applies to a seller whose products are not in your order.';

            return null;
        }

        if (!$voucher->isValidFor($subtotal)) {
            $error = 'This voucher is expired, fully used, or your order does not meet its minimum amount.';

            return null;
        }

        if ($voucher->usedUpBy($buyerId)) {
            $error = 'You have already used voucher "' . $voucher->code . '".';

            return null;
        }

        return $voucher;
    }
}
