<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'My Account — BoomBuy Admin'])

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-settings.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-account.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="account" />

    <main class="main">

    <div class="container account-page">

        <div class="header">
            <h1>My Account</h1>
            <p>Your name, login email and password as the BoomBuy administrator.</p>
        </div>

        @if(session('success'))
            <div class="success-box">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="error-box">{{ session('error') }}</div>
        @endif

        <div class="account-head">
            <span class="account-avatar">{{ strtoupper(mb_substr($admin->name ?: 'A', 0, 1)) }}</span>
            <div>
                <h2>{{ $admin->name }}</h2>
                <p>{{ $admin->email }}</p>
                <small>Administrator · Buyers, sellers and riders see you as “BoomBuy Support”.</small>
            </div>
        </div>

        {{-- Wide screens: the two forms side by side. --}}
        <div class="account-cards">

        <!-- ACCOUNT DETAILS -->

        <div class="card">
            <h2><i class="bi bi-person"></i> Account Details</h2>
            <p class="desc">The email is what you log in with. Changing it asks for your current password.</p>

            <form method="POST" action="{{ route('admin.account.update') }}">
                @csrf

                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" maxlength="100" value="{{ old('name', $admin->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Login Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $admin->email) }}" data-original="{{ $admin->email }}" required>
                    </div>
                </div>

                <div class="form-group" id="emailPasswordField" hidden>
                    <label for="email_current_password">Current Password</label>
                    <input type="password" id="email_current_password" name="current_password" autocomplete="current-password">
                    <span class="field-hint">Needed because you are changing the login email.</span>
                </div>

                <button type="submit" class="save-btn">Save Changes</button>
            </form>
        </div>

        <!-- CHANGE PASSWORD -->

        <div class="card">
            <h2><i class="bi bi-key"></i> Change Password</h2>
            <p class="desc">At least 8 characters.</p>

            <form method="POST" action="{{ route('admin.account.password') }}">
                @csrf

                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password_confirmation">Confirm New Password</label>
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation" autocomplete="new-password" required>
                        <span class="field-error" id="passwordMatchMsg" hidden>Passwords do not match.</span>
                    </div>
                </div>

                <label class="show-passwords">
                    <input type="checkbox" id="showPasswords"> Show passwords
                </label>

                <button type="submit" class="save-btn">Update Password</button>
            </form>
        </div>
        </div>{{-- .account-cards --}}

    </div>

    </main>

</div>

<script>
    (function () {
        // Ask for the password only when the login email is being changed.
        var email = document.getElementById('email');
        var emailPassword = document.getElementById('emailPasswordField');
        function syncEmail() {
            var changed = email.value.trim().toLowerCase() !== email.dataset.original.toLowerCase();
            emailPassword.hidden = !changed;
            emailPassword.querySelector('input').required = changed;
        }
        email.addEventListener('input', syncEmail);
        syncEmail();

        var newPwd = document.getElementById('new_password');
        var confirmPwd = document.getElementById('new_password_confirmation');
        var msg = document.getElementById('passwordMatchMsg');
        function checkMatch() {
            msg.hidden = confirmPwd.value === '' || confirmPwd.value === newPwd.value;
        }
        newPwd.addEventListener('input', checkMatch);
        confirmPwd.addEventListener('input', checkMatch);

        document.getElementById('showPasswords').addEventListener('change', function () {
            var type = this.checked ? 'text' : 'password';
            ['current_password', 'new_password', 'new_password_confirmation'].forEach(function (id) {
                document.getElementById(id).type = type;
            });
        });
    })();
</script>

@include('partials.pwa-register')

</body>
</html>
