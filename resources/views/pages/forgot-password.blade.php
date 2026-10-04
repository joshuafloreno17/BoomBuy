<!DOCTYPE html>
<html lang="en">

<head>

    @include('partials.head', ['title' => 'Forgot Password — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/forgot-password.css') }}">

</head>

<body>


<nav class="navbar">

    <a href="/" class="logo">
        <img src="{{ asset('images/icon.svg') }}" alt="" class="bb-logo-mark" width="32" height="32"><span class="bb-logo-word">BoomBuy</span>
    </a>

</nav>


<div class="wrapper">

    <div>

        <div class="card">

            <div class="header">

                <div class="icon">
                    <i class="bi bi-key-fill"></i>
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


    @include('partials.pwa-register')

</body>

</html>