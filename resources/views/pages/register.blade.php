```html
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account — BoomBuy</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f8ff;
            color: #172033;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        .navbar {
            width: 100%;
            background: #ffffff;
            border-bottom: 1px solid #e2eaff;
            padding: 18px 7%;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 23px;
            font-weight: 700;
            color: #1769e0;
        }

        .logo span {
            color: #172033;
        }

        .back {
            color: #64748b;
            font-size: 13px;
        }

        .back:hover {
            color: #1769e0;
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

            border: 1px solid #e1e9f6;

            border-radius: 20px;

            padding: 38px;

            box-shadow:
                0 18px 45px
                rgba(39, 84, 150, 0.10);
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

            background:
                linear-gradient(
                    145deg,
                    #e8f2ff,
                    #d5e8ff
                );

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 30px;
        }

        .register-header small {
            color: #3977d5;

            text-transform: uppercase;

            letter-spacing: 2px;

            font-size: 10px;

            font-weight: 700;
        }

        .register-header h1 {
            font-size: 30px;

            margin-top: 8px;
            margin-bottom: 8px;
        }

        .register-header p {
            color: #718096;

            font-size: 13px;

            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;

            color: #334155;

            font-size: 12px;

            font-weight: 700;

            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;

            padding: 13px 14px;

            border: 1px solid #dce7fa;

            border-radius: 9px;

            background: #f8faff;

            outline: none;

            font-size: 13px;

            color: #172033;

            transition: 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #4b8df8;

            background: #ffffff;

            box-shadow:
                0 0 0 3px
                rgba(23, 105, 224, 0.08);
        }

        .role-description {
            margin-top: 7px;

            font-size: 10px;

            color: #94a3b8;

            line-height: 1.5;
        }

        .terms {
            display: flex;

            align-items: flex-start;

            gap: 9px;

            margin: 5px 0 22px;

            color: #718096;

            font-size: 11px;

            line-height: 1.5;
        }

        .terms input {
            margin-top: 2px;

            accent-color: #1769e0;

            cursor: pointer;
        }

        .terms label {
            cursor: pointer;
        }

        .terms a {
            color: #1769e0;

            font-weight: 600;
        }

        .register-btn {
            width: 100%;

            border: none;

            background: #1769e0;

            color: white;

            padding: 14px;

            border-radius: 9px;

            cursor: pointer;

            font-size: 13px;

            font-weight: 700;

            transition: 0.2s;
        }

        .register-btn:hover {
            background: #0f55bd;

            transform: translateY(-1px);
        }

        .login-text {
            text-align: center;

            color: #718096;

            font-size: 12px;

            margin-top: 22px;
        }

        .login-text a {
            color: #1769e0;

            font-weight: 700;
        }

        .footer-text {
            text-align: center;

            color: #94a3b8;

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

        @media (max-width: 500px) {

            .navbar {
                padding: 16px 5%;
            }

            .back {
                display: none;
            }

            .register-card {
                padding: 28px 22px;
            }

            .register-header h1 {
                font-size: 26px;
            }

        }

    </style>

</head>


<body>


<nav class="navbar">

    <a href="/" class="logo">
        Boom<span>Buy</span>
    </a>

    <a href="/products" class="back">
        ← Back to Shop
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

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                        autocomplete="new-password"
                        required
                    >

                </div>


                {{-- CONFIRM PASSWORD --}}

                <div class="form-group">

                    <label for="password_confirmation">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Confirm your password"
                        autocomplete="new-password"
                        required
                    >

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

                        <a href="#">
                            Terms and Conditions
                        </a>

                        and Privacy Policy.

                    </label>

                </div>


                {{-- BUTTON --}}

                <button
                    type="submit"
                    class="register-btn"
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


</body>

</html>
```
