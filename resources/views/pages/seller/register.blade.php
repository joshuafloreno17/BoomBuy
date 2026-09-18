<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Registration — BoomBuy</title>

    @include('partials.pwa-head')

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #fff7f4;
            color: #172033;

            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #ffe9e2;
            padding: 18px 7%;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-family: 'Baloo 2', sans-serif;
            font-size: 23px;
            font-weight: 800;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .back-btn {
            color: #8d6c62;
            background: #fff7f5;
            border: 1px solid #f5ddd6;
            padding: 9px 14px;
            border-radius: 9px;

            font-size: 12px;
            font-weight: 700;

            transition: 0.2s ease;
        }

        .back-btn:hover {
            color: #e8420f;
            border-color: #efb8a7;
            background: #fff1ed;
        }

        .wrapper {
            min-height: calc(100vh - 68px);

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 24px 20px;
        }

        .card {
            width: 100%;
            max-width: 820px;

            background: white;

            border: 1px solid #f7e5e0;
            border-radius: 18px;

            padding: 30px 40px;

            text-align: center;
        }

        .icon {
            width: 52px;
            height: 52px;

            margin: 0 auto 12px;

            background: #ffefea;

            border-radius: 14px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 24px;
        }

        .eyebrow {
            color: #db5a33;

            text-transform: uppercase;
            letter-spacing: 2px;

            font-size: 11px;
            font-weight: 700;
        }

        h1 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 24px;

            margin-top: 6px;
        }

        .subtitle {
            color: #977970;
            font-size: 13px;

            margin-top: 6px;
            margin-bottom: 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            column-gap: 20px;
        }

        .form-grid .section-label,
        .form-grid .submit-btn {
            grid-column: 1 / -1;
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 500px) {
            .navbar {
                padding: 16px 5%;
            }
        }

        .error-box {
            background: #fef2f2;
            color: #dc2626;

            border-radius: 10px;

            padding: 12px 16px;

            font-size: 13px;
            text-align: left;

            margin-bottom: 18px;
        }

        .error-box ul {
            margin-left: 18px;
        }

        .field {
            text-align: left;
            margin-bottom: 12px;
        }

        .field label {
            display: block;

            font-size: 13px;
            font-weight: 700;

            margin-bottom: 6px;
        }

        .field input {
            width: 100%;

            padding: 12px 14px;

            border: 1px solid #f0ddd6;
            border-radius: 10px;

            font-size: 14px;
            font-family: inherit;

            outline: none;

            transition: 0.2s;
        }

        .field input:focus {
            border-color: #e8420f;
        }

        .submit-btn {
            width: 100%;

            background: #e8420f;
            color: white;

            border: none;
            border-radius: 10px;

            padding: 13px;

            font-size: 14px;
            font-weight: 700;
            font-family: inherit;

            cursor: pointer;

            margin-top: 8px;

            transition: 0.2s;
        }

        .submit-btn:hover {
            background: #c43408;
        }

        .footer-text {
            margin-top: 20px;

            font-size: 13px;

            color: #977970;
        }

        .footer-text a {
            color: #e8420f;
            font-weight: 700;
        }

        .section-label {
            text-align: left;

            font-size: 12px;
            font-weight: 800;
            color: #33241f;

            text-transform: uppercase;
            letter-spacing: 1px;

            margin: 10px 0 2px;

            padding-top: 12px;
            border-top: 1px solid #f4e2dc;
        }

        .section-label p {
            font-size: 12px;
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0;
            color: #977970;

            margin-top: 4px;
            margin-bottom: 8px;
        }

        .field input[type="file"] {
            padding: 10px 14px;

            font-size: 12px;
            color: #6a4e46;

            background: #fff6f3;
        }

        .field-hint {
            display: block;

            font-size: 11px;
            color: #b99c93;

            margin-top: 5px;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 42px;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);

            border: none;
            background: transparent;

            display: flex;
            align-items: center;
            justify-content: center;
            color: #6a4e46;

            cursor: pointer;
            padding: 5px;

            opacity: 0.7;
            transition: opacity 0.15s ease;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        .field input.input-error {
            border-color: #dc2626 !important;
            background: #fef2f2 !important;
        }

        .field-error-msg {
            display: none;

            color: #dc2626;
            font-size: 11px;

            margin-top: 6px;
        }

        .file-name {
            display: block;

            font-size: 11px;
            font-weight: 700;
            color: #15803d;

            margin-top: 5px;
        }

        .terms-check {
            grid-column: 1 / -1;

            display: flex;
            align-items: flex-start;
            gap: 8px;

            text-align: left;
            margin-bottom: 6px;

            font-size: 12px;
            color: #563a32;
            line-height: 1.5;
        }

        .terms-check input[type="checkbox"] {
            margin-top: 3px;
            width: auto;
            accent-color: #e8420f;
        }

        .terms-check a {
            color: #e8420f;
            font-weight: 700;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="{{ route('home') }}" class="logo">Boom<span>Buy</span></a>
        <a href="{{ route('register') }}" class="back-btn">← Back</a>
    </nav>

    <div class="wrapper">

    <div class="card">

        <div class="icon">🏬</div>

        <div class="eyebrow">Sell on BoomBuy</div>
        <h1>Create your seller account</h1>
        <p class="subtitle">Sign up to open your own store on BoomBuy.</p>

        @if ($errors->any())
            <div class="error-box">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="error-box">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('seller.register') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-grid">

                <div class="field">
                    <label for="name">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                    >
                </div>

                <div class="field">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                    >
                </div>

                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="{{ old('phone') }}"
                        placeholder="09XX XXX XXXX"
                        required
                    >
                </div>

                <div class="field">
                    <label for="address">Address</label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        value="{{ old('address') }}"
                        required
                    >
                </div>

                <div class="section-label">
                    Seller Verification
                    <p>We need to confirm you're a real seller before your store goes live.</p>
                </div>

                <div class="field">
                    <label for="national_id">Valid Government ID</label>
                    <input
                        type="file"
                        id="national_id"
                        name="national_id"
                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                        onchange="showFileName(this, 'national_id-name')"
                        required
                    >
                    <span class="field-hint">Any government-issued ID (UMID, driver's license, passport, etc.)</span>
                    <span class="file-name" id="national_id-name"></span>
                </div>

                <div class="field">
                    <label for="business_permit">Proof of Business</label>
                    <input
                        type="file"
                        id="business_permit"
                        name="business_permit"
                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                        onchange="showFileName(this, 'business_permit-name')"
                        required
                    >
                    <span class="field-hint">DTI registration, business permit, or any proof that you sell these goods</span>
                    <span class="file-name" id="business_permit-name"></span>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
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
                    <span class="field-hint">At least 8 characters.</span>
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirm Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
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

                <label class="terms-check">
                    <input type="checkbox" name="terms" required>
                    <span>
                        I agree to the
                        <a href="#" onclick="openTerms(event)">Terms &amp; Conditions</a>
                        and
                        <a href="#" onclick="openPrivacy(event)">Privacy Policy</a>.
                    </span>
                </label>

                <button type="submit" class="submit-btn">
                    Create Seller Account
                </button>

            </div>

        </form>

        <div class="footer-text">
            Already have an account?
            <a href="{{ route('login') }}">Login</a>
        </div>

    </div>

    </div>

    @include('partials.terms-modal')

    <script>
        function showFileName(input, targetId) {
            var target = document.getElementById(targetId);
            if (!target) return;

            target.textContent = input.files && input.files[0]
                ? '✓ ' + input.files[0].name
                : '';
        }

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

            var form = document.querySelector('form');

            if (form) {
                form.addEventListener('submit', function () {
                    form.querySelectorAll('[required]').forEach(function (field) {
                        if (!field.checkValidity()) {
                            field.classList.add('input-error');
                        }
                    });
                });

                form.querySelectorAll('[required]').forEach(function (field) {
                    ['input', 'change'].forEach(function (evt) {
                        field.addEventListener(evt, function () {
                            if (field.checkValidity()) {
                                field.classList.remove('input-error');
                            }
                        });
                    });
                });
            }
        })();
    </script>

    @include('partials.pwa-register')

</body>
</html>
