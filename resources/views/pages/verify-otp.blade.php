@php
    // The last step of every sign-up: the left side shows the steps of the
    // form they just filled in, all done, with the email code next.
    $role = session('pending_registration.role') ?? 'buyer';
    $isLogistics = $role === 'logistics';

    $flows = [
        'buyer' => ['Buyer account', route('buyer.register'), [
            ['Personal info', 'user'], ['Delivery address', 'pin'], ['ID and password', 'shield'], ['Review', 'review'],
        ]],
        'seller' => ['Seller account', route('seller.register'), [
            ['Personal info', 'user'], ['Business details', 'brief'], ['Shop address', 'pin'], ['Documents', 'docs'],
        ]],
        'logistics' => ['Logistics account', route('logistics.register'), [
            ['Contact person', 'user'], ['Company details', 'brief'], ['Address and service areas', 'map'], ['Documents', 'docs'],
        ]],
        'rider' => ['Rider account', route('rider.apply'), [
            ['Rider application', 'user'],
        ]],
    ];
    [$kicker, $registerUrl, $steps] = $flows[$role] ?? $flows['buyer'];

    // Counts down from when the code was sent, so it keeps running
    // while the user is away in the mail app.
    $otpExpiresAt = session('otp_expires_at');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $isLogistics ? 'Verify Code - BoomBuy Logistics' : 'Verify Code - BoomBuy', 'fonts' => true])

    <link rel="stylesheet" href="{{ vasset('css/views/register-shell.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/views/verify-otp.css') }}">
</head>
<body class="reg-night {{ $isLogistics ? 'is-logistics' : '' }}">

<div class="reg">

    @include('partials.register-side', [
        'logistics' => $isLogistics,
        'verifying' => true,
        'kicker' => $kicker,
        'headline' => ['One last', 'step.'],
        'blurb' => 'We just need to confirm your email address.',
        'steps' => $steps,
    ])

    <main class="reg-main">
    <div class="reg-inner otp-card">

        <div class="reg-head">
            <div class="reg-card-top">
                <span class="reg-kicker">Verify your email</span>
            </div>
            <h2>Enter the 6-digit code</h2>
            <p class="otp-sub">
                We sent a code to <strong>{{ session('otp_email') }}</strong>. It expires in 5 minutes.
            </p>
        </div>

        @if ($otpExpiresAt)
            <p class="otp-timer" id="otpTimer" data-expires="{{ \Illuminate\Support\Carbon::parse($otpExpiresAt)->toIso8601String() }}">
                Code expires in <strong id="otpTimerLeft">5:00</strong>
            </p>
        @endif

        @if (session('error'))
            <div class="otp-alert is-error" role="alert">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="otp-alert is-error" role="alert">{{ $errors->first() }}</div>
        @endif

        @if (session('status'))
            <div class="otp-alert is-ok" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('otp.verify') }}" class="otp-form">
            @csrf
            <label for="otp_code" class="otp-label">Verification code</label>
            <input type="text" id="otp_code" name="otp_code" maxlength="6" placeholder="000000"
                inputmode="numeric" pattern="\d{6}" title="Enter the 6-digit code." autocomplete="one-time-code"
                class="otp-input" required autofocus>

            <button type="submit" class="otp-btn">Verify and finish</button>
        </form>

        <div class="otp-foot">
            <a href="{{ $registerUrl }}">← Wrong email? Start again</a>

            <form method="POST" action="{{ route('otp.resend') }}">
                @csrf
                <span>Didn’t get it?</span>
                <button type="submit">Resend code</button>
            </form>
        </div>

    </div>
    </main>

</div>

<script>
(function () {
    var timer = document.getElementById('otpTimer');
    if (!timer) return;
    var left = document.getElementById('otpTimerLeft');
    var end = new Date(timer.dataset.expires).getTime();

    function tick() {
        var s = Math.max(0, Math.round((end - Date.now()) / 1000));
        if (s === 0) {
            timer.innerHTML = 'The code has expired. Tap <strong>Resend code</strong> for a new one.';
            timer.classList.add('is-expired');
            return;
        }
        left.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        setTimeout(tick, 1000);
    }

    tick();
    // Coming back from the mail app: show the real time left at once.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && Date.now() < end) {
            var s = Math.round((end - Date.now()) / 1000);
            left.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        }
    });
})();
</script>

@include('partials.pwa-register')

</body>
</html>
