@php
    // Signing up for BoomBuy Logistics: show the Logistics tag.
    $isLogistics = session('pending_registration.role') === 'logistics';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isLogistics ? 'Verify Code - BoomBuy Logistics' : 'Verify Code - BoomBuy' }}</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

</head>
<body style="margin:0; padding:0; background:var(--theme-bg); font-family: Arial, sans-serif;">

    <div style="max-width:420px; margin:60px auto; background:#fff; border-radius:16px; border:1px solid var(--theme-border); padding:32px; text-align:center;">

        <div style="font-size:22px; font-weight:800; margin-bottom:16px;">
            @include('partials.auth-logo', ['logistics' => $isLogistics])
        </div>

        <h1 style="font-size:20px; color:var(--theme-ink);">Enter Verification Code</h1>
        <p style="color:var(--theme-muted); font-size:14px;">
            We sent a 6-digit code to <strong>{{ session('otp_email') }}</strong>. It expires in 5 minutes.
        </p>

        @php
            // Counts down from when the code was sent, so it keeps running
            // while the user is away in the mail app.
            $otpExpiresAt = session('otp_expires_at');
        @endphp
        @if ($otpExpiresAt)
            <p id="otpTimer" data-expires="{{ \Illuminate\Support\Carbon::parse($otpExpiresAt)->toIso8601String() }}"
                style="color:var(--theme-muted); font-size:13px; margin:-4px 0 14px;">
                Code expires in <strong id="otpTimerLeft">5:00</strong>
            </p>
        @endif

        @if ($errors->any())
            <div style="background:#fee2e2; color:#dc2626; padding:10px; border-radius:8px; margin-bottom:16px; font-size:13px;">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('status'))
            <div style="background:#e5ffe8; color:#2c8c3c; padding:10px; border-radius:8px; margin-bottom:16px; font-size:13px;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('otp.verify') }}">
            @csrf
            <input type="text" name="otp_code" maxlength="6" placeholder="000000"
                inputmode="numeric" pattern="\d{6}" title="Enter the 6-digit code." autocomplete="one-time-code"
                style="width:100%; box-sizing:border-box; font-size:28px; letter-spacing:10px; text-align:center; padding:14px; border:1px solid var(--theme-border); border-radius:10px; margin-bottom:16px;" required autofocus>

            <button type="submit"
                style="width:100%; background:var(--theme-primary); color:#fff; border:none; padding:14px; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer;">
                Verify
            </button>
        </form>

        <form method="POST" action="{{ route('otp.resend') }}" style="margin-top:16px;">
            @csrf
            <button type="submit" style="background:none; border:none; color:var(--theme-primary); font-size:13px; cursor:pointer; text-decoration:underline;">
                Resend Code
            </button>
        </form>

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
                timer.innerHTML = 'The code has expired. Tap <strong>Resend Code</strong> for a new one.';
                timer.style.color = '#dc2626';
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