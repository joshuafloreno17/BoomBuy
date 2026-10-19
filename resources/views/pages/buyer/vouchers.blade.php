<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'My Vouchers — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/buyer-wishlist.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/views/buyer-vouchers.css') }}">
</head>

<body>

    @include('partials.buyer-navbar', ['activeNav' => 'vouchers'])

    <div class="container">

        @include('partials.page-head', [
            'title' => 'My Vouchers',
            'note' => $wallet->count() . ' ' . \Illuminate\Support\Str::plural('voucher', $wallet->count()) . ' ready to use',
        ])

        @if (session('success'))
            <div class="success-box">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="error-box">{{ session('error') }}</div>
        @endif

        @if($wallet->isNotEmpty())
            <div class="wallet">
                @foreach($wallet as $voucher)
                    <article class="wallet-card" data-wallet-voucher="{{ $voucher->code }}">
                        <div class="wallet-main">
                            <span class="wallet-kind">{{ ['store' => $shops->get($voucher->seller_id)['name'] ?? 'Store voucher', 'platform' => 'BoomBuy', 'shipping' => 'Shipping'][$voucher->kind()] }}</span>
                            <strong>{{ $voucher->summary() }}</strong>
                            <small>Code <b>{{ $voucher->code }}</b>{{ $voucher->ends_at ? ' · valid until ' . $voucher->ends_at->format('M d, Y g:i A') : '' }}</small>
                        </div>
                        @if($voucher->kind() === 'store' && $shops->get($voucher->seller_id))
                            <a href="{{ $shops->get($voucher->seller_id)['url'] }}" class="wallet-btn">Shop</a>
                        @else
                            <a href="{{ route('products') }}" class="wallet-btn">Shop</a>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <p class="wallet-empty">No vouchers yet. Claim them on store pages and product pages, then apply them in your cart.</p>
        @endif

        @if($platform->isNotEmpty())
            <h2 class="wallet-heading">BoomBuy vouchers to claim</h2>
            @include('partials.voucher-strip', ['vouchers' => $platform])
        @endif

    </div>

    @include('partials.pwa-register')

</body>
</html>
