<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile — BoomBuy Seller</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/seller-profile.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="profile" :user="$user" />

    <main class="main-content">

        <div class="container">

            <x-seller-page-head title="My Profile" subtitle="Manage your shop details, account information and password." />

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
                $displayName = $dbUser->name ?? ($user['name'] ?? 'Seller');
                $initial = strtoupper(substr($displayName, 0, 1));
            @endphp

            <div class="profile-header">

                <form
                    action="{{ route('seller.profile.photo') }}"
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
                            <i class="bi bi-camera-fill"></i>
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
                            Seller since {{ \Illuminate\Support\Carbon::parse($dbUser->created_at)->format('F Y') }}
                        </p>
                    @endif
                    @if($application)
                        @php
                            $statusClass = match($application->status) {
                                'Approved' => 'status-approved',
                                'Rejected' => 'status-rejected',
                                default => 'status-pending',
                            };
                        @endphp
                        <span class="status-pill {{ $statusClass }}">
                            Verification: {{ $application->status }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- STATS -->

            <div class="stats-row">

                <div class="stat-card">
                    <span class="stat-label">Total Products</span>
                    <strong class="stat-value">{{ $totalProducts }}</strong>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Total Sales (Delivered)</span>
                    <strong class="stat-value">₱{{ number_format($totalSales, 2) }}</strong>
                </div>

            </div>

            @if($application)
                <!-- SHOP PROFILE: name + about text shown on the shop page; category stays as registered -->
                <div class="card" id="shop">
                    <h3>Shop Profile</h3>
                    <p class="card-sub">
                        This is what buyers see on your shop page.
                        <a href="{{ route('shop.seller', $user['id']) }}" style="color:#c43408; font-weight:700;">View my shop →</a>
                    </p>

                    <form method="POST" action="{{ route('seller.shop.update') }}">
                        @csrf

                        <div class="form-grid">
                            <div class="field">
                                <label for="business_name">Shop Name</label>
                                <input type="text" id="business_name" name="business_name" minlength="3" maxlength="60" required
                                       value="{{ old('business_name', $application->business_name ?? '') }}">
                            </div>
                            <div class="field">
                                <label>Line of Business</label>
                                <input type="text" value="{{ \App\Support\Categories::LIST[\App\Support\Categories::slug($application->business_category) ?? ''] ?? ($application->business_category ?? '—') }}" disabled>
                                <span class="field-hint">You can only sell in this category. Contact support to change it.</span>
                            </div>
                            <div class="field" style="grid-column: 1 / -1;">
                                <label for="shop_description">About Your Shop <span class="field-hint" style="display:inline;">(optional)</span></label>
                                <textarea id="shop_description" name="shop_description" rows="3" maxlength="500"
                                          placeholder="What do you sell, and why should buyers choose your shop?"
                                          style="width:100%; box-sizing:border-box; padding:12px 14px; border:1px solid #f0d9d1; border-radius:10px; font:inherit; font-size:14px; resize:vertical;">{{ old('shop_description', $application->shop_description ?? '') }}</textarea>
                                <span class="field-hint">Up to 500 characters. Shown at the top of your shop page.</span>
                            </div>
                        </div>

                        <button type="submit" class="save-btn">Save Shop Details</button>
                    </form>
                </div>
            @endif

            <!-- PROFILE INFORMATION -->

            <div class="card">

                <h3>Profile Information</h3>
                <p class="card-sub">Update your name, phone number, and address.</p>

                <form method="POST" action="{{ route('seller.profile.update') }}">
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

                <form method="POST" action="{{ route('seller.profile.password') }}" id="passwordForm">
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

    </main>

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
