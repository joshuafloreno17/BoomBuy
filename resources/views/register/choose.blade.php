<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Account Type - BoomBuy</title>

    @include('partials.pwa-head')

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #fffaf8;
            color: #172033;
            min-height: 100vh;
        }

        a { text-decoration: none; }

        h1, h2, .logo { font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif; }

        .choose-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 7%;
            background:
                radial-gradient(circle at 80% 15%, #ffe5dc 0, transparent 35%),
                linear-gradient(180deg, #ffffff, #fff7f4);
        }

        .choose-logo {
            font-size: 27px;
            font-weight: 800;
            color: #e8420f;
            margin-bottom: 30px;
        }

        .choose-logo span { color: #172033; }

        .choose-header {
            text-align: center;
            margin-bottom: 44px;
        }

        .choose-header small {
            display: inline-block;
            color: #e8420f;
            background: #ffefea;
            padding: 8px 13px;
            border-radius: 30px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .choose-header h1 {
            font-size: clamp(28px, 4vw, 38px);
            letter-spacing: -1px;
            margin-bottom: 10px;
        }

        .choose-header p {
            color: #8d6c62;
            font-size: 14px;
        }

        .choose-grid {
            display: flex;
            gap: 22px;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 1000px;
        }

        .choose-card {
            background: white;
            border: 1px solid #f4e2dc;
            border-radius: 20px;
            padding: 34px 26px;
            width: 260px;
            text-align: center;
            transition: 0.22s ease;
        }

        .choose-card:hover {
            transform: translateY(-6px);
            border-color: #f1c7ba;
            box-shadow: 0 17px 34px rgba(72, 45, 35, 0.09);
        }

        .choose-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            border-radius: 17px;
            background: #fff1ed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            transition: 0.2s ease;
        }

        .choose-card:hover .choose-icon {
            background: #ffe4dc;
            transform: scale(1.05);
        }

        .choose-card h2 {
            font-size: 19px;
            margin-bottom: 8px;
            color: #2e211d;
        }

        .choose-card p {
            font-size: 12px;
            color: #977970;
            line-height: 1.6;
            margin-bottom: 22px;
            min-height: 38px;
        }

        .choose-btn {
            display: inline-block;
            background: #e8420f;
            color: white;
            font-size: 12px;
            font-weight: 800;
            padding: 11px 20px;
            border-radius: 12px;
            box-shadow: 0 10px 22px rgba(232, 66, 15, 0.16);
            transition: 0.2s ease;
        }

        .choose-card:hover .choose-btn {
            background: #cf380b;
        }

        .choose-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .choose-card.disabled:hover {
            transform: none;
            border-color: #f4e2dc;
            box-shadow: none;
        }

        .choose-footer {
            margin-top: 36px;
            color: #8d6c62;
            font-size: 13px;
        }

        .choose-footer a {
            color: #e8420f;
            font-weight: 700;
        }

        @media (max-width: 600px) {
            .choose-card { width: 100%; max-width: 300px; }
        }
    </style>
</head>
<body>

    <div class="choose-page">

        <a href="{{ route('home') }}" class="choose-logo">Boom<span>Buy</span></a>

        <div class="choose-header">
            <small>Get Started</small>
            <h1>What type of account do you want to create?</h1>
            <p>Select an option below to continue your registration.</p>
        </div>

        <div class="choose-grid">

            <a href="{{ route('buyer.register') }}" class="choose-card">
                <div class="choose-icon">🛍️</div>
                <h2>Buyer</h2>
                <p>Shop and order products from trusted sellers.</p>
                <span class="choose-btn">Continue as Buyer</span>
            </a>

            <a href="{{ route('seller.register') }}" class="choose-card">
                <div class="choose-icon">🏬</div>
                <h2>Seller</h2>
                <p>Open your own store and sell your products.</p>
                <span class="choose-btn">Continue as Seller</span>
            </a>

            <a href="{{ route('rider.apply') }}" class="choose-card">
                <div class="choose-icon">🏍️</div>
                <h2>Rider</h2>
                <p>Deliver orders and earn on your own schedule.</p>
                <span class="choose-btn">Continue as Rider</span>
            </a>

        </div>

        <div class="choose-footer">
            Already have an account? <a href="{{ route('login') }}">Log in</a>
        </div>

    </div>

    @include('partials.pwa-register')

</body>
</html>