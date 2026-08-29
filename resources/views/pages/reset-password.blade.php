<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create New Password — BoomBuy</title>

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

        .header {
            text-align: center;
            margin-bottom: 28px;
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
        }

        button:hover {
            background: #0f55bd;
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