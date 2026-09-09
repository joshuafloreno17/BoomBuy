<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Settings — BoomBuy</title>

    <style>
@import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: #fbf6f5;
            color: #1f2937;
        }

        .container {
            width: 86%;
            max-width: 1100px;
            margin: 40px auto;
        }

        .header {
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .header p {
            color: #816f6a;
        }

        .card {
            background: white;
            border: 1px solid #ebe6e5;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,.05);
        }

        .card h2 {
            font-size: 19px;
            margin-bottom: 8px;
        }

        .card p {
            color: #816f6a;
            font-size: 14px;
        }

        .back {
            display: inline-block;
            margin-bottom: 25px;
            color: #f34f1d;
            text-decoration: none;
            font-weight: 700;
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
</style>
</head>

<body>

<div class="container">

    <a href="{{ route('admin.dashboard') }}" class="back">
        ← Back to Dashboard
    </a>

    <div class="header">
        <h1>Admin Settings</h1>
        <p>Manage your BoomBuy admin settings.</p>
    </div>

    <div class="card">
        <h2>⚙️ General Settings</h2>
        <p>System configuration and general marketplace settings.</p>
    </div>

    <div class="card">
        <h2>🔔 Notifications</h2>
        <p>Manage admin notifications and alerts.</p>
    </div>

    <div class="card">
        <h2>🔐 Security</h2>
        <p>Manage account and security preferences.</p>
    </div>

</div>

</body>
</html>