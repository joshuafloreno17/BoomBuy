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
            <p class="card-sub">Your name, and the email and mobile number you log in and get updates with.</p>

            @php
                $nameParts = explode(' ', trim($dbUser->name ?? ''), 2);
                $pendingEmail = \App\Http\Controllers\ContactChangeController::pending((int) $dbUser->id, 'email');
                $pendingPhone = \App\Http\Controllers\ContactChangeController::pending((int) $dbUser->id, 'phone');
            @endphp

            <form method="POST" action="{{ route('buyer.profile.update') }}">
                @csrf

                <div class="form-grid">
                    <div class="field">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" autocapitalize="words" minlength="2" maxlength="50" value="{{ old('first_name', $dbUser->first_name ?: ($nameParts[0] ?? '')) }}" required>
                        @error('first_name')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" autocapitalize="words" minlength="2" maxlength="50" value="{{ old('last_name', $dbUser->last_name ?: ($nameParts[1] ?? '')) }}" required>
                        @error('last_name')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <button type="submit" class="save-btn">Save Changes</button>
            </form>

            <div class="form-grid contact-grid">
                {{-- Email: a code to the new address; the old one works until it is entered. --}}
                <div class="field">
                    <label>Email Address</label>
                    <p class="contact-now" data-current-email>{{ $dbUser->email }}</p>
                    <form method="POST" action="{{ route('buyer.email.request') }}" class="contact-form">
                        @csrf
                        <input type="email" name="email" placeholder="New email address" value="{{ old('email') }}" required aria-label="New email address">
                        <button type="submit" class="addr-btn">Send code</button>
                    </form>
                    @error('email')<span class="field-error">{{ $message }}</span>@enderror
                    @if($pendingEmail)
                        <form method="POST" action="{{ route('buyer.contact.verify', 'email') }}" class="contact-form">
                            @csrf
                            <input type="text" name="otp" inputmode="numeric" maxlength="6" placeholder="6-digit code sent to {{ $pendingEmail['value'] }}" required aria-label="Code">
                            <button type="submit" class="addr-btn">Verify</button>
                        </form>
                    @endif
                </div>

                {{-- Mobile number: the code is emailed (no SMS). --}}
                <div class="field">
                    <label>Mobile Number</label>
                    <p class="contact-now" data-current-phone>{{ $dbUser->phone ?: '—' }}</p>
                    <form method="POST" action="{{ route('buyer.phone.request') }}" class="contact-form">
                        @csrf
                        <input type="text" name="phone" placeholder="09XXXXXXXXX" maxlength="13" value="{{ old('phone') }}" required aria-label="New mobile number">
                        <button type="submit" class="addr-btn">Send code</button>
                    </form>
                    @error('phone')<span class="field-error">{{ $message }}</span>@enderror
                    @if($pendingPhone)
                        <form method="POST" action="{{ route('buyer.contact.verify', 'phone') }}" class="contact-form">
                            @csrf
                            <input type="text" name="otp" inputmode="numeric" maxlength="6" placeholder="6-digit code (check your email)" required aria-label="Code">
                            <button type="submit" class="addr-btn">Verify</button>
                        </form>
                    @endif
                </div>
            </div>
            @error('otp')<span class="field-error">{{ $message }}</span>@enderror

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
                                @if($addr->recipient_name)<span class="addr-recipient">{{ $addr->recipient_name }}</span>@endif
                                <span>{{ $addr->address }}</span>
                                <span class="addr-phone"><i class="bi bi-telephone"></i> {{ $addr->phone }}</span>
                            </div>
                            <div class="addr-actions">
                                <a href="{{ route('buyer.addresses.edit', $addr->id) }}" class="addr-btn"
                                   data-viewer data-viewer-kind="inline" data-viewer-target="#addrEditPanel" data-address-id="{{ $addr->id }}"
                                   data-address="{{ json_encode($addr->only(['id', 'label', 'recipient_name', 'phone', 'region', 'province', 'city_municipality', 'barangay', 'street'])) }}">Edit</a>
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
                <details class="addr-add" @if($errors->hasAny(['address', 'phone', 'label', 'recipient_name', 'region', 'province', 'city_municipality', 'barangay', 'street'])) open @endif>
                    <summary><i class="bi bi-plus-lg"></i> Add a new address</summary>

                    <form method="POST" action="{{ route('buyer.addresses.store') }}">
                        @csrf
                        <div class="form-grid">
                            <div class="field">
                                <label for="addr_label">Label <span class="field-hint" style="display:inline;">(optional)</span></label>
                                <input type="text" id="addr_label" name="label" maxlength="40" placeholder="Home, Work…" value="{{ old('_edit_id') ? '' : old('label') }}">
                            </div>
                        </div>
                        @include('partials.address-book-fields', ['address' => null, 'prefix' => 'addr', 'useOld' => !old('_edit_id')])
                        <label class="addr-default-check">
                            <input type="checkbox" name="is_default" value="1" @checked($addresses->isEmpty())>
                            Use as my default address
                        </label>
                        <button type="submit" class="save-btn">Save Address</button>
                    </form>
                </details>
            @endif

            @if($addresses->isNotEmpty())
                @php $editingId = old('_edit_id'); @endphp
                <div id="addrEditPanel" class="addr-edit-panel" hidden>
                    <h3>Edit Address</h3>
                    <p class="card-sub">Orders already placed keep the address they were sent to.</p>

                    <form method="POST" id="addrEditForm"
                          action="{{ $editingId ? route('buyer.addresses.update', $editingId) : '' }}"
                          data-action="{{ route('buyer.addresses.update', 0) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_edit_id" value="{{ $editingId }}">

                        <div class="form-grid">
                            <div class="field">
                                <label for="edit_label">Label <span class="field-hint" style="display:inline;">(optional)</span></label>
                                <input type="text" id="edit_label" name="label" maxlength="40" placeholder="Home, Work…" value="{{ $editingId ? old('label') : '' }}">
                            </div>
                        </div>

                        @include('partials.address-book-fields', ['address' => null, 'prefix' => 'edit', 'bag' => 'addressEdit', 'useOld' => (bool) $editingId])

                        <div class="addr-edit-actions">
                            <button type="submit" class="save-btn">Save Address</button>
                            <button type="button" class="addr-btn" data-viewer-close>Cancel</button>
                        </div>
                    </form>
                </div>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var panel = document.getElementById('addrEditPanel');
            if (!panel) return;

            var form = document.getElementById('addrEditForm');
            var fields = panel.querySelector('[data-address-book]');
            var failedId = form.elements._edit_id.value;
            var keepTyped = !!failedId;

            document.addEventListener('bbviewer:open', function (e) {
                var raw = e.target.getAttribute('data-address');
                if (!raw) return;

                // Just came back with errors: keep what the buyer typed.
                if (keepTyped) {
                    keepTyped = false;
                    return;
                }

                var a = JSON.parse(raw);
                form.action = form.getAttribute('data-action').replace(/\/0$/, '/' + a.id);
                form.elements._edit_id.value = a.id;
                form.elements.label.value = a.label || '';
                form.elements.recipient_name.value = a.recipient_name || '';
                form.elements.phone.value = a.phone || '';
                form.elements.street.value = a.street || '';
                panel.querySelectorAll('.field-error').forEach(function (n) { n.remove(); });
                window.bbAddressBook.fill(fields, a);
            });

            if (failedId) {
                var button = document.querySelector('[data-address-id="' + failedId + '"]');
                if (button && window.bbViewer) window.bbViewer.open(button);
            }
        });
    </script>

    @include('partials.buyer-footer')

    @include('partials.viewer-modal')

    @include('partials.pwa-register')

</body>
</html>
