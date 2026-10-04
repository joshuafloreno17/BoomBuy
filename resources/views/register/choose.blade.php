<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Choose Account Type - BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/register-choose.css') }}">
</head>
<body>

    <div class="choose-page">

        <a href="{{ route('home') }}" class="choose-logo"><img src="{{ asset('images/icon.svg') }}" alt="" class="bb-logo-mark" width="32" height="32"><span class="bb-logo-word">BoomBuy</span></a>

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

            <a href="{{ route('rider.apply') }}" class="choose-card">
                <div class="choose-icon"><i class="bi bi-bicycle"></i></div>
                <h2>Rider</h2>
                <p>Deliver orders and earn on your own schedule.</p>
                <span class="choose-btn">Continue as Rider</span>
            </a>

            <a href="{{ route('logistics.register') }}" class="choose-card">
                <div class="choose-icon"><i class="bi bi-truck"></i></div>
                <h2>Logistics / Sorting Center</h2>
                <p>Manage parcel sorting and rider delivery assignments.</p>
                <span class="choose-btn">Continue as Logistics</span>
            </a>

        </div>

        <div class="choose-footer">
            Already have an account? <a href="{{ route('login') }}">Log in</a>
        </div>

    </div>

    @include('partials.pwa-register')

</body>
</html>