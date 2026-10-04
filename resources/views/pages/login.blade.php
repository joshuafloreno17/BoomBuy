<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'Login — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/login.css') }}">
</head>

<body>

    <nav class="navbar">

        <a href="/" class="logo">
            <img src="{{ asset('images/icon.svg') }}" alt="" class="bb-logo-mark" width="32" height="32"><span class="bb-logo-word">BoomBuy</span>
        </a>

    </nav>


    <div class="login-wrapper">

        <div>

            <div class="login-card">

                <div class="login-header">

                    <div class="login-icon">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>

                    <small>
                        Welcome Back
                    </small>

                    <h1>
                        Login to BoomBuy
                    </h1>

                    <p>
                        Sign in to your account and
                        continue using BoomBuy.
                    </p>

                </div>


                {{-- ERROR --}}

                @if(session('error'))

                    <div class="error-box">
                        {{ session('error') }}
                    </div>

                @endif


                {{-- SUCCESS --}}

                @if(session('success'))

                    <div class="success-box">
                        {{ session('success') }}
                    </div>

                @endif


                {{-- VALIDATION ERRORS --}}

                @if($errors->any())

                    <div class="error-box">
                        {{ $errors->first() }}
                    </div>

                @endif


                <form
                    action="{{ route('login.submit') }}"
                    method="POST"
                >

                    @csrf


                    {{-- EMAIL --}}

                    <div class="form-group">

                        <label>
                            Email Address
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Enter your email"
                            autocomplete="email"
                            autofocus
                            required
                        >

                    </div>


                    {{-- PASSWORD --}}

                    <div class="form-group">

                        <div class="password-row">

                            <label>
                                Password
                            </label>

                            <a
                                href="{{ route('password.request') }}"
                                class="forgot"
                            >
                                Forgot password?
                            </a>

                        </div>


                        <div class="password-input-wrapper">

                            <input
                                type="password"
                                name="password"
                                id="login-password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="show-password-btn"
                                onclick="togglePassword('login-password', this)"
                                aria-label="Show password"
                            ><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>

                        </div>

                        <label class="remember-row">
                            <input type="checkbox" name="remember">
                            <span>Remember me for 30 days</span>
                        </label>

                    </div>


                    {{-- LOGIN BUTTON --}}

                    <button
                        type="submit"
                        class="login-btn"
                    >
                        Login
                    </button>


                    {{-- TERMS --}}

                    <div class="terms-login">

                        By continuing to use BoomBuy, you acknowledge that you have
                        read our

                        <a
                            href="#"
                            onclick="openTerms(event)"
                        >
                            Terms & Conditions
                        </a>

                        and

                        <a
                            href="#"
                            onclick="openPrivacy(event)"
                        >
                            Privacy Policy
                        </a>.

                    </div>


                </form>


                <div class="register-text">

                    Don't have an account?

                    <a href="{{ route('register') }}">
                        Create one
                    </a>

                </div>


            </div>


            <div class="footer-text">

                © 2026 BoomBuy ·
                Shop smarter. Live better.

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- TERMS & CONDITIONS MODAL --}}
    {{-- ===================================================== --}}

    <div
        class="terms-modal"
        id="termsModal"
        onclick="closeTermsOutside(event)"
    >

        <div class="terms-box">

            <div class="terms-header">

                <div>

                    <h2>
                        BoomBuy Terms & Conditions
                    </h2>

                    <p>
                        Please review the terms for using BoomBuy.
                    </p>

                </div>


                <button
                    type="button"
                    class="close-terms"
                    onclick="closeTerms()"
                    aria-label="Close"
                >
                    ×
                </button>

            </div>


            <div class="terms-content">
                @include('partials.policies.body', ['key' => 'terms_policy', 'default' => 'partials.policies.terms-default'])
            </div>


            <div class="terms-footer">

                <button
                    type="button"
                    class="terms-close-btn"
                    onclick="closeTerms()"
                >
                    Close
                </button>

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- PRIVACY POLICY MODAL --}}
    {{-- ===================================================== --}}

    <div
        class="terms-modal"
        id="privacyModal"
        onclick="closePrivacyOutside(event)"
    >

        <div class="terms-box">

            <div class="terms-header">

                <div>

                    <h2>
                        BoomBuy Privacy Policy
                    </h2>

                    <p>
                        How BoomBuy handles user information.
                    </p>

                </div>


                <button
                    type="button"
                    class="close-terms"
                    onclick="closePrivacy()"
                    aria-label="Close"
                >
                    ×
                </button>

            </div>


            <div class="terms-content">
                @include('partials.policies.body', ['key' => 'privacy_policy', 'default' => 'partials.policies.privacy-default'])
            </div>


            <div class="terms-footer">

                <button
                    type="button"
                    class="terms-close-btn"
                    onclick="closePrivacy()"
                >
                    Close
                </button>

            </div>

        </div>

    </div>


    <script>

        /* =========================
           PASSWORD SHOW / HIDE
        ========================= */

        const EYE_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        const EYE_OFF_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

        function togglePassword(inputId, button) {

            const input = document.getElementById(inputId);

            if (input.type === 'password') {

                input.type = 'text';
                button.innerHTML = EYE_ICON;
                button.setAttribute('aria-label', 'Hide password');

            } else {

                input.type = 'password';
                button.innerHTML = EYE_OFF_ICON;
                button.setAttribute('aria-label', 'Show password');

            }

        }


        /* =========================
           TERMS MODAL
        ========================= */

        function openTerms(event) {

            event.preventDefault();

            document.getElementById('privacyModal')
                .classList.remove('show');

            document.getElementById('termsModal')
                .classList.add('show');

            document.body.style.overflow = 'hidden';

        }


        function closeTerms() {

            document.getElementById('termsModal')
                .classList.remove('show');

            document.body.style.overflow = '';

        }


        function closeTermsOutside(event) {

            if (
                event.target ===
                document.getElementById('termsModal')
            ) {

                closeTerms();

            }

        }


        /* =========================
           PRIVACY MODAL
        ========================= */

        function openPrivacy(event) {

            event.preventDefault();

            document.getElementById('termsModal')
                .classList.remove('show');

            document.getElementById('privacyModal')
                .classList.add('show');

            document.body.style.overflow = 'hidden';

        }


        function closePrivacy() {

            document.getElementById('privacyModal')
                .classList.remove('show');

            document.body.style.overflow = '';

        }


        function closePrivacyOutside(event) {

            if (
                event.target ===
                document.getElementById('privacyModal')
            ) {

                closePrivacy();

            }

        }


        /* =========================
           ESC KEY
        ========================= */

        document.addEventListener('keydown', function(event) {

            if (event.key === 'Escape') {

                closeTerms();
                closePrivacy();

            }

        });

    </script>

    @include('partials.pwa-register')

</body>

</html>