<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: #fff7f4;
            color: #172033;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: 86%;
            max-width: 1200px;
            margin: 45px auto 80px;
        }


        .success {
            background: #ecfdf3;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .error {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 25px;
            align-items: start;
        }

        .cart-box {
            background: #fff;
            border: 1px solid #f7e5e0;
            border-radius: 18px;
            overflow: hidden;
        }

        .cart-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f9ebe7;
            font-size: 16px;
            font-weight: 700;
        }

        .cart-header-row {
            display: flex;
            align-items: center;
        }

        .select-all-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .select-all-label input {
            width: 17px;
            height: 17px;
            accent-color: #e8420f;
            cursor: pointer;
        }

        .cart-item {
            padding: 22px 24px;
            display: grid;
            grid-template-columns: 22px 90px 1fr auto;
            gap: 18px;
            align-items: center;
            border-bottom: 1px solid #f7efed;
        }

        /* One block per seller — each ships and charges delivery separately. */
        .cart-shop {
            border-bottom: 8px solid #fff7f4;
        }

        .cart-shop:last-child {
            border-bottom: none;
        }

        .cart-shop-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            border-bottom: 1px solid #f7efed;
            background: #fffaf8;
        }

        .cart-shop-head input {
            width: 18px;
            height: 18px;
            accent-color: #e8420f;
        }

        .cart-shop-head .bi-shop {
            color: #c43408;
        }

        .cart-shop-name {
            font-size: 14px;
            font-weight: 800;
            color: #172033;
            text-decoration: none;
        }

        a.cart-shop-name:hover {
            color: #c43408;
        }

        .cart-shop-chat {
            margin-left: auto;
            font-size: 12.5px;
            font-weight: 700;
            color: #c43408;
            text-decoration: none;
        }

        .cart-shop-delivery {
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            font-weight: 600;
            color: #6f5a53;
        }

        .cart-shop-delivery:empty {
            display: none;
        }

        .cart-shop-delivery i {
            color: #c43408;
        }

        .cart-shop-delivery.is-free {
            color: #0a6f66;
        }

        .cart-shop-delivery.is-free i {
            color: #0a6f66;
        }

        .cart-item.item-deselected {
            opacity: 0.5;
        }

        .cart-item.is-unavailable .product-image,
        .cart-item.is-unavailable .unit-price,
        .cart-item.is-unavailable .item-total {
            opacity: .45;
        }

        .unavailable-note {
            margin: 4px 0 6px;
            color: #b42318;
            font-size: 12px;
            font-weight: 700;
        }

        .item-checkbox {
            width: 17px;
            height: 17px;
            accent-color: #e8420f;
            cursor: pointer;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 90px;
            height: 90px;
            border-radius: 13px;
            background: linear-gradient(145deg, #ffede8, #ffdfd5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 43px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info h3 {
            font-size: 16px;
            margin-bottom: 6px;
        }

        .category {
            display: inline-block;
            background: #fff1ed;
            color: #db5a33;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .unit-price {
            color: #977970;
            font-size: 12px;
        }

        .quantity {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #fbe2db;
            background: #fffaf8;
            color: #e8420f;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 700;
        }

        .qty-btn:hover {
            background: #ffefea;
        }

        .qty-number {
            min-width: 25px;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
        }

        .item-right {
            text-align: right;
        }

        .item-total {
            color: #e8420f;
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .remove-btn {
            border: none;
            background: transparent;
            color: #ef4444;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
        }

        .remove-btn:hover {
            text-decoration: underline;
        }

        .empty-cart {
            background: #fff;
            border: 1px solid #f7e5e0;
            border-radius: 18px;
            padding: 70px 25px;
            text-align: center;
        }

        .empty-icon {
            font-size: 65px;
            margin-bottom: 15px;
        }

        .empty-cart h2 {
            font-size: 22px;
            margin-bottom: 8px;
        }

        .empty-cart p {
            color: #977970;
            font-size: 13px;
            margin-bottom: 22px;
        }

        .shop-btn {
            display: inline-block;
            background: #e8420f;
            color: #fff;
            padding: 13px 22px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
        }

        .summary {
            background: #fff;
            border: 1px solid #f7e5e0;
            border-radius: 18px;
            padding: 25px;
            position: sticky;
            top: 20px;
        }

        .summary h2 {
            font-size: 19px;
            margin-bottom: 22px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
            color: #8d6c62;
            font-size: 13px;
        }

        .summary-row strong {
            color: #172033;
        }

        .summary-total {
            border-top: 1px solid #f6e8e4;
            margin-top: 18px;
            padding-top: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary-total span {
            font-size: 14px;
            font-weight: 700;
        }

        .summary-total strong {
            color: #e8420f;
            font-size: 24px;
        }

        .checkout-btn {
            display: block;
            width: 100%;
            border: none;
            background: #e8420f;
            color: #fff;
            padding: 14px;
            border-radius: 9px;
            margin-top: 22px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
        }

        .checkout-btn:hover {
            background: #c43408;
        }

        .continue {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #db5a33;
            font-size: 12px;
            font-weight: 600;
        }

        h1, h2, h3, .logo {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
        }

        button,
        .btn,
        .checkout-btn,
        .shop-btn {
            border-radius: 12px !important;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        button:hover,
        .btn:hover,
        .checkout-btn:hover,
        .shop-btn:hover {
            transform: translateY(-1px);
        }

        ::selection {
            background: #ffd7c2;
            color: #7c1a00;
        }

        @media (max-width: 850px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }

            .summary {
                position: static;
            }
        }

        @media (max-width: 600px) {
            .container {
                width: 92%;
            }

            .cart-item {
                grid-template-columns: 20px 70px 1fr;
            }

            .product-image {
                width: 70px;
                height: 70px;
                font-size: 32px;
            }

            .item-right {
                grid-column: 3;
                text-align: left;
            }
        }
    </style>
</head>

<body>

@include('partials.buyer-navbar', ['activeNav' => 'cart'])

<div class="container">

    @php $cartUnits = array_sum(array_map('intval', $cart ?? [])); @endphp
    @include('partials.page-head', [
        'title' => 'Shopping Cart',
        'note' => $cartUnits ? $cartUnits . ' ' . \Illuminate\Support\Str::plural('item', $cartUnits) : null,
    ])

    @if(session('success'))
        <div class="success">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="error">
            {{ session('error') }}
        </div>
    @endif

    @php
        /*
        |--------------------------------------------------------------------------
        | GET CART
        |--------------------------------------------------------------------------
        */

        $cart = session()->get('cart', []);

        /*
        |--------------------------------------------------------------------------
        | GET PRODUCTS FROM DATABASE
        |--------------------------------------------------------------------------
        */

        $cartProductIds = array_map(
            fn ($key) => parseCartKey($key)[0],
            array_keys($cart)
        );

        $databaseProducts = \App\Models\Product::whereIn(
            'id',
            array_unique($cartProductIds)
        )->get()->keyBy('id');

        // Archived/flagged items and items of suspended sellers stay visible
        // (so the buyer knows what happened) but can't be selected or paid for.
        $onSaleIds = \App\Models\Product::onSale()
            ->whereIn('products.id', array_unique($cartProductIds))
            ->pluck('products.id')
            ->flip();

        $subtotal = 0;
        $totalItems = 0;

        // Grouped by seller: each seller ships, and charges delivery, separately.
        $cartGroups = [];
        foreach ($cart as $groupKey => $groupQty) {
            $groupProduct = $databaseProducts->get(parseCartKey($groupKey)[0]);
            if ($groupProduct) {
                $cartGroups[(int) $groupProduct->seller_id][$groupKey] = $groupQty;
            }
        }
        $cartShops = \App\Support\SellerShop::many(array_keys($cartGroups));
    @endphp

    @if(empty($cart))

        <div class="empty-cart">

            <div class="empty-icon">
                <i class="bi bi-cart-x"></i>
            </div>

            <h2>
                Your cart is empty
            </h2>

            <p>
                Looks like you haven't added anything yet.
            </p>

            <a href="/products" class="shop-btn">
                <i class="bi bi-bag-fill"></i> Start Shopping
            </a>

        </div>

    @else

        <div class="cart-layout">

            <div class="cart-box">

                <div class="cart-header cart-header-row">
                    <label class="select-all-label">
                        <input type="checkbox" id="selectAll" checked>
                        Cart Items
                    </label>
                </div>

                @foreach($cartGroups as $groupSellerId => $groupItems)

                @php $groupShop = $cartShops->get($groupSellerId); @endphp

                <section class="cart-shop" data-shop="{{ $groupSellerId }}">

                    <div class="cart-shop-head">
                        <input type="checkbox" class="shop-checkbox" checked aria-label="Select all items from {{ $groupShop['name'] ?? 'this shop' }}">
                        <i class="bi bi-shop" aria-hidden="true"></i>
                        @if($groupShop)
                            <a href="{{ $groupShop['url'] }}" class="cart-shop-name">{{ $groupShop['name'] }}</a>
                            <a href="{{ route('messages.thread', $groupSellerId) }}" class="cart-shop-chat"><i class="bi bi-chat-dots"></i> Chat</a>
                        @else
                            <span class="cart-shop-name">BoomBuy</span>
                        @endif
                    </div>

                @foreach($groupItems as $cartKey => $quantity)

                    @php
                        [$productId, $variationId] = parseCartKey($cartKey);
                        $product = $databaseProducts->get($productId);
                    @endphp

                    @if($product)

                        @php
                            $quantity = (int) $quantity;

                            $itemVariation = $variationId
                                ? \App\Models\ProductVariation::find($variationId)
                                : null;

                            $itemUnitPrice = (float) $product->price + ($itemVariation ? (float) $itemVariation->price_adjustment : 0);

                            $itemTotal =
                                $itemUnitPrice * $quantity;

                            $itemAvailable = $onSaleIds->has($product->id);

                            if ($itemAvailable) {
                                $subtotal += $itemTotal;
                                $totalItems += $quantity;
                            }
                        @endphp

                        <div class="cart-item {{ $itemAvailable ? '' : 'is-unavailable' }}" id="cart-item-{{ $cartKey }}" data-cart-key="{{ $cartKey }}">

                            @if($itemAvailable)
                                <input
                                    type="checkbox"
                                    class="item-checkbox"
                                    data-cart-key="{{ $cartKey }}"
                                    data-unit-price="{{ $itemUnitPrice }}"
                                    data-quantity="{{ $quantity }}"
                                    checked
                                >
                            @else
                                {{-- Not an .item-checkbox, so select-all and the totals skip it. --}}
                                <input type="checkbox" disabled aria-label="Unavailable item" style="width:18px;height:18px;flex-shrink:0;">
                            @endif

                            <div class="product-image">
                                <x-product-thumb :image="$product->image" :category="$product->category" size="90" style="width:100%; height:100%; border-radius:13px;" />
                            </div>

                            <div class="product-info">

                                <span class="category">
                                    {{ $product->category ?? 'Other' }}
                                </span>

                                <h3>
                                    {{ $product->name }}
                                </h3>

                                @if(!$itemAvailable)
                                    <div class="unavailable-note">
                                        <i class="bi bi-exclamation-circle"></i> No longer available — please remove it from your cart.
                                    </div>
                                @endif

                                <div class="unit-price">
                                    ₱{{ number_format($itemUnitPrice, 2) }} each
                                    @if($itemVariation)
                                        <br>{{ $itemVariation->variation_type }}: {{ $itemVariation->variation_value }}
                                    @endif
                                </div>

                                <div class="quantity">

                                    <form
                                        class="qty-form"
                                        data-cart-key="{{ $cartKey }}"
                                        action="{{ route('cart.update', $cartKey) }}"
                                        method="POST"
                                    >

                                        @csrf

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="decrease"
                                        >

                                        <button
                                            type="submit"
                                            class="qty-btn"
                                        >
                                            −
                                        </button>

                                    </form>

                                    <span class="qty-number" id="qty-{{ $cartKey }}">
                                        {{ $quantity }}
                                    </span>

                                    <form
                                        class="qty-form"
                                        data-cart-key="{{ $cartKey }}"
                                        action="{{ route('cart.update', $cartKey) }}"
                                        method="POST"
                                    >

                                        @csrf

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="increase"
                                        >

                                        <button
                                            type="submit"
                                            class="qty-btn"
                                        >
                                            +
                                        </button>

                                    </form>

                                </div>

                            </div>

                            <div class="item-right">

                                <div class="item-total" id="item-total-{{ $cartKey }}">
                                    ₱{{ number_format($itemTotal, 2) }}
                                </div>

                                <form
                                    class="remove-form"
                                    data-cart-key="{{ $cartKey }}"
                                    action="{{ route('cart.remove', $cartKey) }}"
                                    method="POST"
                                >

                                    @csrf

                                    <button
                                        type="submit"
                                        class="remove-btn"
                                    >
                                        <i class="bi bi-trash3-fill"></i> Remove
                                    </button>

                                </form>

                            </div>

                        </div>

                    @endif

                @endforeach

                    <div class="cart-shop-delivery" data-shop-delivery aria-live="polite"></div>

                </section>

                @endforeach

            </div>

            <div class="summary">

                <h2>
                    Order Summary
                </h2>

                <div class="summary-row">

                    <span>
                        Items
                    </span>

                    <strong id="summary-items">
                        {{ $totalItems }}
                    </strong>

                </div>

                <div class="summary-row">

                    <span>
                        Subtotal
                    </span>

                    <strong id="summary-subtotal">
                        ₱{{ number_format($subtotal, 2) }}
                    </strong>

                </div>

                <div class="summary-row">

                    <span>
                        Delivery Fee
                    </span>

                    <strong style="font-size:12px;">
                        Calculated at checkout
                    </strong>

                </div>

                @if($appliedVoucher)

                    @php
                        $discountAmount = $appliedVoucher->calculateDiscount($voucherSubtotal);
                        $finalTotal = max(0, $subtotal - $discountAmount);
                    @endphp

                    <div class="summary-row">

                        <span>
                            Voucher ({{ $appliedVoucher->code }})
                        </span>

                        <strong style="color:#15803d;">
                            −₱{{ number_format($discountAmount, 2) }}
                        </strong>

                    </div>

                    <form method="POST" action="{{ route('cart.voucher.remove') }}" style="margin-bottom:12px;">
                        @csrf
                        <button type="submit" style="border:none; background:none; color:#be123c; font-size:11px; font-weight:700; cursor:pointer; text-decoration:underline;">
                            Remove voucher
                        </button>
                    </form>

                @else

                    @php
                        $finalTotal = $subtotal;
                    @endphp

                    <form method="POST" action="{{ route('cart.voucher.apply') }}" style="display:flex; gap:8px; margin-bottom:12px;">
                        @csrf
                        <input
                            type="text"
                            name="voucher_code"
                            placeholder="Voucher code"
                            style="flex:1; padding:9px 11px; border:1px solid #f0ddd5; border-radius:8px; font-family:inherit; font-size:12px; text-transform:uppercase;"
                        >
                        <button type="submit" style="border:none; background:#172033; color:white; padding:9px 14px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer;">
                            Apply
                        </button>
                    </form>

                @endif

                <div class="summary-total">

                    <span>
                        Total
                    </span>

                    <strong id="summary-total">
                        ₱{{ number_format($finalTotal, 2) }}
                    </strong>

                </div>

                <a
                    href="{{ route('checkout') }}"
                    id="checkoutBtn"
                    class="checkout-btn"
                >
                    <i class="bi bi-credit-card-fill"></i> Proceed to Checkout
                </a>

                <a
                    href="/products"
                    class="continue"
                >
                    Continue Shopping
                </a>

            </div>

        </div>

    @endif

</div>


    <script>
        (function () {
            // A voucher's discount depends on server-side rules (percentage vs.
            // fixed, minimum order amount) that this script doesn't know, so if
            // one is applied, reload after any quantity change instead of trying
            // to patch the discounted total here — keeps the total correct and
            // re-validates the voucher (e.g. if the subtotal drops below its
            // minimum) instead of silently going stale.
            var cartHasVoucher = @json((bool) $appliedVoucher);

            function updateCartBadge(count) {
                var badge = document.getElementById('cartCount');
                if (!badge) return;

                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline-flex' : 'none';
            }

            function applySummary(data) {
                if (cartHasVoucher) {
                    location.reload();
                    return;
                }

                var itemsEl = document.getElementById('summary-items');
                var subtotalEl = document.getElementById('summary-subtotal');
                var totalEl = document.getElementById('summary-total');

                if (itemsEl) itemsEl.textContent = data.total_items;
                if (subtotalEl) subtotalEl.textContent = '₱' + data.subtotal;
                if (totalEl) totalEl.textContent = '₱' + data.subtotal;

                if (typeof data.cart_count !== 'undefined') {
                    updateCartBadge(data.cart_count);
                }
            }

            function submitCartForm(form) {
                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(form)
                })
                    .then(function (res) {
                        if (!res.ok) {
                            // Session/CSRF expired or a server error occurred —
                            // reload so the user gets a fresh, correct page
                            // instead of "undefined" values written from an
                            // error response.
                            location.reload();
                            return null;
                        }

                        return res.json();
                    })
                    .catch(function () {
                        form.submit();
                        return null;
                    })
                    .then(function (data) {
                        if (!data) return;

                        if (data.blocked) {
                            bbAlert(data.message || 'No more stock available for this product.');
                            return;
                        }

                        var cartKey = form.dataset.cartKey;

                        if (data.removed) {
                            var row = document.getElementById('cart-item-' + cartKey);
                            if (row) row.remove();
                        } else {
                            var qtyEl = document.getElementById('qty-' + cartKey);
                            var totalEl = document.getElementById('item-total-' + cartKey);
                            var checkboxEl = document.querySelector('.item-checkbox[data-cart-key="' + cartKey + '"]');

                            if (qtyEl) qtyEl.textContent = data.quantity;
                            if (totalEl) totalEl.textContent = '₱' + data.item_total;
                            if (checkboxEl) checkboxEl.dataset.quantity = data.quantity;
                        }

                        if (document.querySelectorAll('.cart-item').length === 0) {
                            location.reload();
                            return;
                        }

                        applySummary(data);
                        recomputeSelection();
                    });
            }

            document.querySelectorAll('.qty-form, .remove-form').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();

                    var actionInput = form.querySelector('input[name="action"]');

                    if (actionInput && actionInput.value === 'decrease') {
                        var qtyEl = document.getElementById('qty-' + form.dataset.cartKey);
                        var currentQty = qtyEl ? parseInt(qtyEl.textContent, 10) : 0;

                        if (currentQty <= 1) {
                            bbAlert('Quantity can\'t go below 1. Use "Remove" if you want to take this item out of your cart.');
                            return;
                        }
                    }

                    submitCartForm(form);
                });
            });

            /* =========================
               ITEM SELECTION FOR CHECKOUT
               ========================= */

            var selectAllBox = document.getElementById('selectAll');
            var checkoutBtn = document.getElementById('checkoutBtn');
            var checkoutBaseUrl = checkoutBtn ? checkoutBtn.getAttribute('href') : '';

            function getCheckboxes() {
                return Array.prototype.slice.call(document.querySelectorAll('.item-checkbox'));
            }

            function recomputeSelection() {
                var boxes = getCheckboxes();
                var items = 0;
                var subtotal = 0;

                boxes.forEach(function (box) {
                    var row = document.getElementById('cart-item-' + box.dataset.cartKey);
                    if (row) row.classList.toggle('item-deselected', !box.checked);

                    if (box.checked) {
                        var qty = parseInt(box.dataset.quantity, 10) || 0;
                        var price = parseFloat(box.dataset.unitPrice) || 0;
                        items += qty;
                        subtotal += qty * price;
                    }
                });

                var itemsEl = document.getElementById('summary-items');
                var subtotalEl = document.getElementById('summary-subtotal');
                var totalEl = document.getElementById('summary-total');

                if (itemsEl) itemsEl.textContent = items;
                if (subtotalEl) subtotalEl.textContent = '₱' + subtotal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                // A voucher's discount is server-side logic (percentage vs. fixed,
                // minimum order amount) — only mirror the total here when there's
                // no voucher to keep this in sync without reimplementing that math.
                if (!cartHasVoucher && totalEl) {
                    totalEl.textContent = '₱' + subtotal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }

                if (selectAllBox) {
                    var checkedCount = boxes.filter(function (b) { return b.checked; }).length;
                    selectAllBox.checked = checkedCount === boxes.length;
                    selectAllBox.indeterminate = checkedCount > 0 && checkedCount < boxes.length;
                }

                updateShops();
            }

            /* =========================
               PER-SHOP: select all + delivery fee
               ========================= */

            var deliveryFee = @json(\App\Support\DeliveryFee::baseFee());
            var freeDeliveryMin = @json(\App\Support\DeliveryFee::FREE_SHIPPING_MIN);
            var peso = function (n) {
                return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            };

            function updateShops() {
                document.querySelectorAll('.cart-shop').forEach(function (shop) {
                    var boxes = Array.prototype.slice.call(shop.querySelectorAll('.item-checkbox'));

                    // A shop whose last item was removed disappears.
                    if (boxes.length === 0) {
                        shop.remove();
                        return;
                    }

                    var checked = boxes.filter(function (b) { return b.checked; });
                    var shopBox = shop.querySelector('.shop-checkbox');
                    shopBox.checked = checked.length === boxes.length;
                    shopBox.indeterminate = checked.length > 0 && checked.length < boxes.length;

                    var subtotal = checked.reduce(function (sum, b) {
                        return sum + (parseInt(b.dataset.quantity, 10) || 0) * (parseFloat(b.dataset.unitPrice) || 0);
                    }, 0);

                    var line = shop.querySelector('[data-shop-delivery]');
                    line.classList.remove('is-free');

                    if (checked.length === 0) {
                        line.innerHTML = '<i class="bi bi-info-circle"></i> No items selected from this shop';
                    } else if (subtotal >= freeDeliveryMin) {
                        line.classList.add('is-free');
                        line.innerHTML = '<i class="bi bi-truck"></i> Free delivery from this shop';
                    } else {
                        line.innerHTML = '<i class="bi bi-truck"></i> Delivery ' + peso(deliveryFee)
                            + ' — add ' + peso(freeDeliveryMin - subtotal) + ' more from this shop for free delivery';
                    }
                });
            }

            document.querySelectorAll('.shop-checkbox').forEach(function (shopBox) {
                shopBox.addEventListener('change', function () {
                    shopBox.closest('.cart-shop').querySelectorAll('.item-checkbox').forEach(function (box) {
                        box.checked = shopBox.checked;
                    });
                    recomputeSelection();
                });
            });

            getCheckboxes().forEach(function (box) {
                box.addEventListener('change', recomputeSelection);
            });

            if (selectAllBox) {
                selectAllBox.addEventListener('change', function () {
                    getCheckboxes().forEach(function (box) {
                        box.checked = selectAllBox.checked;
                    });
                    recomputeSelection();
                });
            }

            updateShops();

            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function (e) {
                    e.preventDefault();

                    var selectedKeys = getCheckboxes()
                        .filter(function (b) { return b.checked; })
                        .map(function (b) { return b.dataset.cartKey; });

                    if (selectedKeys.length === 0) {
                        bbAlert('Select at least one item to check out.');
                        return;
                    }

                    var separator = checkoutBaseUrl.indexOf('?') === -1 ? '?' : '&';
                    window.location.href = checkoutBaseUrl + separator + 'items=' + encodeURIComponent(selectedKeys.join(','));
                });
            }
        })();
    </script>

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>
</html>