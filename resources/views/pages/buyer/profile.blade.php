<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile — BoomBuy</title>

    @include('partials.pwa-head')

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #fff7f4;
            color: #172033;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: calc(100% - 40px);
            max-width: 760px;

            margin: 45px auto 80px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 30px;
        }

        .page-header p {
            color: #977970;
            font-size: 13px;
            margin-top: 6px;
        }

        .success-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 12px 15px;
            border-radius: 9px;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .error-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 12px 15px;
            border-radius: 9px;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 16px;

            background: white;
            border: 1px solid #f7e5e0;
            border-radius: 16px;

            padding: 24px;

            margin-bottom: 20px;
        }

        .profile-avatar {
            width: 60px;
            height: 60px;

            border-radius: 50%;

            background: #e8420f;
            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 24px;
            font-weight: 800;

            flex-shrink: 0;

            overflow: hidden;
        }

        .avatar-upload {
            position: relative;
            flex-shrink: 0;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-camera {
            position: absolute;
            bottom: -2px;
            right: -2px;

            width: 24px;
            height: 24px;

            background: white;
            border: 2px solid #fff7f4;
            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 11px;
            cursor: pointer;

            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            transition: 0.2s ease;
        }

        .avatar-camera:hover {
            background: #fff1ed;
        }

        .profile-header h2 {
            font-size: 18px;
        }

        .profile-header p {
            color: #8d6c62;
            font-size: 12px;
            margin-top: 3px;
        }

        .member-since {
            color: #b99c93;
            font-size: 11px;
            margin-top: 4px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;

            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            border: 1px solid #f7e5e0;
            border-radius: 14px;

            padding: 18px 20px;
        }

        .stat-label {
            display: block;

            color: #8d6c62;
            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.5px;

            margin-bottom: 8px;
        }

        .stat-value {
            font-family: 'Baloo 2', sans-serif;
            font-size: 24px;
            color: #e8420f;
        }

        @media (max-width: 500px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            border: 1px solid #f7e5e0;
            border-radius: 16px;

            padding: 26px;

            margin-bottom: 20px;
        }

        .card h3 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 17px;
            margin-bottom: 4px;
        }

        .card-sub {
            color: #977970;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .field {
            text-align: left;
            margin-bottom: 16px;
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

        .field input:disabled {
            background: #fbf6f5;
            color: #a0847b;
            cursor: not-allowed;
        }

        .field-hint {
            display: block;

            font-size: 11px;
            color: #b99c93;

            margin-top: 5px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0 16px;
        }

        @media (max-width: 560px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
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

        .save-btn {
            background: #e8420f;
            color: white;

            border: none;
            border-radius: 10px;

            padding: 12px 22px;

            font-size: 13px;
            font-weight: 700;
            font-family: inherit;

            cursor: pointer;

            transition: 0.2s;
        }

        .save-btn:hover {
            background: #c43408;
        }
    </style>
</head>

<body>

    @include('partials.buyer-navbar')

    <div class="container">

        <div class="page-header">
            <h1>My Profile</h1>
            <p>Manage your account information and password.</p>
        </div>

        @if (session('success'))
            <div class="success-box">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="error-box">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-box">{{ $errors->first() }}</div>
        @endif

        @php
            $displayName = $dbUser->name ?? ($user['name'] ?? 'Buyer');
            $initial = strtoupper(substr($displayName, 0, 1));
        @endphp

        <div class="profile-header">

            <form
                action="{{ route('buyer.profile.photo') }}"
                method="POST"
                enctype="multipart/form-data"
                id="photoForm"
            >
                @csrf

                <div class="avatar-upload">

                    <div class="profile-avatar">
                        @if(!empty($dbUser->profile_photo))
                            <img
                                src="{{ asset('storage/profile-photos/' . $dbUser->profile_photo) }}"
                                alt="Profile Picture"
                            >
                        @else
                            {{ $initial }}
                        @endif
                    </div>

                    <label for="profile_photo_input" class="avatar-camera" title="Change photo">
                        📷
                    </label>

                    <input
                        type="file"
                        name="profile_photo"
                        id="profile_photo_input"
                        accept="image/png,image/jpeg,image/webp"
                        style="display:none;"
                        onchange="document.getElementById('photoForm').submit();"
                    >

                </div>

            </form>

            <div>
                <h2>{{ $displayName }}</h2>
                <p>{{ $dbUser->email ?? ($user['email'] ?? '') }}</p>
                @if(!empty($dbUser->created_at))
                    <p class="member-since">
                        Member since {{ \Illuminate\Support\Carbon::parse($dbUser->created_at)->format('F Y') }}
                    </p>
                @endif
            </div>
        </div>

        <!-- STATS -->

        <div class="stats-row">

            <div class="stat-card">
                <span class="stat-label">Total Orders</span>
                <strong class="stat-value">{{ $totalOrders }}</strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">Total Spent</span>
                <strong class="stat-value">₱{{ number_format($totalSpent, 2) }}</strong>
            </div>

        </div>

        <!-- PROFILE INFORMATION -->

        <div class="card">

            <h3>Profile Information</h3>
            <p class="card-sub">Update your name, phone number, and address.</p>

            <form method="POST" action="{{ route('buyer.profile.update') }}">
                @csrf

                <div class="form-grid">

                    <div class="field">
                        <label for="name">Full Name</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $dbUser->name ?? '') }}"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="email">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            value="{{ $dbUser->email ?? '' }}"
                            disabled
                        >
                        <span class="field-hint">Email address cannot be changed.</span>
                    </div>

                    <div class="field">
                        <label for="phone">Phone Number</label>
                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            value="{{ old('phone', $dbUser->phone ?? '') }}"
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
                            value="{{ old('address', $dbUser->address ?? '') }}"
                            required
                        >
                        <span class="field-hint">This address will be used as your default shipping address.</span>
                    </div>

                </div>

                <button type="submit" class="save-btn">
                    Save Changes
                </button>

            </form>

        </div>

        <!-- CHANGE PASSWORD -->

        <div class="card">

            <h3>Change Password</h3>
            <p class="card-sub">Update your account password.</p>

            <form method="POST" action="{{ route('buyer.profile.password') }}" id="passwordForm">
                @csrf

                <div class="field">
                    <label for="current_password">Current Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            required
                        >
                        <button
                            type="button"
                            class="password-toggle"
                            data-target="current_password"
                            aria-label="Show password"
                        ><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
                    </div>
                </div>

                <div class="form-grid">

                    <div class="field">
                        <label for="new_password">New Password</label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                minlength="8"
                                required
                            >
                            <button
                                type="button"
                                class="password-toggle"
                                data-target="new_password"
                                aria-label="Show password"
                            ><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
                        </div>
                        <span class="field-hint">At least 8 characters.</span>
                    </div>

                    <div class="field">
                        <label for="new_password_confirmation">Confirm New Password</label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="new_password_confirmation"
                                name="new_password_confirmation"
                                required
                            >
                            <button
                                type="button"
                                class="password-toggle"
                                data-target="new_password_confirmation"
                                aria-label="Show password"
                            ><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
                        </div>
                        <span class="field-error-msg" id="password-match-msg">
                            Passwords do not match.
                        </span>
                    </div>

                </div>

                <button type="submit" class="save-btn">
                    Update Password
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

            var newPwd = document.getElementById('new_password');
            var confirmPwd = document.getElementById('new_password_confirmation');
            var msg = document.getElementById('password-match-msg');

            if (newPwd && confirmPwd) {
                function checkMatch() {
                    if (confirmPwd.value === '') {
                        confirmPwd.classList.remove('input-error');
                        if (msg) msg.style.display = 'none';
                        return;
                    }

                    if (confirmPwd.value !== newPwd.value) {
                        confirmPwd.classList.add('input-error');
                        if (msg) msg.style.display = 'block';
                    } else {
                        confirmPwd.classList.remove('input-error');
                        if (msg) msg.style.display = 'none';
                    }
                }

                newPwd.addEventListener('input', checkMatch);
                confirmPwd.addEventListener('input', checkMatch);
            }
        })();
    </script>

    @include('partials.pwa-register')

</body>
</html>
