@php
    // Opened from the BoomBuy Logistics login (see AuthController::showForgotPassword).
    $isLogistics = session('auth_portal') === 'logistics';

    $eyeOff = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>

    @include('partials.head', ['title' => $isLogistics ? 'Create New Password — BoomBuy Logistics' : 'Create New Password — BoomBuy', 'fonts' => true])

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
        'headline' => ['Choose a new', 'password.'],
        'blurb' => 'Use the code from your email, then pick something only you know.',
        'steps' => [['Your email', 'mail'], ['New password', 'key']],
    ])

    <main class="reg-main">
    <div class="reg-inner otp-card">

        <div class="reg-head">
            <div class="reg-card-top">
                <span class="reg-kicker">{{ $isLogistics ? 'BoomBuy Logistics Security' : 'BoomBuy Security' }}</span>
            </div>
            <h2>Create new password</h2>
            <p class="otp-sub">Enter the 6-digit code we emailed you, then choose a new password.</p>
        </div>

        @if(session('error'))
            <div class="otp-alert is-error" role="alert">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="otp-alert is-ok" role="status">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="otp-form">

            @csrf

            <label for="otp_code" class="otp-label">Verification code</label>
            <input
                type="text"
                id="otp_code"
                name="otp_code"
                placeholder="000000"
                maxlength="6"
                inputmode="numeric"
                autocomplete="one-time-code"
                class="otp-input"
                required
                autofocus
            >

            <label for="password" class="otp-label">New password</label>
            <div class="auth-password">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Min. 8 · A-Z, a-z, 0-9 and a symbol"
                    autocomplete="new-password"
                    minlength="8"
                    class="auth-input"
                    required
                >
                <button
                    type="button"
                    class="password-toggle"
                    data-target="password"
                    aria-label="Show password"
                >{!! $eyeOff !!}</button>
            </div>

            <label for="password_confirmation" class="otp-label">Confirm new password</label>
            <div class="auth-password">
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    placeholder="Type the same password again"
                    autocomplete="new-password"
                    class="auth-input"
                    required
                >
                <button
                    type="button"
                    class="password-toggle"
                    data-target="password_confirmation"
                    aria-label="Show password"
                >{!! $eyeOff !!}</button>
            </div>

            <span class="field-error-msg" id="password-match-msg">
                Passwords do not match.
            </span>

            <button type="submit" class="otp-btn">Change password</button>

        </form>

        <div class="otp-foot">
            <a href="{{ route($isLogistics ? 'logistics.login' : 'login') }}">← Back to login</a>
            <a href="{{ route('password.request', $isLogistics ? ['portal' => 'logistics'] : []) }}">Send a new code</a>
        </div>

    </div>
    </main>

</div>

<script>
    window.bbRegSide.show(2);

    (function () {
        var EYE_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        var EYE_OFF_ICON = @json($eyeOff);

        document.querySelectorAll('.password-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.dataset.target);
                if (!input) return;

                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.innerHTML = show ? EYE_ICON : EYE_OFF_ICON;
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });

        var pwd = document.getElementById('password');
        var confirmPwd = document.getElementById('password_confirmation');
        var msg = document.getElementById('password-match-msg');

        function checkMatch() {
            var typed = confirmPwd.value !== '';
            var differ = typed && confirmPwd.value !== pwd.value;
            confirmPwd.classList.toggle('input-error', differ);
            msg.style.display = differ ? 'block' : 'none';
        }

        pwd.addEventListener('input', checkMatch);
        confirmPwd.addEventListener('input', checkMatch);
    })();
</script>

@include('partials.pwa-register')

</body>

</html>
