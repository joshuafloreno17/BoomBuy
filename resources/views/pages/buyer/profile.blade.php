<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'My Profile — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/buyer-profile.css') }}">
</head>

<body>

    @include('partials.buyer-navbar', ['activeNav' => 'profile'])

    <div class="container">

        @include('partials.page-head', ['title' => 'My Profile', 'crumbs' => ['My account' => route('buyer.account')]])

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
                        <span class="field-hint">Used at checkout until you save addresses below.</span>
                    </div>

                </div>

                <button type="submit" class="save-btn">
                    Save Changes
                </button>

            </form>

        </div>

        <!-- ADDRESS BOOK -->

        <div class="card" id="addresses">

            <h3>My Addresses</h3>
            <p class="card-sub">Save the places you ship to, then pick one at checkout instead of typing it again.</p>

            @if($addresses->isEmpty())
                <div class="addr-empty">
                    <i class="bi bi-geo-alt"></i>
                    No saved addresses yet. Checkout uses your profile address above until you add one.
                </div>
            @else
                <ul class="addr-list">
                    @foreach($addresses as $addr)
                        <li class="addr-item {{ $addr->is_default ? 'is-default' : '' }}">
                            <div class="addr-text">
                                <div class="addr-top">
                                    <strong>{{ $addr->label ?: 'Address' }}</strong>
                                    @if($addr->is_default)
                                        <span class="addr-badge"><i class="bi bi-check-lg"></i> Default</span>
                                    @endif
                                </div>
                                <span>{{ $addr->address }}</span>
                                <span class="addr-phone"><i class="bi bi-telephone"></i> {{ $addr->phone }}</span>
                            </div>
                            <div class="addr-actions">
                                @unless($addr->is_default)
                                    <form method="POST" action="{{ route('buyer.addresses.default', $addr->id) }}">
                                        @csrf
                                        <button type="submit" class="addr-btn">Set as default</button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('buyer.addresses.delete', $addr->id) }}" data-confirm="Delete this address?" data-confirm-danger data-confirm-ok="Delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="addr-btn is-danger" aria-label="Delete {{ $addr->label ?: 'address' }}"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($addresses->count() < 10)
                <details class="addr-add" @if($errors->has('address') || $errors->has('phone') || $errors->has('label')) open @endif>
                    <summary><i class="bi bi-plus-lg"></i> Add a new address</summary>

                    <form method="POST" action="{{ route('buyer.addresses.store') }}">
                        @csrf
                        <div class="form-grid">
                            <div class="field">
                                <label for="addr_label">Label <span class="field-hint" style="display:inline;">(optional)</span></label>
                                <input type="text" id="addr_label" name="label" maxlength="40" placeholder="Home, Work…" value="{{ old('label') }}">
                            </div>
                            <div class="field">
                                <label for="addr_phone">Phone Number</label>
                                <input type="text" id="addr_phone" name="phone" maxlength="20" placeholder="09XX XXX XXXX" value="{{ old('phone', $dbUser->phone ?? '') }}" required>
                            </div>
                            <div class="field" style="grid-column: 1 / -1;">
                                <label for="addr_address">Complete Address</label>
                                <input type="text" id="addr_address" name="address" maxlength="255" placeholder="House No., Street, Barangay, City, Province" value="{{ old('address') }}" required>
                            </div>
                        </div>
                        <label class="addr-default-check">
                            <input type="checkbox" name="is_default" value="1" @checked($addresses->isEmpty())>
                            Use as my default address
                        </label>
                        <button type="submit" class="save-btn">Save Address</button>
                    </form>
                </details>
            @endif

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

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>
</html>
