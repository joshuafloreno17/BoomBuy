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

    <link rel="stylesheet" href="{{ vasset('css/views/checkout.css') }}">

</head>

<body>

@include('partials.buyer-navbar', ['activeNav' => 'cart'])


<div class="container">

    @include('partials.page-head', ['title' => 'Checkout', 'crumbs' => ['Cart' => route('cart')]])


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
                                {{ !$codBlocked && old('payment', 'Cash on Delivery') === 'Cash on Delivery' ? 'checked' : '' }}
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
                                    <x-product-thumb :image="($lineVariation->image ?? null) ?: $product['image']" :category="$product['category']" size="48" style="width:100%; height:100%; border-radius:inherit;" />
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

                <div class="subtotal-row" style="font-size:11px; color:#6b6570;">
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