<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Checkout — BoomBuy</title>

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

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 40px auto;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .page-title h1 {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .page-title p {
            color: #977970;
            font-size: 13px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 25px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 12px 30px rgba(39, 84, 150, 0.07);
        }

        .card h2 {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #563a32;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #fbe2db;
            border-radius: 9px;
            background: #fffaf8;
            outline: none;
            font-size: 13px;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #e8420f;
            background: #ffffff;
            box-shadow:
                0 0 0 3px
                rgba(23, 105, 224, 0.08);
        }

        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .payment-option {
            position: relative;
        }

        .payment-option input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            cursor: pointer;
        }

        .payment-option label {
            display: block;
            padding: 13px 14px;
            border: 1.5px solid #fbe2db;
            border-radius: 10px;
            background: #fffaf8;
            font-size: 13px;
            font-weight: 700;
            color: #563a32;
            cursor: pointer;
            transition: 0.15s ease;
        }

        .payment-option input:checked + label {
            border-color: #e8420f;
            background: #fff1ec;
            color: #c43408;
        }

        .payment-option input:focus-visible + label {
            box-shadow: 0 0 0 3px rgba(232, 66, 15, 0.18);
        }

        .payment-option.is-disabled label {
            opacity: 0.45;
            cursor: not-allowed;
            text-decoration: line-through;
        }

        .payment-option.is-disabled input {
            cursor: not-allowed;
        }

        .payment-note {
            display: flex;
            gap: 8px;
            align-items: flex-start;

            margin-top: 12px;
            padding: 11px 13px;

            border-radius: 10px;
            background: #f7f4f2;
            color: #6b5048;

            font-size: 12px;
            line-height: 1.5;
        }

        .payment-note i {
            margin-top: 2px;
        }

        .payment-note.is-warning {
            background: #fff4e5;
            color: #8a5a00;
        }

        @media (max-width: 480px) {
            .payment-options {
                grid-template-columns: 1fr;
            }
        }

        .error-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 12px 14px;
            border-radius: 9px;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .product {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 16px 0;
            border-bottom: 1px solid #f7efed;
        }

        .product-info {
            display: flex;
            gap: 12px;
        }

        .product-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: #fff2ee;
            overflow: hidden;
            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
            color: var(--accent);
        }

        .product-name {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .product-qty {
            font-size: 11px;
            color: #977970;
        }

        .product-price {
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .subtotal-row {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;

            font-size: 13px;
            color: #8d6c62;
        }

        .subtotal-row strong {
            color: #172033;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-top: 22px;
            padding-top: 20px;
            border-top: 1px solid #ffe9e2;
        }

        .total-label {
            font-size: 15px;
            font-weight: 700;
        }

        .total-price {
            font-size: 22px;
            font-weight: 700;
            color: #e8420f;
        }

        .place-order {
            width: 100%;
            border: none;
            background: #e8420f;
            color: white;
            padding: 15px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            margin-top: 22px;
        }

        .place-order:hover {
            background: #c43408;
        }

        .secure {
            text-align: center;
            color: #b99c93;
            font-size: 11px;
            margin-top: 15px;
        }

        @media (max-width: 800px) {

            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .container {
                width: 94%;
            }

        }

    
/* ===== BoomBuy Vibrant Design System Overrides ===== */
h1, h2, h3, .logo, .hero-title, .hero h1, .section-title, .page-title,
.product-title, .price, .cta, .cta-title, .brand, .checkout-title,
.card-title, .modal-title, .auth-title, .form-title, .empty-title,
.step-title, .order-title, .stat-title, .stat-value, .banner-title {
    font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
    letter-spacing: -0.01em;
}
button, .btn, [class*="btn-"], .add-to-cart, .buy-now, .checkout-btn,
.register-btn, .login-btn, .submit-btn, .primary-btn {
    border-radius: 12px !important;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}
button:hover, .btn:hover, [class*="btn-"]:hover, .add-to-cart:hover,
.buy-now:hover, .primary-btn:hover {
    transform: translateY(-1px);
}
.card, [class*="-card"], .product-card {
    border-radius: 16px !important;
}
::selection {
    background: #ffd7c2;
    color: #7c1a00;
}

    /* Saved addresses */
    .addr-pick {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 10px;
        margin-bottom: 8px;
    }

    .addr-pick-option {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 14px;
        border: 1px solid #f0d9d1;
        border-radius: 12px;
        background: #fff;
        cursor: pointer;
        transition: border-color 0.15s ease, background 0.15s ease;
    }

    .addr-pick-option:has(input:checked) {
        border-color: #172033;
        background: #fffaf8;
    }

    .addr-pick-option input {
        margin-top: 3px;
        accent-color: #c43408;
    }

    .addr-pick-option span {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .addr-pick-option strong {
        font-size: 13.5px;
        color: #172033;
    }

    .addr-pick-option em {
        margin-left: 6px;
        padding: 1px 7px;
        border-radius: 999px;
        background: #172033;
        color: #fff;
        font-size: 10.5px;
        font-style: normal;
    }

    .addr-pick-option small {
        font-size: 12px;
        color: #6f5a53;
        overflow-wrap: anywhere;
    }

    .addr-manage {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 16px;
        font-size: 12.5px;
        font-weight: 700;
        color: #c43408;
        text-decoration: none;
    }
</style>

</head>

<body>

@include('partials.buyer-navbar', ['activeNav' => 'cart'])


<div class="container">

    <div class="page-title">

        <h1>Checkout</h1>

        <p>
            Complete your information to place your order.
        </p>

    </div>


    @if(session('error'))

        <div class="error-box">
            {{ session('error') }}
        </div>

    @endif


    <form
        action="{{ route('checkout.place') }}"
        method="POST"
    >

        @csrf


        <div class="checkout-grid">


            {{-- SHIPPING INFORMATION --}}

            <div class="card">

                <h2>
                    <i class="bi bi-geo-alt-fill"></i> Shipping Information
                </h2>

                {{-- ADDRESS BOOK: pick a saved address instead of retyping it --}}
                @if(!empty($addresses) && $addresses->count() > 0)
                    <div class="addr-pick" role="radiogroup" aria-label="Saved addresses">
                        @foreach($addresses as $addr)
                            <label class="addr-pick-option">
                                <input
                                    type="radio"
                                    name="saved_address"
                                    value="{{ $addr->id }}"
                                    data-address="{{ $addr->address }}"
                                    data-phone="{{ $addr->phone }}"
                                    @checked(!old('address') && $addr->is_default)
                                >
                                <span>
                                    <strong>
                                        {{ $addr->label ?: 'Address' }}
                                        @if($addr->is_default)
                                            <em>Default</em>
                                        @endif
                                    </strong>
                                    <small>{{ $addr->address }}</small>
                                    <small>{{ $addr->phone }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <a href="{{ route('buyer.profile') }}#addresses" class="addr-manage"><i class="bi bi-pencil"></i> Manage addresses</a>
                @else
                    <a href="{{ route('buyer.profile') }}#addresses" class="addr-manage"><i class="bi bi-plus-lg"></i> Save addresses for faster checkout</a>
                @endif


                <div class="form-group">

                    <label>
                        Full Name
                    </label>

                    <input
                        type="text"
                        value="{{ $user['name'] ?? '' }}"
                        readonly
                    >

                </div>


                <div class="form-group">

                    <label>
                        Email Address
                    </label>

                    <input
                        type="email"
                        value="{{ $user['email'] ?? '' }}"
                        readonly
                    >

                </div>


                <div class="form-group">

                    <label>
                        Delivery Address
                    </label>

                    <input
                        type="text"
                        name="address"
                        value="{{ old('address', $savedAddress ?? '') }}"
                        placeholder="House No., Street, Barangay, City"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Phone Number
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone', $savedPhone ?? '') }}"
                        placeholder="09XXXXXXXXX"
                        required
                    >

                </div>


                <h2 style="margin-top: 30px;">
                    <i class="bi bi-credit-card-fill"></i> Payment Method
                </h2>


                <div class="form-group">

                    <label>
                        Select Payment Method
                    </label>

                    <div class="payment-options">

                        @php $codBlocked = !empty($codStatus['blocked']); @endphp

                        <div class="payment-option {{ $codBlocked ? 'is-disabled' : '' }}">
                            <input
                                type="radio"
                                name="payment"
                                id="payment-cod"
                                value="Cash on Delivery"
                                {{ !$codBlocked && old('payment') === 'Cash on Delivery' ? 'checked' : '' }}
                                {{ $codBlocked ? 'disabled' : '' }}
                                required
                            >
                            <label for="payment-cod"><i class="bi bi-cash-coin"></i> Cash on Delivery</label>
                        </div>

                        <div class="payment-option">
                            <input
                                type="radio"
                                name="payment"
                                id="payment-gcash"
                                value="GCash"
                                {{ old('payment') === 'GCash' ? 'checked' : '' }}
                                required
                            >
                            <label for="payment-gcash"><i class="bi bi-phone-fill"></i> GCash</label>
                        </div>

                        <div class="payment-option">
                            <input
                                type="radio"
                                name="payment"
                                id="payment-maya"
                                value="Maya"
                                {{ old('payment') === 'Maya' ? 'checked' : '' }}
                            >
                            <label for="payment-maya"><i class="bi bi-phone-fill"></i> Maya</label>
                        </div>

                        <div class="payment-option">
                            <input
                                type="radio"
                                name="payment"
                                id="payment-card"
                                value="Credit / Debit Card"
                                {{ old('payment') === 'Credit / Debit Card' ? 'checked' : '' }}
                            >
                            <label for="payment-card"><i class="bi bi-credit-card-fill"></i> Credit / Debit Card</label>
                        </div>

                    </div>

                    @if($codBlocked)
                        <div class="payment-note is-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Cash on Delivery is paused on your account until
                            <strong>{{ $codStatus['available_at']->format('M d, Y') }}</strong>
                            because of {{ $codStatus['strikes'] }} cancelled or refused orders in the last 30 days.
                        </div>
                    @endif

                    <div class="payment-note">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>
                            <strong>Cash on Delivery</strong> orders can be cancelled while the seller is still preparing them.
                            Orders paid by <strong>GCash, Maya or card</strong> can't be cancelled after checkout — you can request a return once you receive them.
                        </span>
                    </div>

                </div>

            </div>


            {{-- ORDER SUMMARY --}}

            <div class="card">

                <h2>
                    <i class="bi bi-receipt"></i> Order Summary
                </h2>


                @php

                    $total = 0;

                @endphp


                @foreach($cart as $cartKey => $quantity)

                    @php
                        [$productId, $variationId] = parseCartKey($cartKey);
                    @endphp

                    @if(isset($products[$productId]))

                        @php

                            $product = $products[$productId];

                            $unitPrice = (float) $product['price'];
                            $lineVariation = $variationId
                                ? \App\Models\ProductVariation::find($variationId)
                                : null;

                            if ($lineVariation) {
                                $unitPrice += (float) $lineVariation->price_adjustment;
                            }

                            $subtotal = $unitPrice * $quantity;

                            $total += $subtotal;

                        @endphp


                        <div class="product">

                            <div class="product-info">

                                <div class="product-icon">
                                    <x-product-thumb :image="$product['image']" :category="$product['category']" size="48" style="width:100%; height:100%; border-radius:inherit;" />
                                </div>


                                <div>

                                    <div class="product-name">

                                        {{ $product['name'] }}

                                    </div>

                                    <div class="product-qty">

                                        Quantity:
                                        {{ $quantity }}

                                        @if($lineVariation)
                                            · {{ $lineVariation->variation_type }}: {{ $lineVariation->variation_value }}
                                        @endif

                                    </div>

                                </div>

                            </div>


                            <div class="product-price">

                                ₱{{ number_format($subtotal, 2) }}

                            </div>

                        </div>

                    @endif

                @endforeach


                <div class="subtotal-row">
                    <span>Subtotal</span>
                    <strong>₱{{ number_format($total, 2) }}</strong>
                </div>

                @php
                    // Computed by App\Support\CheckoutPlan — the same math placeOrder uses.
                    $parcelCount = count($checkoutPlan['orders']);
                    $checkoutDiscount = $checkoutPlan['discount'];
                    $checkoutDeliveryFee = $checkoutPlan['delivery_fee'];
                    $checkoutFinalTotal = $checkoutPlan['total'];
                @endphp

                <div class="subtotal-row">
                    <span>
                        Delivery Fee
                        @if($parcelCount > 1)
                            ({{ $parcelCount }} sellers)
                        @endif
                    </span>
                    <strong>{{ $checkoutDeliveryFee > 0 ? '₱' . number_format($checkoutDeliveryFee, 2) : 'FREE' }}</strong>
                </div>

                <div class="subtotal-row" style="font-size:11px; color:#8d6c62;">
                    <span>
                        @if($parcelCount > 1)
                            Items from different sellers become separate orders, each shipped on its own.
                        @endif
                        Free delivery on orders ₱{{ number_format($freeShippingMin ?? 999) }} and up{{ $parcelCount > 1 ? ' (per seller)' : '' }}.
                    </span>
                </div>

                @if($appliedVoucher && $checkoutDiscount > 0)
                    <div class="subtotal-row">
                        <span>Voucher ({{ $appliedVoucher->code }})</span>
                        <strong style="color:#15803d;">−₱{{ number_format($checkoutDiscount, 2) }}</strong>
                    </div>
                @endif

                <div class="total-row">

                    <span class="total-label">
                        Total
                    </span>

                    <span class="total-price">

                        ₱{{ number_format($checkoutFinalTotal, 2) }}

                    </span>

                </div>


                <button
                    type="submit"
                    class="place-order"
                >

                    <i class="bi bi-bag-check-fill"></i> Place Order

                </button>


                <div class="secure">

                    <i class="bi bi-shield-lock-fill"></i> Your order information is stored securely.

                </div>

            </div>


        </div>

    </form>

</div>

    <script>
    (function () {
        // Picking a saved address fills the delivery fields below.
        var address = document.querySelector('input[name="address"]');
        var phone = document.querySelector('input[name="phone"]');
        if (!address || !phone) return;

        document.querySelectorAll('input[name="saved_address"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                address.value = radio.dataset.address;
                phone.value = radio.dataset.phone;
            });
        });

        // Typing a different address un-picks the saved one.
        [address, phone].forEach(function (input) {
            input.addEventListener('input', function () {
                document.querySelectorAll('input[name="saved_address"]:checked').forEach(function (radio) {
                    if (radio.dataset.address !== address.value || radio.dataset.phone !== phone.value) {
                        radio.checked = false;
                    }
                });
            });
        });
    })();
    </script>
    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>

</html>