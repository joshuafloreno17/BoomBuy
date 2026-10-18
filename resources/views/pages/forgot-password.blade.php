@php
    // Opened from the BoomBuy Logistics login (see AuthController::showForgotPassword).
    $isLogistics = session('auth_portal') === 'logistics';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>

    @include('partials.head', ['title' => $isLogistics ? 'Forgot Password — BoomBuy Logistics' : 'Forgot Password — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/forgot-password.css') }}">

</head>

<body>


<nav class="navbar">

    @include('partials.auth-logo', ['logistics' => $isLogistics])

</nav>


<div class="wrapper">

    <div>

        <div class="card">

            <div class="header">

                <div class="icon">
                    <i class="bi bi-key-fill"></i>
                </div>

                <small>
                    {{ $isLogistics ? 'BoomBuy Logistics Account' : 'BoomBuy Account' }}
                </small>

                <h1>
                    Forgot Password?
                </h1>

                <p>
                    Enter the email address connected
                    to your {{ $isLogistics ? 'BoomBuy Logistics' : 'BoomBuy' }} account.
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

                <a href="{{ route($isLogistics ? 'logistics.login' : 'login') }}">
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