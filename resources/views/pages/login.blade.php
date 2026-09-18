<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login — BoomBuy</title>

    @include('partials.pwa-head')

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
            width: 100%;
            background: #ffffff;
            border-bottom: 1px solid #ffe9e2;
            padding: 18px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 23px;
            font-weight: 700;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .login-wrapper {
            min-height: calc(100vh - 70px);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px 20px;
        }

        .login-card {
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

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 18px;
            border-radius: 16px;
            background:
                linear-gradient(
                    145deg,
                    #ffede8,
                    #ffdfd5
                );
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }

        .login-header small {
            color: #db5a33;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 10px;
            font-weight: 700;
        }

        .login-header h1 {
            font-size: 30px;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #977970;
            font-size: 13px;
            line-height: 1.6;
        }

        .error-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 12px 14px;
            border-radius: 9px;
            font-size: 12px;
            margin-bottom: 18px;
        }

        .success-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 12px 14px;
            border-radius: 9px;
            font-size: 12px;
            margin-bottom: 18px;
        }

        .form-group {
            margin-bottom: 19px;
        }

        .form-group label {
            display: block;
            color: #563a32;
            font-size: 12px;
            font-weight: 700;
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
            color: #172033;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #ff7044;
            background: #ffffff;
            box-shadow:
                0 0 0 3px
                rgba(23, 105, 224, 0.08);
        }

        .password-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .password-row label {
            margin-bottom: 0;
        }

        .forgot {
            color: #e8420f;
            font-size: 11px;
            font-weight: 600;
        }

        /* PASSWORD SHOW / HIDE */

        .password-input-wrapper {
            position: relative;
            width: 100%;
        }

        .password-input-wrapper input {
            padding-right: 42px;
        }

        .show-password-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6a4e46;
            cursor: pointer;
            padding: 5px;
            opacity: 0.7;
            transition: opacity 0.15s ease;
        }

        .show-password-btn:hover {
            opacity: 1;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 7px;

            margin-top: 12px;

            font-size: 12px;
            color: #563a32;
            cursor: pointer;
        }

        .remember-row input {
            width: auto;
            accent-color: #e8420f;
        }

        /* ROLE */

        .role-title {
            display: block;
            margin-bottom: 10px;
            color: #563a32;
            font-size: 12px;
            font-weight: 700;
        }

        .role-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .role-option {
            position: relative;
        }

        .role-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .role-option label {
            min-height: 72px;
            border: 1px solid #fbe2db;
            background: #fffaf8;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            cursor: pointer;
            transition: 0.2s;
            margin: 0;
            font-size: 11px;
            color: #6a4e46;
        }

        .role-icon {
            font-size: 23px;
        }

        .role-option input:checked + label {
            border-color: #e8420f;
            background: #fff2ee;
            color: #e8420f;
            box-shadow:
                0 0 0 2px
                rgba(23, 105, 224, 0.08);
        }

        .role-option label:hover {
            border-color: #f9a389;
            transform: translateY(-1px);
        }

        /* LOGIN BUTTON */

        .login-btn {
            width: 100%;
            border: none;
            background: #e8420f;
            color: white;
            padding: 14px;
            border-radius: 9px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
            margin-top: 5px;
        }

        .login-btn:hover {
            background: #c43408;
            transform: translateY(-1px);
        }

        /* TERMS LINK */

        .terms-login {
            text-align: center;
            color: #977970;
            font-size: 10px;
            line-height: 1.6;
            margin-top: 17px;
        }

        .terms-login a {
            color: #e8420f;
            font-weight: 700;
            cursor: pointer;
        }

        .terms-login a:hover {
            text-decoration: underline;
        }

        .register-text {
            text-align: center;
            color: #977970;
            font-size: 12px;
            margin-top: 22px;
        }

        .register-text a {
            color: #e8420f;
            font-weight: 700;
        }

        .admin-link {
            text-align: center;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #f7efed;
        }

        .admin-link a {
            color: #8d6c62;
            font-size: 11px;
            font-weight: 600;
        }

        .admin-link a:hover {
            color: #e8420f;
        }

        .footer-text {
            text-align: center;
            color: #b99c93;
            font-size: 11px;
            margin-top: 25px;
        }

        /* TERMS MODAL */

        .terms-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(23, 32, 51, 0.55);
            z-index: 9999;
            padding: 20px;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px);
        }

        .terms-modal.show {
            display: flex;
        }

        .terms-box {
            width: 100%;
            max-width: 560px;
            max-height: 85vh;
            background: #ffffff;
            border-radius: 20px;
            box-shadow:
                0 25px 70px
                rgba(0, 0, 0, 0.20);
            overflow: hidden;
            animation: modalOpen 0.2s ease;
        }

        @keyframes modalOpen {

            from {
                opacity: 0;
                transform: translateY(12px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

        }

        .terms-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid #f4e5e0;
        }

        .terms-header h2 {
            color: #172033;
            font-size: 23px;
        }

        .terms-header p {
            color: #977970;
            font-size: 11px;
            margin-top: 3px;
        }

        .close-terms {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 50%;
            background: #fff3ef;
            color: #8d6c62;
            font-size: 20px;
            cursor: pointer;
            transition: 0.2s;
        }

        .close-terms:hover {
            background: #ffe1d7;
            color: #e8420f;
        }

        .terms-content {
            padding: 22px 24px;
            max-height: 55vh;
            overflow-y: auto;
            color: #66514a;
            font-size: 12px;
            line-height: 1.7;
        }

        .terms-content::-webkit-scrollbar {
            width: 7px;
        }

        .terms-content::-webkit-scrollbar-track {
            background: #fff7f4;
            border-radius: 10px;
        }

        .terms-content::-webkit-scrollbar-thumb {
            background: #f3c5b6;
            border-radius: 10px;
        }

        .terms-section {
            margin-bottom: 20px;
        }

        .terms-section:last-child {
            margin-bottom: 0;
        }

        .terms-section h3 {
            color: #e8420f;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .terms-section p {
            margin-bottom: 7px;
        }

        .terms-section ul {
            padding-left: 19px;
        }

        .terms-section li {
            margin-bottom: 5px;
        }

        .terms-footer {
            padding: 16px 24px 20px;
            border-top: 1px solid #f4e5e0;
            display: flex;
            justify-content: flex-end;
        }

        .terms-close-btn {
            border: none;
            background: #e8420f;
            color: #ffffff;
            padding: 11px 20px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .terms-close-btn:hover {
            background: #c43408;
            transform: translateY(-1px);
        }

        /* RESPONSIVE */

        @media (max-width: 500px) {

            .navbar {
                padding: 16px 5%;
            }

            .login-card {
                padding: 28px 22px;
            }

            .login-header h1 {
                font-size: 26px;
            }

            .role-options {
                grid-template-columns: 1fr;
            }

            .role-option label {
                min-height: 55px;
                flex-direction: row;
                justify-content: flex-start;
                padding: 0 18px;
            }

            .terms-box {
                max-height: 90vh;
            }

            .terms-content {
                max-height: 62vh;
            }

            .terms-header {
                padding: 18px;
            }

            .terms-header h2 {
                font-size: 20px;
            }

            .terms-content {
                padding: 20px 18px;
            }

            .terms-footer {
                padding: 14px 18px 18px;
            }

            .terms-close-btn {
                width: 100%;
            }
        }

        /* BOOMBUY VIBRANT DESIGN */

        h1,
        h2,
        h3,
        .logo,
        .hero-title,
        .hero h1,
        .section-title,
        .page-title,
        .product-title,
        .price,
        .cta,
        .cta-title,
        .brand,
        .checkout-title,
        .card-title,
        .modal-title,
        .auth-title,
        .form-title,
        .empty-title,
        .step-title,
        .order-title,
        .stat-title,
        .stat-value,
        .banner-title {
            font-family:
                'Baloo 2',
                'Plus Jakarta Sans',
                sans-serif;

            letter-spacing: -0.01em;
        }

        button {
            transition:
                transform 0.15s ease,
                box-shadow 0.15s ease,
                background 0.15s ease;
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


    <div class="login-wrapper">

        <div>

            <div class="login-card">

                <div class="login-header">

                    <div class="login-icon">
                        🔐
                    </div>

                    <small>
                        Welcome Back
                    </small>

                    <h1>
                        Login to BoomBuy
                    </h1>

                    <p>
                        Sign in to your account and
                        continue using BoomBuy.
                    </p>

                </div>


                {{-- ERROR --}}

                @if(session('error'))

                    <div class="error-box">
                        {{ session('error') }}
                    </div>

                @endif


                {{-- SUCCESS --}}

                @if(session('success'))

                    <div class="success-box">
                        {{ session('success') }}
                    </div>

                @endif


                {{-- VALIDATION ERRORS --}}

                @if($errors->any())

                    <div class="error-box">
                        {{ $errors->first() }}
                    </div>

                @endif


                <form
                    action="{{ route('login.submit') }}"
                    method="POST"
                >

                    @csrf


                    {{-- EMAIL --}}

                    <div class="form-group">

                        <label>
                            Email Address
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Enter your email"
                            autocomplete="email"
                            autofocus
                            required
                        >

                    </div>


                    {{-- PASSWORD --}}

                    <div class="form-group">

                        <div class="password-row">

                            <label>
                                Password
                            </label>

                            <a
                                href="{{ route('password.request') }}"
                                class="forgot"
                            >
                                Forgot password?
                            </a>

                        </div>


                        <div class="password-input-wrapper">

                            <input
                                type="password"
                                name="password"
                                id="login-password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="show-password-btn"
                                onclick="togglePassword('login-password', this)"
                                aria-label="Show password"
                            ><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>

                        </div>

                        <label class="remember-row">
                            <input type="checkbox" name="remember">
                            <span>Remember me for 30 days</span>
                        </label>

                    </div>


                    {{-- ROLE --}}

                    <div class="form-group">

                        <span class="role-title">
                            Login As
                        </span>


                        <div class="role-options">


                            {{-- BUYER --}}

                            <div class="role-option">

                                <input
                                    type="radio"
                                    name="role"
                                    id="role-buyer"
                                    value="buyer"
                                    {{ old('role') === 'buyer' ? 'checked' : '' }}
                                    required
                                >

                                <label for="role-buyer">

                                    <span class="role-icon">
                                        👤
                                    </span>

                                    <span>
                                        Buyer
                                    </span>

                                </label>

                            </div>


                            {{-- SELLER --}}

                            <div class="role-option">

                                <input
                                    type="radio"
                                    name="role"
                                    id="role-seller"
                                    value="seller"
                                    {{ old('role') === 'seller' ? 'checked' : '' }}
                                >

                                <label for="role-seller">

                                    <span class="role-icon">
                                        🛍️
                                    </span>

                                    <span>
                                        Seller
                                    </span>

                                </label>

                            </div>


                            {{-- RIDER --}}

                            <div class="role-option">

                                <input
                                    type="radio"
                                    name="role"
                                    id="role-rider"
                                    value="rider"
                                    {{ old('role') === 'rider' ? 'checked' : '' }}
                                >

                                <label for="role-rider">

                                    <span class="role-icon">
                                        🛵
                                    </span>

                                    <span>
                                        Rider
                                    </span>

                                </label>

                            </div>


                        </div>

                    </div>


                    {{-- LOGIN BUTTON --}}

                    <button
                        type="submit"
                        class="login-btn"
                    >
                        Login
                    </button>


                    {{-- TERMS --}}

                    <div class="terms-login">

                        By continuing to use BoomBuy, you acknowledge that you have
                        read our

                        <a
                            href="#"
                            onclick="openTerms(event)"
                        >
                            Terms & Conditions
                        </a>

                        and

                        <a
                            href="#"
                            onclick="openPrivacy(event)"
                        >
                            Privacy Policy
                        </a>.

                    </div>


                </form>


                <div class="register-text">

                    Don't have an account?

                    <a href="{{ route('register') }}">
                        Create one
                    </a>

                </div>


                <div class="admin-link">

                    <a href="{{ route('admin.login') }}">
                        🛠️ Admin Login
                    </a>

                </div>


            </div>


            <div class="footer-text">

                © 2026 BoomBuy ·
                Shop smarter. Live better.

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- TERMS & CONDITIONS MODAL --}}
    {{-- ===================================================== --}}

    <div
        class="terms-modal"
        id="termsModal"
        onclick="closeTermsOutside(event)"
    >

        <div class="terms-box">

            <div class="terms-header">

                <div>

                    <h2>
                        BoomBuy Terms & Conditions
                    </h2>

                    <p>
                        Please review the terms for using BoomBuy.
                    </p>

                </div>


                <button
                    type="button"
                    class="close-terms"
                    onclick="closeTerms()"
                    aria-label="Close"
                >
                    ×
                </button>

            </div>


            <div class="terms-content">


                <div class="terms-section">

                    <h3>
                        1. Account Responsibility
                    </h3>

                    <p>
                        Users are responsible for providing accurate account
                        information and keeping their login credentials secure.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        2. Account Types
                    </h3>

                    <p>
                        BoomBuy provides different account types with different
                        responsibilities.
                    </p>

                    <ul>

                        <li>
                            <strong>Buyer</strong> — may browse products,
                            manage a cart, place orders, and manage purchases.
                        </li>

                        <li>
                            <strong>Seller</strong> — may add and manage
                            products and manage seller orders.
                        </li>

                        <li>
                            <strong>Rider</strong> — may manage assigned
                            deliveries and update delivery status.
                        </li>

                    </ul>

                </div>


                <div class="terms-section">

                    <h3>
                        3. Proper Use
                    </h3>

                    <p>
                        Users must use BoomBuy responsibly and must not attempt
                        to access another user's account or misuse the system.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        4. Orders and Transactions
                    </h3>

                    <p>
                        Users should provide accurate information when placing
                        orders or performing other transactions through BoomBuy.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        5. Product Information
                    </h3>

                    <p>
                        Sellers are responsible for providing accurate product
                        names, descriptions, prices, and stock information.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        6. Delivery
                    </h3>

                    <p>
                        Buyers should provide accurate delivery details.
                        Riders are responsible for properly managing assigned
                        deliveries and updating delivery statuses.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        7. Prohibited Activities
                    </h3>

                    <ul>

                        <li>
                            Using false account information.
                        </li>

                        <li>
                            Accessing another user's account.
                        </li>

                        <li>
                            Misusing the ordering or delivery system.
                        </li>

                        <li>
                            Providing misleading product information.
                        </li>

                        <li>
                            Performing fraudulent or unauthorized activities.
                        </li>

                    </ul>

                </div>


                <div class="terms-section">

                    <h3>
                        8. Account Access
                    </h3>

                    <p>
                        Users must select the correct account type when logging
                        in. Access to features depends on the user's assigned
                        role.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        9. Agreement
                    </h3>

                    <p>
                        By using BoomBuy, users acknowledge these Terms and
                        Conditions and agree to use the platform responsibly.
                    </p>

                </div>


            </div>


            <div class="terms-footer">

                <button
                    type="button"
                    class="terms-close-btn"
                    onclick="closeTerms()"
                >
                    Close
                </button>

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- PRIVACY POLICY MODAL --}}
    {{-- ===================================================== --}}

    <div
        class="terms-modal"
        id="privacyModal"
        onclick="closePrivacyOutside(event)"
    >

        <div class="terms-box">

            <div class="terms-header">

                <div>

                    <h2>
                        BoomBuy Privacy Policy
                    </h2>

                    <p>
                        How BoomBuy handles user information.
                    </p>

                </div>


                <button
                    type="button"
                    class="close-terms"
                    onclick="closePrivacy()"
                    aria-label="Close"
                >
                    ×
                </button>

            </div>


            <div class="terms-content">


                <div class="terms-section">

                    <h3>
                        1. Information We Collect
                    </h3>

                    <p>
                        BoomBuy may store information needed to create and
                        manage accounts and process marketplace activities,
                        such as a user's name, email address, account role,
                        orders, and delivery information.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        2. How Information Is Used
                    </h3>

                    <p>
                        Information may be used to provide account access,
                        process orders, manage deliveries, display account
                        information, and operate BoomBuy features.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        3. Account Security
                    </h3>

                    <p>
                        Users should keep their passwords confidential and
                        should not share their account credentials with other
                        people.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        4. Transaction Information
                    </h3>

                    <p>
                        Information related to orders and deliveries may be
                        stored so that buyers, sellers, riders, and authorized
                        administrators can perform their respective functions.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        5. Information Protection
                    </h3>

                    <p>
                        BoomBuy is designed to limit access to information
                        according to user roles and system permissions.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        6. User Responsibility
                    </h3>

                    <p>
                        Users should make sure that the information they provide
                        is accurate and should avoid sharing personal account
                        information unnecessarily.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        7. Policy Updates
                    </h3>

                    <p>
                        The Privacy Policy may be updated when BoomBuy features
                        or requirements change.
                    </p>

                </div>


            </div>


            <div class="terms-footer">

                <button
                    type="button"
                    class="terms-close-btn"
                    onclick="closePrivacy()"
                >
                    Close
                </button>

            </div>

        </div>

    </div>


    <script>

        /* =========================
           PASSWORD SHOW / HIDE
        ========================= */

        const EYE_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        const EYE_OFF_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

        function togglePassword(inputId, button) {

            const input = document.getElementById(inputId);

            if (input.type === 'password') {

                input.type = 'text';
                button.innerHTML = EYE_ICON;
                button.setAttribute('aria-label', 'Hide password');

            } else {

                input.type = 'password';
                button.innerHTML = EYE_OFF_ICON;
                button.setAttribute('aria-label', 'Show password');

            }

        }


        /* =========================
           TERMS MODAL
        ========================= */

        function openTerms(event) {

            event.preventDefault();

            document.getElementById('privacyModal')
                .classList.remove('show');

            document.getElementById('termsModal')
                .classList.add('show');

            document.body.style.overflow = 'hidden';

        }


        function closeTerms() {

            document.getElementById('termsModal')
                .classList.remove('show');

            document.body.style.overflow = '';

        }


        function closeTermsOutside(event) {

            if (
                event.target ===
                document.getElementById('termsModal')
            ) {

                closeTerms();

            }

        }


        /* =========================
           PRIVACY MODAL
        ========================= */

        function openPrivacy(event) {

            event.preventDefault();

            document.getElementById('termsModal')
                .classList.remove('show');

            document.getElementById('privacyModal')
                .classList.add('show');

            document.body.style.overflow = 'hidden';

        }


        function closePrivacy() {

            document.getElementById('privacyModal')
                .classList.remove('show');

            document.body.style.overflow = '';

        }


        function closePrivacyOutside(event) {

            if (
                event.target ===
                document.getElementById('privacyModal')
            ) {

                closePrivacy();

            }

        }


        /* =========================
           ESC KEY
        ========================= */

        document.addEventListener('keydown', function(event) {

            if (event.key === 'Escape') {

                closeTerms();
                closePrivacy();

            }

        });

    </script>

    @include('partials.pwa-register')

</body>

</html>