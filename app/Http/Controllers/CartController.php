<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Support\CartContents;
use App\Support\CheckoutPlan;
use App\Support\CodPolicy;
use App\Support\DeliveryFee;
use App\Support\ParcelRoute;
use App\Support\PhLocations;
use Illuminate\Support\Facades\DB;
use App\Services\PlaceOrderService;
use App\Exceptions\CheckoutFailed;

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

        if ($product->sellerOnVacation()) {
            return $this->addResult(false, Product::VACATION_MESSAGE);
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

        // A whole number from 1 up to what is in stock (A17); never silently changed.
        $rawQty = trim((string) request('quantity', '1'));

        if (!ctype_digit($rawQty) || (int) $rawQty < 1) {
            return $this->addResult(false, 'Enter a quantity from 1 to ' . $effectiveStock . '.');
        }

        $requestedQty = (int) $rawQty;

        if ($currentQuantity + $requestedQty > $effectiveStock) {
            return $this->addResult(false, 'Only ' . $effectiveStock . ' available' . ($currentQuantity ? ' (you have ' . $currentQuantity . ' in your cart)' : '') . '.');
        }

        // The seller's most-per-buyer (orders placed before count too).
        if ($limitError = $product->purchaseLimitError((int) $user['id'], $currentQuantity + $requestedQty)) {
            return $this->addResult(false, $limitError);
        }

        $cart[$cartKey] = $currentQuantity + $requestedQty;

        /*
        |--------------------------------------------------------------------------
        | SAVE CART
        |--------------------------------------------------------------------------
        */

        session()->put('cart', $cart);

        return $this->addResult(true, 'Added to cart.', CartContents::count());
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

        if ($product->sellerOnVacation()) {
            return back()->with('error', Product::VACATION_MESSAGE);
        }

        $rawQty = trim((string) request('quantity', '1'));

        if (!ctype_digit($rawQty) || (int) $rawQty < 1) {
            return back()->with('error', 'Enter a whole quantity of at least 1.');
        }

        $quantity = (int) $rawQty;

        if ($limitError = $product->purchaseLimitError((int) $user['id'], $quantity)) {
            return back()->with('error', $limitError);
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
        $blockMessage = 'No more stock available for this product.';
        $effectiveStock = $variation ? (int) $variation->stock : (int) ($product->stock ?? 0);
        $buyerId = (int) (session('user')['id'] ?? 0);

        if ($action === 'set') {
            // A typed quantity: a whole number from 1 to the stock (A17).
            $rawQty = trim((string) request('quantity', ''));

            if (!ctype_digit($rawQty) || (int) $rawQty < 1) {
                $blocked = true;
                $blockMessage = 'Enter a quantity of at least 1, or remove the item.';
            } elseif ((int) $rawQty > $effectiveStock) {
                $blocked = true;
                $blockMessage = 'Only ' . $effectiveStock . ' available.';
            } elseif ($product && ($limitError = $product->purchaseLimitError($buyerId, (int) $rawQty))) {
                $blocked = true;
                $blockMessage = $limitError;
            } else {
                $cart[$key] = (int) $rawQty;
            }
        } elseif ($action === 'increase') {

            if ($cart[$key] >= $effectiveStock) {
                $blocked = true;
                $blockMessage = 'Only ' . $effectiveStock . ' available.';
            } elseif ($product && ($limitError = $product->purchaseLimitError($buyerId, $cart[$key] + 1))) {
                $blocked = true;
                $blockMessage = $limitError;
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

            $unitPrice = $product ? $product->unitPrice($variation ?: null) : 0;

            return response()->json(array_merge(
                [
                    'removed' => $newQuantity <= 0,
                    'quantity' => $newQuantity,
                    'item_total' => $product
                        ? number_format($unitPrice * $newQuantity, 2)
                        : '0.00',
                    'blocked' => $blocked,
                    'message' => $blocked ? $blockMessage : null,
                ],
                cartSummary($cart)
            ));
        }

        if ($blocked) {
            return back()->with('error', $blockMessage);
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
        // Same host exactly — "boombuy.store.evil.com" also starts with "https://boombuy.store".
        $sameSite = parse_url($previous, PHP_URL_HOST) === request()->getHost();

        if (!session('user') && $sameSite && !str_contains($previous, '/login')) {
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
            CartContents::prune();

        // The vouchers applied, worked out the way checkout will (the items still for sale).
        [$cartPlan, $appliedVouchers, $voucherNotices] = $this->voucherPlan($this->buyableLines($cart));
        $appliedVoucher = $appliedVouchers->first();

        // Flash units, bundle deals and free items, per line.
        $cartPieces = collect(\App\Support\CartPricing::price(\App\Support\CartPricing::fromCart($cart)))
            ->mapWithKeys(fn ($line) => [$line['key'] => $line['pieces']])->all();

        return view(
            'pages.cart',
            compact('cart', 'appliedVoucher', 'appliedVouchers', 'voucherNotices', 'cartPlan', 'cartPieces')
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

        $buyerId = (int) session('user.id');
        $lines = $this->buyableLines($cart);

        // A store voucher only discounts its own store's items (selected ones, if limited).
        if ($voucher->kind() === 'store' && $voucher->eligibleSubtotal($lines) <= 0) {
            return back()->with('error', $voucher->applies_to === 'selected'
                ? 'Voucher ' . $voucher->code . ' is not applicable to the items in your cart.'
                : 'This voucher only applies to a seller whose products are not in your cart.');
        }

        $base = $voucher->kind() === 'store'
            ? $voucher->eligibleSubtotal($lines)
            : round(array_sum(array_map(fn ($line) => $line['price'] * $line['quantity'], $lines)), 2);

        if ($problem = $voucher->problemFor($base)) {
            return back()->with('error', $problem);
        }

        if ($buyerId && $voucher->usedUpBy($buyerId)) {
            return back()->with('error', 'You have already used voucher "' . $voucher->code . '"' . ((int) $voucher->per_buyer_limit > 1 ? ' ' . $voucher->per_buyer_limit . ' times' : '') . '.');
        }

        // A claimable voucher typed in is saved to the wallet too.
        if ($buyerId && $voucher->display === 'claimable') {
            \App\Models\VoucherClaim::firstOrCreate(['voucher_id' => $voucher->id, 'user_id' => $buyerId]);
        }

        $replaced = \App\Support\VoucherSelection::add($voucher);

        return back()->with('success', 'Voucher "' . $voucher->code . '" applied!'
            . ($replaced ? ' It replaced ' . $replaced . ' (one voucher of each kind per order).' : ''));
    }

    public function removeVoucher()
    {
        \App\Support\VoucherSelection::remove(request('voucher_code') ?: null);

        return back()->with('success', 'Voucher removed.');
    }

    /**
     * The cart lines a buyer can actually buy now, priced like checkout:
     * ['seller_id', 'product_id', 'variation_id', 'price', 'quantity'].
     */
    private function buyableLines(array $cart): array
    {
        $lines = [];
        $products = Product::whereIn('id', array_map(fn ($key) => (int) parseCartKey($key)[0], array_keys($cart)))->get()->keyBy('id');

        foreach ($cart as $key => $quantity) {
            [$productId, $variationId] = parseCartKey($key);
            $product = $products->get((int) $productId);

            if (!$product || !$product->isPurchasable() || $product->sellerOnVacation()) {
                continue;
            }

            $variation = $variationId ? \App\Models\ProductVariation::find($variationId) : null;

            if ($variationId && !$variation) {
                continue;
            }

            $lines[] = ['key' => $key, 'product' => $product, 'variation' => $variation, 'quantity' => (int) $quantity];
        }

        // Flash units, bundle deals and free items priced like checkout.
        return \App\Support\CartPricing::planLines(\App\Support\CartPricing::price($lines));
    }

    /**
     * The checkout math with the applied vouchers. A voucher that no longer
     * applies (minimum spend not met any more, expired, ended, used up) is
     * dropped and the buyer is told why.
     *
     * @return array{0: array, 1: \Illuminate\Support\Collection, 2: string[]}
     */
    private function voucherPlan(array $lines, ?array $location = null, string $fulfillment = 'delivery'): array
    {
        $buyerId = (int) session('user.id');
        $notices = [];

        foreach (\App\Support\VoucherSelection::vouchers() as $voucher) {
            if ($buyerId && $voucher->usedUpBy($buyerId)) {
                $notices[] = 'You have already used voucher ' . $voucher->code . '. It was removed.';
                \App\Support\VoucherSelection::remove($voucher->code);
            }
        }

        $vouchers = \App\Support\VoucherSelection::vouchers();
        $plan = CheckoutPlan::build($lines, $vouchers, $location, $fulfillment);

        foreach ($plan['problems'] as $code => $problem) {
            $notices[] = $problem . ' It was removed.';
            \App\Support\VoucherSelection::remove($code);
        }

        return [$plan, $vouchers->whereIn('code', $plan['applied'])->values(), $notices];
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

            $cart = CartContents::prune();

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

            // Deactivated products and stores on vacation can't be checked out.
            if (!$products[$productId]->isPurchasable()) {
                return redirect()
                    ->route('cart')
                    ->with('error', $products[$productId]->name . ' is no longer available. Please remove it from your selection.');
            }

            if ($products[$productId]->sellerOnVacation()) {
                return redirect()
                    ->route('cart')
                    ->with('error', $products[$productId]->name . ': ' . Product::VACATION_MESSAGE);
            }

            // More in the cart than is left: lower it first ("Only 3 left").
            [, $lineVariationId] = parseCartKey($cartKey);
            $lineStock = $lineVariationId
                ? (int) \App\Models\ProductVariation::where('id', $lineVariationId)->value('stock')
                : (int) $products[$productId]->stock;

            if ((int) $quantity > $lineStock) {
                return redirect()
                    ->route('cart')
                    ->with('error', $products[$productId]->name . ': only ' . $lineStock . ' left. Lower the quantity to ' . $lineStock . ' or fewer to check out.');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | APPLIED VOUCHER (carried over from the cart page)
        |--------------------------------------------------------------------------
        */

        // Worked out below with the delivery address (CheckoutPlan + the applied vouchers).

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

        // No ordering on an incomplete account (name, phone). The address is
        // the one typed on this page, checked when the order is placed.
        $missingInfo = \App\Support\BuyerProfile::missing((int) $user['id'], false);
        $freeShippingMin = DeliveryFee::freeShippingMin();

        // Same split/fee/voucher math placeOrder will use (flash units, bundles, free items included).
        $pricedLines = \App\Support\CartPricing::price(\App\Support\CartPricing::fromCart($cart));
        $linePieces = collect($pricedLines)->mapWithKeys(fn ($line) => [$line['key'] => $line['pieces']])->all();
        $planLines = \App\Support\CartPricing::planLines($pricedLines);

        // The delivery fee depends on how far each seller is from the address.
        $deliveryTo = PhLocations::locate($savedAddress);
        [$checkoutPlan, $appliedVouchers, $voucherNotices] = $this->voucherPlan($planLines, $deliveryTo);
        $appliedVoucher = $appliedVouchers->first();
        $voucherSubtotal = $checkoutPlan['subtotal'];
        $pickupCenter = $deliveryTo ? ParcelRoute::pickupPointFor($deliveryTo['province'], $deliveryTo['city']) : null;
        $checkoutEta = self::latestEta($checkoutPlan);

        // Kept so the page can re-price delivery when another address is picked.
        session()->put('checkout_quote', ['lines' => $planLines, 'voucher_codes' => $appliedVouchers->pluck('code')->all()]);

        return view(
            'pages.checkout',
            compact(
                'user',
                'cart',
                'products',
                'appliedVoucher',
                'appliedVouchers',
                'voucherNotices',
                'voucherSubtotal',
                'linePieces',
                'savedAddress',
                'savedPhone',
                'addresses',
                'codStatus',
                'freeShippingMin',
                'checkoutPlan',
                'deliveryTo',
                'missingInfo',
                'pickupCenter',
                'checkoutEta'
            )
        );
    }

    /**
     * The checkout totals for another delivery address (JSON) — the delivery
     * fee changes with the distance from each seller's town.
     */
    public function deliveryQuote()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return response()->json(['error' => 'Please log in again.'], 401);
        }

        $quote = session()->get('checkout_quote');

        if (!$quote) {
            return response()->json(['error' => 'Please reopen checkout.'], 409);
        }

        $location = PhLocations::locate((string) request('address'));
        $voucher = !empty($quote['voucher_codes']) ? \App\Models\Voucher::whereIn('code', $quote['voucher_codes'])->get() : null;
        $pickupCenter = $location ? ParcelRoute::pickupPointFor($location['province'], $location['city']) : null;
        $fulfillment = request('fulfillment') === 'pickup' && $pickupCenter ? 'pickup' : 'delivery';
        $plan = CheckoutPlan::build($quote['lines'], $voucher, $location, $fulfillment);

        return response()->json([
            'located' => $location ? trim(($location['city'] ? $location['city'] . ', ' : '') . str_replace(' (NCR)', '', $location['province'])) : null,
            'zones' => collect($plan['orders'])->pluck('zone')->unique()->map(fn ($z) => ParcelRoute::ZONES[$z] ?? $z)->values(),
            'eta' => self::latestEta($plan),
            'fulfillment' => $fulfillment,
            'pickup_center' => $pickupCenter ? ['name' => $pickupCenter->name, 'address' => $pickupCenter->address ?: $pickupCenter->town] : null,
            'delivery_fee' => $plan['delivery_fee'],
            'shipping_discount' => $plan['shipping_discount'],
            'total' => $plan['total'],
            'unserviceable' => $plan['unserviceable'],
        ]);
    }

    /** The arrival window of the slowest parcel in a checkout ("Oct 9–11"). */
    public static function latestEta(array $plan): ?string
    {
        $order = ['local' => 0, 'province' => 1, 'island' => 2, 'far' => 3];
        $slowest = collect($plan['orders'])->pluck('zone')->sortBy(fn ($z) => $order[$z] ?? 2)->last();

        return $slowest ? ParcelRoute::etaLabel($slowest) : null;
    }

    public function placeOrder(PlaceOrderService $orders)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        // The checkout page already blocks this; the server makes sure.
        // (The posted address is checked for a town by PlaceOrderService.)
        $missingInfo = \App\Support\BuyerProfile::missing((int) $user['id'], false);

        if ($missingInfo) {
            return redirect()
                ->route('buyer.profile')
                ->with('error', 'Complete your account before checking out — missing: ' . implode(', ', array_map('lcfirst', $missingInfo)) . '.');
        }

        // What is being ordered: a "Buy now" item, or the lines the buyer
        // selected on the cart page (everything else stays in the cart).
        $buyNow = session()->get('buy_now', []);
        $isBuyNow = !empty($buyNow);

        if ($isBuyNow) {
            $cart = $buyNow;
        } else {
            $cart = session()->get('cart', []);

            if (empty($cart)) {
                return redirect()->route('cart')->with('error', 'Your cart is empty.');
            }

            if (session()->has('checkout_selection')) {
                $cart = array_intersect_key($cart, array_flip(session()->get('checkout_selection')));

                if (empty($cart)) {
                    return redirect()->route('cart')->with('error', 'Please select at least one item to check out.');
                }
            }
        }

        // A second Place Order with the same form (double-click, Back + resend) places nothing.
        $submitToken = request(\App\Support\SubmitOnce::FIELD);

        if (!\App\Support\SubmitOnce::claim($submitToken)) {
            return redirect()->route('buyer.orders')->with('success', 'Your order was already placed.');
        }

        try {
            $orderIds = $orders->place(
                $user,
                $cart,
                request()->only('address', 'phone', 'payment', 'fulfillment', 'note', 'expected_total'),
                \App\Support\VoucherSelection::codes()
            );
        } catch (CheckoutFailed $e) {
            \App\Support\SubmitOnce::release($submitToken);

            if ($e->voucherDropped) {
                \App\Support\VoucherSelection::clear();
            }

            return $e->backToCart
                ? redirect()->route('cart')->with('error', $e->getMessage())
                : back()->withInput()->with('error', $e->getMessage());
        }

        \App\Support\VoucherSelection::clear();

        if ($isBuyNow) {
            session()->forget('buy_now');
        } else {
            // Remove only the lines that were just ordered.
            session()->put('cart', array_diff_key(session()->get('cart', []), $cart));
            session()->forget('checkout_selection');
        }

        // GCash, Maya, card: pay on the sandbox gateway first (To Pay until then).
        $first = DB::table('orders')->where('id', $orderIds[0])->first();

        if ($first && \App\Support\Payments::awaitingPayment($first)) {
            return redirect()
                ->route('payment.show', $first->payment_group)
                ->with('success', count($orderIds) > 1 ? count($orderIds) . ' orders placed — one per seller. Pay for them together below.' : 'Order placed. Complete your payment below.');
        }

        return redirect()
            ->route('orders.success', $orderIds[0])
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

        $order['eta'] = ParcelRoute::etaLabel($order['delivery_zone'] ?? null, $order['created_at'] ?? null);
        $collectAt = ParcelRoute::collectionPointId($order);
        $order['pickup_center'] = ($order['fulfillment'] ?? 'delivery') === 'pickup' && $collectAt
            ? \App\Models\SortingCenter::find($collectAt)
            : null;

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
