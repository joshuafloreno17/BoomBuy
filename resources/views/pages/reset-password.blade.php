<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create New Password — BoomBuy</title>

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
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #ffe9e2;
            padding: 18px 7%;
        }

        .logo {
            font-size: 23px;
            font-weight: 700;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .wrapper {
            min-height: calc(100vh - 70px);

            display: flex;
            justify-content: center;
            align-items: center;

            padding: 40px 20px;
        }

        .card {
            width: 100%;
            max-width: 440px;

            background: #ffffff;

            border: 1px solid #f7e5e0;
            border-radius: 20px;

            padding: 40px;

            box-shadow:
                0 18px 45px
                rgba(39, 84, 150, 0.10);
        }

        .header {
            text-align: center;
            margin-bottom: 28px;
        }

        .icon {
            width: 65px;
            height: 65px;

            margin: 0 auto 18px;

            border-radius: 16px;

            background: #ffede8;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 30px;
        }

        .header small {
            color: #db5a33;

            text-transform: uppercase;
            letter-spacing: 2px;

            font-size: 10px;
            font-weight: 700;
        }

        .header h1 {
            font-size: 28px;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .header p {
            color: #977970;
            font-size: 13px;
        }

        .message {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;

            padding: 12px 14px;

            border-radius: 9px;

            margin-bottom: 18px;

            font-size: 12px;
        }

        .success {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #15803d;
        }

        .form-group {
            margin-bottom: 19px;
        }

        label {
            display: block;

            color: #563a32;

            font-size: 12px;
            font-weight: 700;

            margin-bottom: 8px;
        }

        input {
            width: 100%;

            padding: 13px 14px;

            border: 1px solid #fbe2db;

            border-radius: 9px;

            background: #fffaf8;

            outline: none;

            font-size: 13px;
        }

        input:focus {
            border-color: #ff7044;
            background: #ffffff;
        }

        button {
            width: 100%;

            border: none;

            background: #e8420f;
            color: white;

            padding: 14px;

            border-radius: 9px;

            cursor: pointer;

            font-size: 13px;
            font-weight: 700;
        }

        button:hover {
            background: #c43408;
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

<nav class="navbar">

    <a href="/" class="logo">
        Boom<span>Buy</span>
    </a>

</nav>


<div class="wrapper">

    <div class="card">

        <div class="header">

            <div class="icon">
                🔐
            </div>

            <small>
                BoomBuy Security
            </small>

            <h1>
                Create New Password
            </h1>

            <p>
                Enter your new password below.
            </p>

        </div>


        @if(session('error'))

            <div class="message">
                {{ session('error') }}
            </div>

        @endif


        @if(session('success'))

            <div class="message success">
                {{ session('success') }}
            </div>

        @endif


        <form
            method="POST"
            action="{{ route('password.update') }}"
        >

            @csrf


            <div class="form-group">

                <label>
                    New Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter new password"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Confirm New Password
                </label>

                <input
                    type="password"
                    name="password_confirmation"
                    placeholder="Confirm new password"
                    required
                >

            </div>


            <button type="submit">

                Change Password

            </button>

        </form>

    </div>

</div>

</body>

</html>