<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login — BoomBuy</title>

    <style>
@import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: #fff2ee;
            color: #172033;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
        }

        .admin-card {
            width: 100%;
            max-width: 430px;
            background: #ffffff;
            border: 1px solid #fbe2db;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(39, 84, 150, 0.12);
        }

        .admin-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .admin-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 18px;
            border-radius: 18px;
            background: linear-gradient(145deg, #ffede8, #ffdfd5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .admin-header small {
            color: #e8420f;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 10px;
            font-weight: 700;
        }

        .admin-header h1 {
            font-size: 29px;
            margin-top: 9px;
            margin-bottom: 9px;
        }

        .admin-header p {
            color: #977970;
            font-size: 13px;
            line-height: 1.6;
        }

        .admin-notice {
            background: #fff4f1;
            border: 1px solid #ffe4dc;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 22px;
            color: #db5a33;
            font-size: 11px;
            line-height: 1.5;
        }

        .error-message {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 12px 14px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 12px;
        }

        .success-message {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 12px 14px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 12px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            color: #563a32;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #fbe2db;
            border-radius: 9px;
            background: #fffaf8;
            outline: none;
            font-size: 13px;
            color: #172033;
            transition: 0.2s;
        }

        .form-group input:focus {
            border-color: #ff7044;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(23, 105, 224, 0.08);
        }

        .login-btn {
            width: 100%;
            border: none;
            background: #e8420f;
            color: #ffffff;
            padding: 14px;
            border-radius: 9px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
        }

        .login-btn:hover {
            background: #c43408;
            transform: translateY(-1px);
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 22px;
            color: #8d6c62;
            font-size: 12px;
        }

        .back:hover {
            color: #e8420f;
        }

        .footer {
            text-align: center;
            color: #b99c93;
            font-size: 11px;
            margin-top: 25px;
        }

        .footer strong {
            color: #e8420f;
        }

        @media (max-width: 500px) {
            .admin-card {
                padding: 28px 22px;
            }

            .admin-header h1 {
                font-size: 25px;
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
</style>
</head>

<body>

<div class="page">

    <div>

        <div class="admin-card">

            <div class="admin-header">

                <div class="admin-icon">
                    🛠️
                </div>

                <small>BoomBuy Management</small>

                <h1>
                    Admin Login
                </h1>

                <p>
                    Sign in to access the BoomBuy
                    administration dashboard.
                </p>

            </div>


            <div class="admin-notice">

                🔒 This area is restricted to authorized
                BoomBuy administrators only.

            </div>


            @if(session('error'))

                <div class="error-message">
                    ❌ {{ session('error') }}
                </div>

            @endif


            @if(session('success'))

                <div class="success-message">
                    ✅ {{ session('success') }}
                </div>

            @endif


            <form
                action="{{ route('admin.login.submit') }}"
                method="POST"
            >

                @csrf

                <div class="form-group">

                    <label>
                        Admin Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        placeholder="Enter admin email"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Enter admin password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="login-btn"
                >
                    🔐 Login to Dashboard
                </button>

            </form>


            <a href="/login" class="back">
                ← Back to Customer Login
            </a>

        </div>


        <div class="footer">

            © 2026 <strong>BoomBuy</strong> ·
            Admin Portal

        </div>

    </div>

</div>

</body>
</html>