<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login — BoomBuy</title>

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

            border: 1px solid #e1e9f6;

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
                    #e8f2ff,
                    #d5e8ff
                );

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 30px;
        }

        .login-header small {
            color: #3977d5;

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
            color: #718096;

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

        }

        .form-group input:focus,
        .form-group select:focus {

            border-color: #4b8df8;

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

            color: #1769e0;

            font-size: 11px;

            font-weight: 600;
        }

        .role-title {

            display: block;

            margin-bottom: 10px;

            color: #334155;

            font-size: 12px;

            font-weight: 700;
        }

        .role-options {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

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

            border: 1px solid #dce7fa;

            background: #f8faff;

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

            color: #475569;

        }

        .role-icon {
            font-size: 23px;
        }

        .role-option input:checked + label {

            border-color: #1769e0;

            background: #eef5ff;

            color: #1769e0;

            box-shadow:
                0 0 0 2px
                rgba(23, 105, 224, 0.08);
        }

        .role-option label:hover {

            border-color: #8db7f5;

            transform: translateY(-1px);
        }

        .login-btn {

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

            margin-top: 5px;
        }

        .login-btn:hover {

            background: #0f55bd;

            transform: translateY(-1px);
        }

        .register-text {

            text-align: center;

            color: #718096;

            font-size: 12px;

            margin-top: 22px;
        }

        .register-text a {

            color: #1769e0;

            font-weight: 700;
        }

        .admin-link {

            text-align: center;

            margin-top: 18px;

            padding-top: 18px;

            border-top: 1px solid #edf1f7;
        }

        .admin-link a {

            color: #64748b;

            font-size: 11px;

            font-weight: 600;
        }

        .admin-link a:hover {

            color: #1769e0;
        }

        .footer-text {

            text-align: center;

            color: #94a3b8;

            font-size: 11px;

            margin-top: 25px;
        }

        @media (max-width: 500px) {

            .navbar {
                padding: 16px 5%;
            }

            .back {
                display: none;
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
                            href="#"
                            class="forgot"
                            onclick="return false;"
                        >
                            Forgot password?
                        </a>

                    </div>

                    <input
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

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


</body>

</html>