<!DOCTYPE html>
<html lang="en">

<head>

    @include('partials.head', ['title' => 'Create New Password — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/reset-password.css') }}">

</head>

<body>

<nav class="navbar">

    <a href="/" class="logo">
        <img src="{{ asset('images/icon.svg') }}" alt="" class="bb-logo-mark" width="32" height="32"><span class="bb-logo-word">BoomBuy</span>
    </a>

</nav>


<div class="wrapper">

    <div class="card">

        <div class="header">

            <div class="icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <small>
                BoomBuy Security
            </small>

            <h1>
                Create New Password
            </h1>

            <p>
                Enter the 6-digit code we emailed you, then choose a new password.
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
                    Verification Code
                </label>

                <input
                    type="text"
                    id="otp_code"
                    name="otp_code"
                    placeholder="6-digit code from your email"
                    maxlength="6"
                    inputmode="numeric"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    New Password
                </label>

                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter new password"
                        minlength="8"
                        required
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        data-target="password"
                        aria-label="Show password"
                    ><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
                </div>

            </div>


            <div class="form-group">

                <label>
                    Confirm New Password
                </label>

                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Confirm new password"
                        required
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        data-target="password_confirmation"
                        aria-label="Show password"
                    ><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
                </div>

                <span class="field-error-msg" id="password-match-msg">
                    Passwords do not match.
                </span>

            </div>


            <button type="submit">

                Change Password

            </button>

        </form>

    </div>

</div>

    <script>
        (function () {
            var EYE_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
            var EYE_OFF_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

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

            if (pwd && confirmPwd) {
                function checkMatch() {
                    if (confirmPwd.value === '') {
                        confirmPwd.classList.remove('input-error');
                        if (msg) msg.style.display = 'none';
                        return;
                    }

                    if (confirmPwd.value !== pwd.value) {
                        confirmPwd.classList.add('input-error');
                        if (msg) msg.style.display = 'block';
                    } else {
                        confirmPwd.classList.remove('input-error');
                        if (msg) msg.style.display = 'none';
                    }
                }

                pwd.addEventListener('input', checkMatch);
                confirmPwd.addEventListener('input', checkMatch);
            }
        })();
    </script>

    @include('partials.pwa-register')

</body>

</html>