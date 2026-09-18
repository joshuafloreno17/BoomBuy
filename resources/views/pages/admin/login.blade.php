<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login — BoomBuy</title>

    @include('partials.pwa-head')

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: #fff2ee;
            color: #172033;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
        }

        .admin-card {
            width: 100%;
            max-width: 430px;
            background: #ffffff;
            border: 1px solid #fbe2db;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(39, 84, 150, 0.12);
        }

        .admin-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .admin-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 18px;
            border-radius: 18px;
            background: linear-gradient(145deg, #ffede8, #ffdfd5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .admin-header small {
            color: #e8420f;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 10px;
            font-weight: 700;
        }

        .admin-header h1 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 29px;
            margin-top: 9px;
            margin-bottom: 9px;
        }

        .admin-header p {
            color: #977970;
            font-size: 13px;
            line-height: 1.6;
        }

        .admin-notice {
            background: #fff4f1;
            border: 1px solid #ffe4dc;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 22px;
            color: #db5a33;
            font-size: 11px;
            line-height: 1.5;
        }

        .error-message {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 12px 14px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 12px;
        }

        .success-message {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 12px 14px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 12px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            color: #563a32;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #fbe2db;
            border-radius: 9px;
            background: #fffaf8;
            outline: none;
            font-size: 13px;
            color: #172033;
            transition: 0.2s;
        }

        .input-wrapper input[type="password"] {
            padding-right: 42px;
        }

        .input-wrapper input[type="text"] {
            padding-right: 42px;
        }

        .form-group input:focus {
            border-color: #ff7044;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(255, 112, 68, 0.08);
        }

        .show-password {
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

        .show-password:hover {
            opacity: 1;
        }

        .forgot-row {
            display: flex;
            justify-content: flex-end;
            margin-top: -7px;
            margin-bottom: 20px;
        }

        .forgot-password {
            color: #e8420f;
            font-size: 11px;
            font-weight: 700;
            transition: 0.2s;
        }

        .forgot-password:hover {
            color: #c43408;
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            border: none;
            background: #e8420f;
            color: #ffffff;
            padding: 14px;
            border-radius: 9px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
        }

        .login-btn:hover {
            background: #c43408;
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(232, 66, 15, 0.18);
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 22px;
            color: #8d6c62;
            font-size: 12px;
        }

        .back:hover {
            color: #e8420f;
        }

        .footer {
            text-align: center;
            color: #b99c93;
            font-size: 11px;
            margin-top: 25px;
        }

        .footer strong {
            color: #e8420f;
        }

        /* =========================
           MODAL
        ========================= */

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(23, 32, 51, 0.45);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.show {
            display: flex;
        }

        .modal-card {
            width: 100%;
            max-width: 480px;
            max-height: 85vh;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.18);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 22px;
            border-bottom: 1px solid #f5e5df;
        }

        .modal-header h2 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 21px;
            color: #172033;
        }

        .close-modal {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 50%;
            background: #fff1ed;
            color: #e8420f;
            font-size: 18px;
            cursor: pointer;
        }

        .modal-body {
            padding: 22px;
            overflow-y: auto;
            max-height: 60vh;
        }

        .modal-body h3 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 16px;
            margin-bottom: 7px;
            color: #563a32;
        }

        .modal-body p {
            color: #6f5b55;
            font-size: 12px;
            line-height: 1.7;
            margin-bottom: 18px;
        }

        .modal-footer {
            padding: 16px 22px;
            border-top: 1px solid #f5e5df;
            text-align: right;
        }

        .modal-ok {
            border: none;
            background: #e8420f;
            color: white;
            padding: 10px 18px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        @media (max-width: 500px) {
            .admin-card {
                padding: 28px 22px;
            }

            .admin-header h1 {
                font-size: 25px;
            }
        }

        /* ===== BoomBuy Design System ===== */

        h1,
        h2,
        h3,
        .logo {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
        }

        button {
            border-radius: 12px;
            transition: transform 0.15s ease,
                        box-shadow 0.15s ease,
                        background 0.15s ease;
        }

        ::selection {
            background: #ffd7c2;
            color: #7c1a00;
        }
    </style>
</head>

<body>

<div class="page">

    <div>

        <div class="admin-card">

            <!-- HEADER -->

            <div class="admin-header">

                <div class="admin-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>

                <small>BoomBuy Management</small>

                <h1>
                    Admin Login
                </h1>

                <p>
                    Sign in to access the BoomBuy
                    administration dashboard.
                </p>

            </div>


            <!-- NOTICE -->

            <div class="admin-notice">

                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                This area is restricted to authorized
                BoomBuy administrators only.

            </div>


            <!-- ERROR -->

            @if(session('error'))

                <div class="error-message">

                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    {{ session('error') }}

                </div>

            @endif


            <!-- SUCCESS -->

            @if(session('success'))

                <div class="success-message">

                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><polyline points="16 9 11 14 8 11"/></svg>
                    {{ session('success') }}

                </div>

            @endif


            <!-- LOGIN FORM -->

            <form
                action="{{ route('admin.login.submit') }}"
                method="POST"
            >

                @csrf


                <!-- EMAIL -->

                <div class="form-group">

                    <label>
                        Admin Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter admin email"
                        required
                    >

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label>
                        Password
                    </label>

                    <div class="input-wrapper">

                        <input
                            id="adminPassword"
                            type="password"
                            name="password"
                            placeholder="Enter admin password"
                            required
                        >

                        <button
                            type="button"
                            class="show-password"
                            onclick="toggleAdminPassword()"
                            id="passwordToggle"
                            aria-label="Show password"
                        ><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>

                    </div>

                </div>


                <!-- FORGOT PASSWORD -->

                <div class="forgot-row">

                    <a
                        href="#"
                        class="forgot-password"
                        onclick="openForgotPassword(event)"
                    >
                        Forgot Password?
                    </a>

                </div>


                <!-- LOGIN BUTTON -->

                <button
                    type="submit"
                    class="login-btn"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Login to Dashboard
                </button>

            </form>


            <!-- BACK -->

            <a
                href="/login"
                class="back"
            >
                ← Back to Customer Login
            </a>

        </div>


        <!-- FOOTER -->

        <div class="footer">

            © 2026 <strong>BoomBuy</strong> ·
            Admin Portal

        </div>

    </div>

</div>


<!-- =========================
     FORGOT PASSWORD MODAL
========================= -->

<div
    id="forgotPasswordModal"
    class="modal"
    onclick="closeForgotPasswordOutside(event)"
>

    <div class="modal-card">

        <div class="modal-header">

            <h2>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Forgot Password?
            </h2>

            <button
                type="button"
                class="close-modal"
                onclick="closeForgotPassword()"
            >
                ×
            </button>

        </div>


        <div class="modal-body">

            <h3>
                Admin Password Recovery
            </h3>

            <p>
                If you forgot your BoomBuy administrator
                password, please contact the system
                administrator or project owner to reset
                your account credentials.
            </p>

            <h3>
                Security Notice
            </h3>

            <p>
                For security purposes, administrator
                passwords cannot be reset directly from
                this login page.
            </p>

            <h3>
                Need Help?
            </h3>

            <p>
                Make sure you are using the correct admin
                email and password before contacting the
                system administrator.
            </p>

        </div>


        <div class="modal-footer">

            <button
                type="button"
                class="modal-ok"
                onclick="closeForgotPassword()"
            >
                Got it
            </button>

        </div>

    </div>

</div>


<script>

    function toggleAdminPassword() {

        const passwordInput =
            document.getElementById('adminPassword');

        const toggleButton =
            document.getElementById('passwordToggle');

        const EYE_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        const EYE_OFF_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

        if (passwordInput.type === 'password') {

            passwordInput.type = 'text';

            toggleButton.innerHTML = EYE_ICON;
            toggleButton.setAttribute('aria-label', 'Hide password');

        } else {

            passwordInput.type = 'password';

            toggleButton.innerHTML = EYE_OFF_ICON;
            toggleButton.setAttribute('aria-label', 'Show password');

        }

    }


    function openForgotPassword(event) {

        event.preventDefault();

        document
            .getElementById('forgotPasswordModal')
            .classList.add('show');

        document.body.style.overflow = 'hidden';

    }


    function closeForgotPassword() {

        document
            .getElementById('forgotPasswordModal')
            .classList.remove('show');

        document.body.style.overflow = '';

    }


    function closeForgotPasswordOutside(event) {

        if (
            event.target ===
            document.getElementById('forgotPasswordModal')
        ) {

            closeForgotPassword();

        }

    }


    document.addEventListener('keydown', function(event) {

        if (event.key === 'Escape') {

            closeForgotPassword();

        }

    });

</script>

    @include('partials.pwa-register')

</body>

</html>