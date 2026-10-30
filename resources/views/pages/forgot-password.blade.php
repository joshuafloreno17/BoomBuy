@php
    // Opened from the BoomBuy Logistics login (see AuthController::showForgotPassword).
    $isLogistics = session('auth_portal') === 'logistics';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>

    @include('partials.head', ['title' => $isLogistics ? 'Forgot Password — BoomBuy Logistics' : 'Forgot Password — BoomBuy', 'fonts' => true])

    <link rel="stylesheet" href="{{ vasset('css/views/register-shell.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/views/verify-otp.css') }}">

</head>

<body class="reg-night {{ $isLogistics ? 'is-logistics' : '' }}">

<div class="reg">

    @include('partials.register-side', [
        'logistics' => $isLogistics,
        'roleStep' => false,
        'finalStep' => '',
        'kicker' => 'Password reset',
        'headline' => ['Forgot your', 'password?'],
        'blurb' => 'It happens. We’ll email you a code so you can set a new one.',
        'steps' => [['Your email', 'mail'], ['New password', 'key']],
    ])

    <main class="reg-main">
    <div class="reg-inner otp-card">

        <div class="reg-head">
            <div class="reg-card-top">
                <span class="reg-kicker">{{ $isLogistics ? 'BoomBuy Logistics Account' : 'BoomBuy Account' }}</span>
            </div>
            <h2>Forgot password?</h2>
            <p class="otp-sub">
                Enter the email address connected to your {{ $isLogistics ? 'BoomBuy Logistics' : 'BoomBuy' }} account.
            </p>
        </div>

        @if(session('error'))
            <div class="otp-alert is-error" role="alert">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="otp-alert is-ok" role="status">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="otp-form">

            @csrf

            <label for="email" class="otp-label">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="Enter your registered email"
                autocomplete="email"
                class="auth-input"
                required
                autofocus
            >

            <button type="submit" class="otp-btn">Send the code</button>

        </form>

        <div class="otp-foot">
            <a href="{{ route($isLogistics ? 'logistics.login' : 'login') }}">← Back to login</a>
        </div>

    </div>
    </main>

</div>

<script>window.bbRegSide.show(1);</script>

@include('partials.pwa-register')

</body>

</html>
