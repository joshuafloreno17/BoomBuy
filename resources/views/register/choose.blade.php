<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Choose Account Type - BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/register-choose.css') }}">
</head>
<body>

    <div class="choose-page">

        <a href="{{ route('home') }}" class="choose-logo"><img src="{{ vasset('images/logo-wordmark.svg') }}" alt="BoomBuy" class="bb-logo-wordmark" width="131" height="46"></a>

        <div class="choose-header">
            <small>Get Started</small>
            <h1>What type of account do you want to create?</h1>
            <p>Select an option below to continue your registration.</p>
        </div>

        <div class="choose-grid">

            <a href="{{ route('buyer.register') }}" class="choose-card">
                <div class="choose-icon"><i class="bi bi-bag-fill"></i></div>
                <h2>Buyer</h2>
                <p>Shop and order products from trusted sellers.</p>
                <span class="choose-btn">Continue as Buyer</span>
            </a>

            <a href="{{ route('seller.register') }}" class="choose-card">
                <div class="choose-icon"><i class="bi bi-shop"></i></div>
                <h2>Seller</h2>
                <p>Open your own store and sell your products.</p>
                <span class="choose-btn">Continue as Seller</span>
            </a>

            {{-- Riders sign up inside the BoomBuy app only. --}}
            @if(\App\Support\MobileAccess::isApp(request()))
                <a href="{{ route('rider.apply') }}" class="choose-card">
                    <div class="choose-icon"><i class="bi bi-bicycle"></i></div>
                    <h2>Rider</h2>
                    <p>Deliver orders and earn on your own schedule.</p>
                    <span class="choose-btn">Continue as Rider</span>
                </a>
            @endif

        </div>

        <div class="choose-footer">
            Already have an account? <a href="{{ route('login') }}">Log in</a>
        </div>

        {{-- Sorting centers sign up on BoomBuy Logistics (a computer, not the app). --}}
        @unless(\App\Support\MobileAccess::isApp(request()))
            <a href="{{ route('logistics.home') }}" class="choose-portal">
                <i class="bi bi-truck"></i>
                <span>Run a sorting center? <strong>Go to BoomBuy Logistics →</strong></span>
            </a>
        @endunless

    </div>

    @include('partials.pwa-register')

</body>
</html>