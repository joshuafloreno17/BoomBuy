<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account — BoomBuy</title>

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

        .register-wrapper {
            min-height: calc(100vh - 70px);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px 20px;
        }

        .register-card {
            width: 100%;
            max-width: 470px;
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 20px;
            padding: 38px;
            box-shadow: 0 18px 45px rgba(39, 84, 150, 0.10);
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 18px;
            border-radius: 16px;
            background: linear-gradient(145deg, #ffede8, #ffdfd5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }

        .register-header small {
            color: #db5a33;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 10px;
            font-weight: 700;
        }

        .register-header h1 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 30px;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .register-header p {
            color: #977970;
            font-size: 13px;
            line-height: 1.6;
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
            transition: 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #ff7044;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(232, 66, 15, 0.08);
        }

        .role-description {
            margin-top: 7px;
            font-size: 10px;
            color: #b99c93;
            line-height: 1.5;
        }

        /* TERMS */

        .terms {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin: 5px 0 22px;
            color: #977970;
            font-size: 11px;
            line-height: 1.5;
        }

        .terms input {
            margin-top: 2px;
            width: 15px;
            height: 15px;
            accent-color: #e8420f;
            cursor: pointer;
            flex-shrink: 0;
        }

        .terms label {
            cursor: pointer;
        }

        .terms a {
            color: #e8420f;
            font-weight: 700;
            cursor: pointer;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        /* REGISTER BUTTON */

        .register-btn {
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
        }

        .register-btn:hover {
            background: #c43408;
            transform: translateY(-1px);
        }

        .register-btn:disabled {
            background: #f3b7a5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .login-text {
            text-align: center;
            color: #977970;
            font-size: 12px;
            margin-top: 22px;
        }

        .login-text a {
            color: #e8420f;
            font-weight: 700;
        }

        .footer-text {
            text-align: center;
            color: #b99c93;
            font-size: 11px;
            margin-top: 25px;
        }

        .error-message {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 11px 13px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 12px;
            line-height: 1.6;
        }

        .success-message {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 11px 13px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 12px;
        }

        /* PASSWORD */

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 62px;
        }

        .show-password-btn {
            position: absolute;
            right: 9px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #e8420f;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            padding: 5px;
        }

        .show-password-btn:hover {
            color: #c43408;
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
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.20);
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
            font-family: 'Baloo 2', sans-serif;
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
            font-family: 'Baloo 2', sans-serif;
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
            gap: 10px;
        }

        .terms-cancel-btn,
        .terms-agree-btn {
            border: none;
            padding: 11px 18px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .terms-cancel-btn {
            background: #f8f0ed;
            color: #755c54;
        }

        .terms-cancel-btn:hover {
            background: #eee1dc;
        }

        .terms-agree-btn {
            background: #e8420f;
            color: #ffffff;
        }

        .terms-agree-btn:hover {
            background: #c43408;
            transform: translateY(-1px);
        }

        /* BOOMBUY DESIGN */

        h1,
        h2,
        h3,
        .logo {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
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

        @media (max-width: 500px) {

            .register-card {
                padding: 28px 22px;
            }

            .register-header h1 {
                font-size: 26px;
            }

            .terms-box {
                max-height: 90vh;
            }

            .terms-content {
                max-height: 62vh;
            }

            .terms-footer {
                flex-direction: column;
            }

            .terms-cancel-btn,
            .terms-agree-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <a href="/" class="logo">
            Boom<span>Buy</span>
        </a>
    </nav>

    <div class="register-wrapper">

        <div>

            <div class="register-card">

                <div class="register-header">

                    <div class="register-icon">
                        👤
                    </div>

                    <small>
                        BoomBuy Account
                    </small>

                    <h1>
                        Create your account
                    </h1>

                    <p>
                        Join BoomBuy and enjoy a better way
                        to shop, sell, and deliver products.
                    </p>

                </div>

                {{-- ERROR MESSAGE --}}

                @if(session('error'))
                    <div class="error-message">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- SUCCESS MESSAGE --}}

                @if(session('success'))
                    <div class="success-message">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- VALIDATION ERRORS --}}

                @if($errors->any())
                    <div class="error-message">
                        @foreach($errors->all() as $error)
                            <div>
                                {{ $error }}
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- REGISTER FORM --}}

                <form
                    method="POST"
                    action="{{ route('register.submit') }}"
                    id="registerForm"
                >

                    @csrf

                    {{-- NAME --}}

                    <div class="form-group">

                        <label for="name">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Enter your full name"
                            autocomplete="name"
                            required
                        >

                    </div>

                    {{-- EMAIL --}}

                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Enter your email"
                            autocomplete="email"
                            required
                        >

                    </div>

                    {{-- ROLE --}}

                    <div class="form-group">

                        <label for="role">
                            Account Type
                        </label>

                        <select
                            id="role"
                            name="role"
                            required
                            onchange="handleRoleChange(this)"
                        >

                            <option value="">
                                Select your account type
                            </option>

                            <option
                                value="buyer"
                                {{ old('role') === 'buyer' ? 'selected' : '' }}
                            >
                                🛒 Buyer — I want to shop
                            </option>

                            <option
                                value="seller"
                                {{ old('role') === 'seller' ? 'selected' : '' }}
                            >
                                🏪 Seller — I want to sell products
                            </option>

                            <option
                                value="rider"
                                {{ old('role') === 'rider' ? 'selected' : '' }}
                            >
                                🛵 Rider — I want to deliver orders
                            </option>

                        </select>

                        <div class="role-description">
                            Choose the account type you will use
                            on BoomBuy.
                        </div>

                    </div>

                    {{-- PASSWORD --}}

                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <div class="password-wrapper">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Create a password"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="show-password-btn"
                                onclick="togglePassword('password', this)"
                            >
                                Show
                            </button>

                        </div>

                    </div>

                    {{-- CONFIRM PASSWORD --}}

                    <div class="form-group">

                        <label for="password_confirmation">
                            Confirm Password
                        </label>

                        <div class="password-wrapper">

                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                placeholder="Confirm your password"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="show-password-btn"
                                onclick="togglePassword('password_confirmation', this)"
                            >
                                Show
                            </button>

                        </div>

                    </div>

                    {{-- TERMS --}}

                    <div class="terms">

                        <input
                            type="checkbox"
                            id="terms"
                            name="terms"
                            value="1"
                            required
                        >

                        <label for="terms">

                            I agree to the BoomBuy

                            <a
                                href="#"
                                onclick="openTerms(event)"
                            >
                                Terms and Conditions
                            </a>

                            and Privacy Policy.

                        </label>

                    </div>

                    {{-- BUTTON --}}

                    <button
                        type="submit"
                        class="register-btn"
                        id="registerBtn"
                    >
                        Create Account
                    </button>

                </form>

                <div class="login-text">

                    Already have an account?

                    <a href="{{ route('login') }}">
                        Login here
                    </a>

                </div>

            </div>

            <div class="footer-text">
                © 2026 BoomBuy ·
                Shop. Sell. Deliver.
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
                        Please read these terms before creating your account.
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
                        By creating a BoomBuy account, you agree to provide
                        accurate and complete information. You are responsible
                        for keeping your account information and password secure.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        2. Account Types
                    </h3>

                    <p>
                        BoomBuy provides different account types with different
                        responsibilities:
                    </p>

                    <ul>

                        <li>
                            <strong>Buyer</strong> — may browse products,
                            add items to cart, place orders, and manage purchases.
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
                        3. Buying and Selling
                    </h3>

                    <p>
                        Buyers should review product information before placing
                        an order. Sellers are responsible for providing accurate
                        product descriptions, prices, stock information, and
                        other details about their products.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        4. Orders and Delivery
                    </h3>

                    <p>
                        Once an order is placed, the order may go through
                        processing, pickup, delivery, and completion stages.
                        Users agree to provide accurate delivery information
                        to help ensure successful delivery.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        5. Payments
                    </h3>

                    <p>
                        Users agree to provide accurate payment information
                        when required. BoomBuy users must not use fraudulent,
                        unauthorized, or misleading payment information.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        6. Product Information
                    </h3>

                    <p>
                        Sellers must not intentionally provide false,
                        misleading, or inappropriate product information.
                        Products listed on BoomBuy should follow applicable
                        platform rules and school-project requirements.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        7. Privacy
                    </h3>

                    <p>
                        BoomBuy may collect account and transaction information
                        needed to operate the marketplace. Users should avoid
                        sharing their passwords or other account credentials
                        with other people.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        8. Prohibited Activities
                    </h3>

                    <ul>

                        <li>
                            Creating accounts using false information.
                        </li>

                        <li>
                            Attempting to access another user's account.
                        </li>

                        <li>
                            Misusing the ordering or delivery system.
                        </li>

                        <li>
                            Uploading misleading or inappropriate product information.
                        </li>

                        <li>
                            Using BoomBuy for fraudulent or unauthorized activities.
                        </li>

                    </ul>

                </div>


                <div class="terms-section">

                    <h3>
                        9. Account Access
                    </h3>

                    <p>
                        BoomBuy administrators may monitor account and system
                        activity to maintain the proper operation of the
                        marketplace. Users are expected to follow the assigned
                        responsibilities of their selected account type.
                    </p>

                </div>


                <div class="terms-section">

                    <h3>
                        10. Agreement
                    </h3>

                    <p>
                        By checking the agreement box and creating an account,
                        you confirm that you have read and understood these
                        Terms and Conditions and agree to follow them while
                        using BoomBuy.
                    </p>

                </div>

            </div>


            <div class="terms-footer">

                <button
                    type="button"
                    class="terms-cancel-btn"
                    onclick="closeTerms()"
                >
                    Close
                </button>

                <button
                    type="button"
                    class="terms-agree-btn"
                    onclick="agreeToTerms()"
                >
                    ✓ I Understand & Agree
                </button>

            </div>

        </div>

    </div>


    <script>

        /* =========================
           PASSWORD SHOW / HIDE
        ========================= */

        function togglePassword(inputId, button) {

            const input = document.getElementById(inputId);

            if (input.type === 'password') {

                input.type = 'text';
                button.textContent = 'Hide';

            } else {

                input.type = 'password';
                button.textContent = 'Show';

            }
        }


        /* =========================
           RIDER APPLICATION REDIRECT
        ========================= */

        function handleRoleChange(select) {

            if (select.value === 'rider') {

                window.location.href = "{{ route('rider.apply') }}";

            }

        }


        /* =========================
           TERMS MODAL
        ========================= */

        function openTerms(event) {

            event.preventDefault();

            document
                .getElementById('termsModal')
                .classList.add('show');

            document.body.style.overflow = 'hidden';
        }


        function closeTerms() {

            document
                .getElementById('termsModal')
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


        function agreeToTerms() {

            const checkbox =
                document.getElementById('terms');

            checkbox.checked = true;

            closeTerms();

            updateRegisterButton();
        }


        /* =========================
           REGISTER BUTTON STATE
        ========================= */

        const termsCheckbox =
            document.getElementById('terms');

        const registerButton =
            document.getElementById('registerBtn');


        function updateRegisterButton() {

            if (termsCheckbox.checked) {

                registerButton.disabled = false;

            } else {

                registerButton.disabled = true;

            }

        }


        termsCheckbox.addEventListener(
            'change',
            updateRegisterButton
        );


        updateRegisterButton();


        /* =========================
           ESC KEY CLOSE MODAL
        ========================= */

        document.addEventListener(
            'keydown',
            function(event) {

                if (event.key === 'Escape') {

                    closeTerms();

                }

            }
        );

    </script>

    @include('partials.pwa-register')

</body>

</html>