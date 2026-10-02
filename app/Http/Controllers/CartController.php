<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Support\CheckoutPlan;
use App\Support\CodPolicy;
use App\Support\DeliveryFee;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function add($id)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            $this->returnHereAfterLogin();

            // The shop's AJAX "Add to cart" needs to know to send a guest to log in.
            return request()->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Please log in to add items to your cart.', 'login' => route('login')], 401)
                : $user;
        }

        /*
        |--------------------------------------------------------------------------
        | FIND PRODUCT FROM DATABASE
        |--------------------------------------------------------------------------
        */

        $product = Product::find($id);

        if (!$product) {
            return $this->addResult(false, 'Product not found.');
        }

        if (!$product->isPurchasable()) {
            return $this->addResult(false, 'This product is no longer available.');
        }

        /*
        |--------------------------------------------------------------------------
        | RESOLVE THE SELECTED VARIATION (IF ANY)
        |--------------------------------------------------------------------------
        |
        | A product with variations tracks its real sellable stock per
        | variation (see the "N in stock" shown per color/size on the product
        | page) — the base product's own stock column only matters when there
        | is no variation involved.
        |
        */

        [$variation, $variationError] = $product->resolveVariation((int) request('variation_id', 0));

        if ($variationError) {
            return $this->addResult(false, $variationError);
        }

        $variationId = $variation->id ?? 0;

        $effectiveStock = $variation ? (int) $variation->stock : (int) $product->stock;

        /*
        |--------------------------------------------------------------------------
        | CHECK STOCK
        |--------------------------------------------------------------------------
        */

        if ($effectiveStock <= 0) {
            return $this->addResult(false, 'This product is currently out of stock.');
        }

        /*
        |--------------------------------------------------------------------------
        | GET CURRENT CART
        |--------------------------------------------------------------------------
        |
        | Keyed "productId:variationId" so that different variations (e.g. two
        | different colors) of the same product land on separate cart lines
        | instead of merging into one.
        |
        */

        $cartKey = $product->id . ':' . $variationId;

        $cart = session()->get('cart', []);

        $currentQuantity = (int) ($cart[$cartKey] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | PREVENT EXCEEDING STOCK
        |--------------------------------------------------------------------------
        */

        if ($currentQuantity >= $effectiveStock) {
            return $this->addResult(false, 'You cannot add more than the available stock.');
        }

        /*
        |--------------------------------------------------------------------------
        | ADD PRODUCT TO CART
        |--------------------------------------------------------------------------
        */

        $requestedQty = max(1, (int) request('quantity', 1));

        // Never more than what's actually in stock.
        $cart[$cartKey] = min($currentQuantity + $requestedQty, $effectiveStock);

        /*
        |--------------------------------------------------------------------------
        | SAVE CART
        |--------------------------------------------------------------------------
        */

        session()->put('cart', $cart);

        return $this->addResult(true, 'Added to cart.', array_sum($cart));
    }

    /*
    | Answer for add(): JSON for the shop's AJAX "Add to cart" (so it never shows
    | "Added" for a request that failed), a redirect with a flash message otherwise.
    */
    private function addResult(bool $ok, string $message, int $cartCount = 0)
    {
        if (request()->expectsJson()) {
            return response()->json(
                ['ok' => $ok, 'message' => $message, 'cart_count' => $cartCount],
                $ok ? 200 : 422
            );
        }

        return back()->with($ok ? 'success' : 'error', $ok ? '✓ Added to cart!' : $message);
    }

    /*
    |--------------------------------------------------------------------------
    | BUY NOW
    |--------------------------------------------------------------------------
    |
    | FIX: this route was previously registered twice (identical duplicate)
    | under the same name 'buy.now'. Kept a single, combined version here:
    | validates quantity from the request like the original first version,
    | defaulting to 1 if not supplied — same behaviour either caller relied on.
    |
    */

    public function buyNow($id)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            $this->returnHereAfterLogin();

            return $user;
        }

        // Find the actual product from the database
        $product = Product::find($id);

        if (!$product) {
            return back()->with('error', 'Product not found.');
        }

        if (!$product->isPurchasable()) {
            return back()->with('error', 'This product is no longer available.');
        }

        $quantity = (int) request('quantity', 1);

        if ($quantity < 1) {
            $quantity = 1;
        }

        [$variation, $variationError] = $product->resolveVariation((int) request('variation_id', 0));

        if ($variationError) {
            return back()->with('error', $variationError);
        }

        $variationId = $variation->id ?? 0;

        $effectiveStock = $variation ? (int) $variation->stock : (int) $product->stock;

        // Check available stock
        if ($quantity > $effectiveStock) {
            return back()->with('error', 'Not enough stock available.');
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE BUY NOW PRODUCT
        |--------------------------------------------------------------------------
        */

        session()->put('buy_now', [
            $product->id . ':' . $variationId => $quantity,
        ]);

        return redirect()
            ->route('checkout', ['buy_now' => 1]);
    }

    public function update($key)
    {
        $cart =
            session()->get('cart', []);

        if (!isset($cart[$key])) {
            return back();
        }

        $action =
            request('action');

        [$productId, $variationId] = parseCartKey($key);

        $product = Product::find($productId);
        $variation = $variationId ? \App\Models\ProductVariation::find($variationId) : null;

        $blocked = false;

        if ($action === 'increase') {

            $effectiveStock = $variation ? (int) $variation->stock : (int) ($product->stock ?? 0);

            if ($cart[$key] >= $effectiveStock) {
                $blocked = true;
            } else {
                $cart[$key]++;
            }

        } elseif ($action === 'decrease') {

            $cart[$key]--;

            if ($cart[$key] <= 0) {

                unset($cart[$key]);
            }
        }

        session()->put(
            'cart',
            $cart
        );

        if (request()->wantsJson()) {

            $newQuantity = $cart[$key] ?? 0;

            $unitPrice = $product ? (float) $product->price : 0;

            if ($variation) {
                $unitPrice += (float) $variation->price_adjustment;
            }

            return response()->json(array_merge(
                [
                    'removed' => $newQuantity <= 0,
                    'quantity' => $newQuantity,
                    'item_total' => $product
                        ? number_format($unitPrice * $newQuantity, 2)
                        : '0.00',
                    'blocked' => $blocked,
                    'message' => $blocked ? 'No more stock available for this product.' : null,
                ],
                cartSummary($cart)
            ));
        }

        if ($blocked) {
            return back()->with('error', 'No more stock available for this product.');
        }

        return back();
    }

    // Remove from cart
    public function remove($key)
    {
        $cart =
            session()->get('cart', []);

        if (isset($cart[$key])) {

            unset($cart[$key]);
        }

        session()->put(
            'cart',
            $cart
        );

        if (request()->wantsJson()) {

            return response()->json(array_merge(
                ['removed' => true],
                cartSummary($cart)
            ));
        }

        return back()->with(
            'success',
            'Product removed from cart.'
        );
    }

    // Cart page
    /** The navbar cart hover panel, re-fetched on hover so it's never stale. */
    /**
     * A guest who tried to buy is sent to log in; bring them back to the
     * product they were looking at afterwards. Only same-site pages.
     */
    private function returnHereAfterLogin(): void
    {
        $previous = url()->previous();

        if (!session('user') && str_starts_with($previous, url('/')) && !str_contains($previous, '/login')) {
            session()->put('after_login', $previous);
        }
    }

    public function preview()
    {
        return view('partials.navbar-cart-preview');
    }

    public function index()
    {
        // Only logged-in buyers can add to a cart, so a guest's cart is
        // always empty — send them to log in, then straight back here.
        if (!session('user')) {
            session()->put('after_login', route('cart'));

            return redirect()
                ->route('login')
                ->with('success', 'Log in to see your cart.');
        }

        $cart =
            session()->get('cart', []);

        $appliedVoucher = null;
        $voucherError = null;
        $voucherSubtotal = 0;

        $voucherCode = session()->get('applied_voucher');

        if ($voucherCode) {

            $voucher = \App\Models\Voucher::where('code', $voucherCode)->first();

            if ($voucher) {

                // A seller-specific voucher can only ever discount that
                // seller's own lines — never the whole multi-seller cart.
                $voucherSubtotal = $voucher->seller_id
                    ? cartSubtotalForSeller($cart, $voucher->seller_id)
                    : (float) str_replace(',', '', cartSummary($cart)['subtotal']);
            }

            if ($voucher && $voucherSubtotal > 0 && $voucher->isValidFor($voucherSubtotal)) {
                $appliedVoucher = $voucher;
            } else {
                session()->forget('applied_voucher');
            }
        }

        return view(
            'pages.cart',
            compact('cart', 'appliedVoucher', 'voucherError', 'voucherSubtotal')
        );
    }

    public function applyVoucher()
    {
        $cart = session()->get('cart', []);
        $code = strtoupper(trim((string) request('voucher_code')));

        if ($code === '') {
            return back()->with('error', 'Please enter a voucher code.');
        }

        $voucher = \App\Models\Voucher::where('code', $code)->first();

        if (!$voucher) {
            return back()->with('error', 'Invalid voucher code.');
        }

        // A seller-specific voucher can only discount that seller's own items —
        // compute the subtotal from just their lines, not the whole cart.
        if ($voucher->seller_id) {

            $subtotal = cartSubtotalForSeller($cart, $voucher->seller_id);

            if ($subtotal <= 0) {
                return back()->with('error', 'This voucher only applies to a seller whose products are not in your cart.');
            }

        } else {

            $summary = cartSummary($cart);
            $subtotal = (float) str_replace(',', '', $summary['subtotal']);
        }

        if (!$voucher->isValidFor($subtotal)) {
            return back()->with('error', 'This voucher is expired, fully used, or your order does not meet its minimum amount.');
        }

        session()->put('applied_voucher', $voucher->code);

        return back()->with('success', 'Voucher "' . $voucher->code . '" applied!');
    }

    public function removeVoucher()
    {
        session()->forget('applied_voucher');

        return back()->with('success', 'Voucher removed.');
    }

    public function checkout()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK BUY NOW FIRST
        |--------------------------------------------------------------------------
        */

        // A Buy Now item is only checked out when this page was opened from
        // Buy Now itself. Arriving any other way (the cart's Checkout button)
        // means that earlier Buy Now was abandoned — drop it, otherwise it
        // would quietly replace the cart items the buyer chose.
        if (!request()->boolean('buy_now')) {
            session()->forget('buy_now');
        }

        $buyNow = session()->get('buy_now', []);

        if (!empty($buyNow)) {

            $cart = $buyNow;

        } else {

            $cart = session()->get('cart', []);

            if (empty($cart)) {

                return redirect()
                    ->route('cart')
                    ->with(
                        'error',
                        'Your cart is empty.'
                    );
            }

            /*
            |----------------------------------------------------------------------
            | ONLY CHECK OUT THE ITEMS THE BUYER ACTUALLY SELECTED
            |----------------------------------------------------------------------
            |
            | The cart page lets a buyer tick which lines to include. If a
            | selection was passed, narrow $cart down to just those keys and
            | remember the choice in the session so the place-order step (a
            | separate request) checks out the same subset and leaves the rest
            | of the cart untouched.
            |
            */

            $requestedItems = request('items');

            if ($requestedItems !== null) {

                $selectedKeys = array_filter(explode(',', $requestedItems));

                $cart = array_intersect_key(
                    $cart,
                    array_flip($selectedKeys)
                );

                if (empty($cart)) {

                    return redirect()
                        ->route('cart')
                        ->with(
                            'error',
                            'Please select at least one item to check out.'
                        );
                }

                session()->put('checkout_selection', array_keys($cart));

            } elseif (session()->has('checkout_selection')) {

                $cart = array_intersect_key(
                    $cart,
                    array_flip(session()->get('checkout_selection'))
                );

                if (empty($cart)) {
                    session()->forget('checkout_selection');
                    $cart = session()->get('cart', []);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | GET PRODUCTS USING DATABASE IDs
        |--------------------------------------------------------------------------
        */

        $productIds = array_map(
            fn ($key) => parseCartKey($key)[0],
            array_keys($cart)
        );

        $products = Product::whereIn('id', array_unique($productIds))
            ->get()
            ->keyBy('id');

        /*
        |--------------------------------------------------------------------------
        | CHECK CART PRODUCTS
        |--------------------------------------------------------------------------
        */

        foreach ($cart as $cartKey => $quantity) {

            [$productId] = parseCartKey($cartKey);

            if (!$products->has($productId)) {

                return redirect()
                    ->route('cart')
                    ->with(
                        'error',
                        'One of the products could not be found.'
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | APPLIED VOUCHER (carried over from the cart page)
        |--------------------------------------------------------------------------
        */

        $appliedVoucher = null;
        $voucherSubtotal = 0;
        $voucherCode = session()->get('applied_voucher');

        if ($voucherCode) {

            $voucher = \App\Models\Voucher::where('code', $voucherCode)->first();

            if ($voucher) {

                foreach ($cart as $cartKey => $quantity) {

                    [$productId, $variationId] = parseCartKey($cartKey);

                    if (!$products->has($productId)) {
                        continue;
                    }

                    $product = $products[$productId];

                    // A seller-specific voucher can only ever discount that
                    // seller's own lines — never the whole multi-seller cart.
                    if ($voucher->seller_id && (int) $product->seller_id !== (int) $voucher->seller_id) {
                        continue;
                    }

                    $unitPrice = (float) $product->price;

                    if ($variationId) {
                        $variation = \App\Models\ProductVariation::find($variationId);
                        if ($variation) {
                            $unitPrice += (float) $variation->price_adjustment;
                        }
                    }

                    $voucherSubtotal += $unitPrice * (int) $quantity;
                }
            }

            if ($voucher && $voucherSubtotal > 0 && $voucher->isValidFor($voucherSubtotal)) {
                $appliedVoucher = $voucher;
            } else {
                session()->forget('applied_voucher');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SEND TO CHECKOUT PAGE
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | PRE-FILL SAVED ADDRESS/PHONE FROM THE BUYER'S PROFILE
        |--------------------------------------------------------------------------
        */

        $savedBuyer = User::find($user['id']);
        $savedAddress = $savedBuyer->address ?? '';
        $savedPhone = $savedBuyer->phone ?? '';

        // The address book's default wins over the profile address.
        $addresses = \App\Models\BuyerAddress::forUser((int) $user['id']);
        $defaultAddress = $addresses->firstWhere('is_default', true);

        if ($defaultAddress) {
            $savedAddress = $defaultAddress->address;
            $savedPhone = $defaultAddress->phone;
        }

        $codStatus = CodPolicy::status((int) $user['id']);
        $freeShippingMin = DeliveryFee::FREE_SHIPPING_MIN;

        // Same split/fee/voucher math placeOrder will use.
        $planLines = [];

        foreach ($cart as $cartKey => $quantity) {

            [$productId, $variationId] = parseCartKey($cartKey);
            $product = $products[$productId];

            $unitPrice = (float) $product->price;

            if ($variationId && ($variation = \App\Models\ProductVariation::find($variationId))) {
                $unitPrice += (float) $variation->price_adjustment;
            }

            $planLines[] = ['seller_id' => $product->seller_id, 'price' => $unitPrice, 'quantity' => (int) $quantity];
        }

        $checkoutPlan = CheckoutPlan::build($planLines, $appliedVoucher);

        return view(
            'pages.checkout',
            compact(
                'user',
                'cart',
                'products',
                'appliedVoucher',
                'voucherSubtotal',
                'savedAddress',
                'savedPhone',
                'addresses',
                'codStatus',
                'freeShippingMin',
                'checkoutPlan'
            )
        );
    }

    public function placeOrder()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        /*
        |--------------------------------------------------------------------------
        | GET BUY NOW OR CART
        |--------------------------------------------------------------------------
        */

        $buyNow = session()->get('buy_now', []);

        if (!empty($buyNow)) {

            $cart = $buyNow;
            $isBuyNow = true;

        } else {

            $cart = session()->get('cart', []);
            $isBuyNow = false;

            if (empty($cart)) {

                return redirect()
                    ->route('cart')
                    ->with(
                        'error',
                        'Your cart is empty.'
                    );
            }

            // Only place an order for the lines the buyer actually selected on
            // the cart page — everything else stays in the cart untouched.
            if (session()->has('checkout_selection')) {

                $cart = array_intersect_key(
                    $cart,
                    array_flip(session()->get('checkout_selection'))
                );

                if (empty($cart)) {

                    return redirect()
                        ->route('cart')
                        ->with(
                            'error',
                            'Please select at least one item to check out.'
                        );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CHECKOUT INFORMATION
        |--------------------------------------------------------------------------
        */

        $address = trim(request('address'));
        $phone = trim(request('phone'));
        $payment = trim(request('payment'));

        if (
            empty($address) ||
            empty($phone) ||
            empty($payment)
        ) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Please complete all checkout information.'
                );
        }

        // Same rule as the address book — the rider has to be able to call it.
        if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {

            return back()
                ->withInput()
                ->with('error', 'Please enter a valid phone number (numbers only).');
        }

        if (mb_strlen($address) > 500) {

            return back()
                ->withInput()
                ->with('error', 'The delivery address is too long.');
        }

        if (!in_array($payment, CodPolicy::PAYMENT_METHODS, true)) {

            return back()
                ->withInput()
                ->with('error', 'Please choose a valid payment method.');
        }

        if (CodPolicy::isCod($payment)) {

            $codStatus = CodPolicy::status((int) $user['id']);

            if ($codStatus['blocked']) {

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Cash on Delivery is paused on your account until ' .
                        $codStatus['available_at']->format('M d, Y') .
                        ' because of repeated cancellations or refused parcels. Please choose another payment method.'
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BUILD ORDER ITEMS USING PRODUCT IDs
        |--------------------------------------------------------------------------
        */

        $orderItems = [];
        $total = 0;

        foreach ($cart as $cartKey => $quantity) {

            $quantity = (int) $quantity;

            if ($quantity <= 0) {
                continue;
            }

            [$productId, $variationId] = parseCartKey($cartKey);

            /*
            |--------------------------------------------------------------------------
            | FIND PRODUCT BY DATABASE ID
            |--------------------------------------------------------------------------
            */

            $product = Product::find($productId);

            if (!$product) {

                return redirect()
                    ->route('cart')
                    ->with(
                        'error',
                        'One of the products could not be found.'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | RESOLVE SELECTED VARIATION (IF ANY)
            |--------------------------------------------------------------------------
            */

            // The product may have been archived/flagged, or its variation
            // removed, since it went into the cart — this is the last gate.
            if (!$product->isPurchasable()) {

                return redirect()
                    ->route('cart')
                    ->with('error', $product->name . ' is no longer available. Please remove it from your cart.');
            }

            [$variation, $variationError] = $product->resolveVariation($variationId);

            if ($variationError || ($variationId && !$variation)) {

                return redirect()
                    ->route('cart')
                    ->with('error', $variationError ?? ('The option you picked for ' . $product->name . ' is no longer available. Please remove it and add it again.'));
            }

            $variationLabel = $variation
                ? $variation->variation_type . ': ' . $variation->variation_value
                : null;

            /*
            |--------------------------------------------------------------------------
            | CHECK STOCK
            |--------------------------------------------------------------------------
            |
            | A variation tracks its own sellable stock (see the "N in stock"
            | shown per color/size on the product page) — the base product's
            | stock column only applies when there is no variation.
            |
            */

            $effectiveStock = $variation ? (int) $variation->stock : (int) $product->stock;

            if ($effectiveStock < $quantity) {

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        $product->name .
                        ($variationLabel ? " ({$variationLabel})" : '') .
                        ' does not have enough stock.'
                    );
            }

            $unitPrice = (float) $product->price;

            if ($variation) {
                $unitPrice += (float) $variation->price_adjustment;
            }

            if ($unitPrice <= 0) {

                return redirect()
                    ->route('cart')
                    ->with('error', $product->name . ' has an invalid price right now. Please contact the seller.');
            }

            /*
            |--------------------------------------------------------------------------
            | CALCULATE SUBTOTAL
            |--------------------------------------------------------------------------
            */

            $subtotal =
                $unitPrice * $quantity;

            /*
            |--------------------------------------------------------------------------
            | ADD ORDER ITEM
            |--------------------------------------------------------------------------
            */

            $orderItems[] = [

                'product_id' =>
                    $product->id,

                'seller_id' =>
                    $product->seller_id,

                'product_name' =>
                    $product->name,

                // Transient — used below to decrement the right stock row,
                // not a real order_items column.
                'variation_id' =>
                    $variation->id ?? null,

                'variation_label' =>
                    $variationLabel,

                'price' =>
                    $unitPrice,

                'quantity' =>
                    $quantity,
            ];

            $total += $subtotal;
        }

        /*
        |--------------------------------------------------------------------------
        | NO VALID PRODUCTS
        |--------------------------------------------------------------------------
        */

        if (empty($orderItems)) {

            return redirect()
                ->route('cart')
                ->with(
                    'error',
                    'No valid products found.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | APPLY VOUCHER (if one was applied on the cart page)
        |--------------------------------------------------------------------------
        */

        $voucherCode = session()->get('applied_voucher');

        $voucher = $voucherCode
            ? \App\Models\Voucher::where('code', $voucherCode)->first()
            : null;

        // One order per seller; the voucher lands only on its own seller's
        // order, and each order carries its own delivery fee.
        $plan = CheckoutPlan::build($orderItems, $voucher);

        $voucherUsed = collect($plan['orders'])->contains(fn ($o) => $o['voucher_code'] !== null);

        /*
        |--------------------------------------------------------------------------
        | CREATE ORDER + ORDER ITEMS + REDUCE STOCK (ONE TRANSACTION)
        |--------------------------------------------------------------------------
        |
        | Everything below runs atomically. The stock check earlier in this
        | route is only a fast pre-check for a friendly error message — the
        | decrements here are the real guard: each is a conditional
        | "UPDATE ... WHERE stock >= quantity" that MySQL applies as a single
        | atomic row operation, so two concurrent checkouts racing for the
        | last unit can't both succeed. If any item lost the race, the whole
        | order (and its notifications) rolls back instead of overselling.
        |
        */

        $orderIds = [];

        try {

            $orderIds = DB::transaction(function () use (
                $user,
                $plan,
                $voucher,
                $voucherUsed,
                $phone,
                $address,
                $payment
            ) {

                if ($voucherUsed) {

                    // Conditional so two simultaneous checkouts can't push a
                    // voucher past its max_uses — the loser rolls back.
                    $claimedUse = \App\Models\Voucher::where('id', $voucher->id)
                        ->where(function ($q) {
                            $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
                        })
                        ->increment('used_count');

                    if (!$claimedUse) {
                        throw new \RuntimeException('VOUCHER_USED_UP');
                    }
                }

                $orderIds = [];

                foreach ($plan['orders'] as $planned) {

                    $orderId = DB::table('orders')->insertGetId([

                        'buyer_id' =>
                            $user['id'],

                        'total_amount' =>
                            $planned['total'],

                        'voucher_code' =>
                            $planned['voucher_code'],

                        'discount_amount' =>
                            $planned['discount'],

                        'delivery_fee' =>
                            $planned['delivery_fee'],

                        // Seller workflow starts here
                        'status' =>
                            'Pending',

                        'shipping_name' =>
                            $user['name'] ?? 'Buyer',

                        'shipping_phone' =>
                            $phone,

                        'shipping_address' =>
                            $address,

                        'payment_method' =>
                            $payment,

                        'created_at' =>
                            now(),

                        'updated_at' =>
                            now(),

                    ]);

                    $orderIds[] = $orderId;

                    createNotification(
                        (int) $user['id'],
                        'Order Confirmed',
                        'Your order #' . $orderId .
                        ' has been placed successfully and is now Pending.',
                        'order',
                        (int) $orderId
                    );

                    foreach ($planned['lines'] as $item) {

                        DB::table('order_items')->insert([

                            'order_id' =>
                                $orderId,

                            'product_id' =>
                                $item['product_id'],

                            'seller_id' =>
                                $item['seller_id'],

                            'product_name' =>
                                $item['product_name'],

                            'variation_label' =>
                                $item['variation_label'] ?? null,

                            'price' =>
                                $item['price'],

                            'quantity' =>
                                $item['quantity'],

                            'created_at' =>
                                now(),

                            'updated_at' =>
                                now(),

                        ]);

                        // A variation carries its own stock — reduce that instead
                        // of the base product's when one was selected. The WHERE
                        // stock >= quantity clause is what actually prevents the
                        // race: it can never take stock below zero, and it tells
                        // us via $decremented whether we won the race.
                        if (!empty($item['variation_id'])) {

                            $decremented = \App\Models\ProductVariation::where('id', $item['variation_id'])
                                ->where('stock', '>=', $item['quantity'])
                                ->decrement('stock', $item['quantity']);

                        } else {

                            $decremented = Product::where('id', $item['product_id'])
                                ->where('stock', '>=', $item['quantity'])
                                ->decrement('stock', $item['quantity']);
                        }

                        if (!$decremented) {
                            throw new \RuntimeException('OUT_OF_STOCK:' . $item['product_name']);
                        }
                    }

                    // One "new order" notification per seller, for their order only.
                    createNotification(
                        (int) $planned['seller_id'],
                        'New Order Received',
                        'You have a new order #' . $orderId . ' that is waiting for processing.',
                        'order',
                        (int) $orderId
                    );
                }

                return $orderIds;
            });

        } catch (\RuntimeException $e) {

            if (str_starts_with($e->getMessage(), 'OUT_OF_STOCK:')) {

                session()->forget('applied_voucher');

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        substr($e->getMessage(), strlen('OUT_OF_STOCK:')) .
                        ' just sold out while you were checking out. Please adjust your cart and try again.'
                    );
            }

            if ($e->getMessage() === 'VOUCHER_USED_UP') {

                session()->forget('applied_voucher');

                return back()
                    ->withInput()
                    ->with('error', 'That voucher just reached its usage limit. It has been removed — please review your total and place the order again.');
            }

            throw $e;
        }

        session()->forget('applied_voucher');

        /*
        |--------------------------------------------------------------------------
        | CLEAR SOURCE
        |--------------------------------------------------------------------------
        */

        if ($isBuyNow) {

            session()->forget('buy_now');

        } else {

            // Remove only the lines that were just ordered — anything the
            // buyer left unchecked on the cart page stays there.
            $remainingCart = session()->get('cart', []);

            foreach (array_keys($cart) as $orderedKey) {
                unset($remainingCart[$orderedKey]);
            }

            session()->put('cart', $remainingCart);
            session()->forget('checkout_selection');
        }

        /*
        |--------------------------------------------------------------------------
        | ORDER SUCCESS
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'orders.success',
                $orderIds[0]
            )
            ->with('checkout_order_ids', $orderIds)
            ->with(
                'success',
                count($orderIds) > 1
                    ? count($orderIds) . ' orders placed — one per seller, each shipped separately.'
                    : 'Order placed successfully!'
            );
    }

    public function orderSuccess($id)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        /*
        |--------------------------------------------------------------------------
        | GET ORDER FROM DATABASE
        |--------------------------------------------------------------------------
        */

        $order = DB::table('orders')
            ->where('id', $id)
            ->where('buyer_id', $user['id'])
            ->first();

        if (!$order) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | GET ORDER ITEMS
        |--------------------------------------------------------------------------
        */

        $items = DB::table('order_items')
            ->where('order_id', $order->id)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | CONVERT ORDER TO ARRAY
        |--------------------------------------------------------------------------
        */

        $order = (array) $order;

        /*
        |--------------------------------------------------------------------------
        | CONVERT ITEMS TO ARRAYS
        |--------------------------------------------------------------------------
        */

        $order['items'] = $items
            ->map(function ($item) {
                return (array) $item;
            })
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | COMPATIBILITY WITH EXISTING ORDER SUCCESS BLADE
        |--------------------------------------------------------------------------
        */

        $order['total'] =
            (float) $order['total_amount'];

        $order['date'] =
            $order['created_at'];

        $order['buyer_name'] =
            $order['shipping_name'];

        $order['address'] =
            $order['shipping_address'];

        $order['phone'] =
            $order['shipping_phone'];

        $order['payment'] =
            $order['payment_method'];

        // A multi-seller checkout creates one order per seller — show them all.
        $checkoutOrders = DB::table('orders')
            ->whereIn('id', (array) session('checkout_order_ids', [$order['id']]))
            ->where('buyer_id', $user['id'])
            ->orderBy('id')
            ->get(['id', 'total_amount', 'delivery_fee', 'discount_amount']);

        /*
        |--------------------------------------------------------------------------
        | DISPLAY ORDER SUCCESS
        |--------------------------------------------------------------------------
        */

        return view(
            'pages.order-success',
            compact('order', 'checkoutOrders')
        );
    }
}
