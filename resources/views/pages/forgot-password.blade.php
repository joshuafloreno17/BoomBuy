<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Forgot Password — BoomBuy</title>

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
        }

        .logo {
            font-size: 23px;
            font-weight: 700;
            color: #1769e0;
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

            border: 1px solid #e1e9f6;
            border-radius: 20px;

            padding: 40px;

            box-shadow:
                0 18px 45px
                rgba(39, 84, 150, 0.10);
        }

        .icon {
            width: 65px;
            height: 65px;

            margin: 0 auto 18px;

            border-radius: 16px;

            background: #e8f2ff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 28px;
        }

        .header small {
            color: #3977d5;

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
            color: #718096;
            font-size: 13px;
            line-height: 1.6;
        }

        .message {
            padding: 12px 14px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 12px;
        }

        .error {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
        }

        .success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;

            color: #334155;

            font-size: 12px;
            font-weight: 700;

            margin-bottom: 8px;
        }

        input {
            width: 100%;

            padding: 13px 14px;

            border: 1px solid #dce7fa;

            border-radius: 9px;

            background: #f8faff;

            outline: none;

            font-size: 13px;
        }

        input:focus {
            border-color: #4b8df8;
            background: #ffffff;

            box-shadow:
                0 0 0 3px
                rgba(23, 105, 224, 0.08);
        }

        button {
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

        button:hover {
            background: #0f55bd;
            transform: translateY(-1px);
        }

        .back {
            text-align: center;

            margin-top: 20px;

            font-size: 12px;
        }

        .back a {
            color: #1769e0;
            font-weight: 700;
        }

        .footer {
            text-align: center;

            color: #94a3b8;

            font-size: 11px;

            margin-top: 25px;
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

    <div>

        <div class="card">

            <div class="header">

                <div class="icon">
                    🔑
                </div>

                <small>
                    BoomBuy Account
                </small>

                <h1>
                    Forgot Password?
                </h1>

                <p>
                    Enter the email address connected
                    to your BoomBuy account.
                </p>

            </div>


            @if(session('error'))

                <div class="message error">
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
                action="{{ route('password.email') }}"
            >

                @csrf


                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter your registered email"
                        autocomplete="email"
                        required
                    >

                </div>


                <button type="submit">
                    Continue
                </button>

            </form>


            <div class="back">

                <a href="{{ route('login') }}">
                    ← Back to Login
                </a>

            </div>

        </div>


        <div class="footer">

            © 2026 BoomBuy ·
            Shop. Sell. Deliver.

        </div>

    </div>

</div>


</body>

</html>