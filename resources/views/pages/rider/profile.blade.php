<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Profile — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/rider-profile.css') }}">

</head>

<body>


    {{-- =========================
         SIDEBAR
    ========================= --}}

    <x-layout.rider-sidebar active="profile" :user="$user" />


    {{-- =========================
         MAIN
    ========================= --}}

    <main class="main main-content">

        <div class="content">


            <div class="topbar">

                <div>

                    <div class="page-kicker">
                        RIDER CENTER
                    </div>

                    <h1>
                        My Profile
                    </h1>

                    <p class="subtitle">
                        Manage your rider account and profile.
                    </p>

                </div>


                <div class="profile-pill">

                    <div class="profile-pill-icon">
                        <i class="bi bi-bicycle"></i>
                    </div>

                    <strong>
                        {{ $user['name'] ?? 'Rider' }}
                    </strong>

                </div>

            </div>


            {{-- ALERTS --}}

            @if(session('success'))

                <div class="alert success">
                    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                </div>

            @endif


            @if(session('error'))

                <div class="alert error">
                    <i class="bi bi-x-circle-fill"></i> {{ session('error') }}
                </div>

            @endif


            {{-- PROFILE GRID --}}

            <div class="profile-grid">


                {{-- LEFT PROFILE CARD --}}

                <div>

                    <div class="card profile-card">

                        <div class="avatar-wrapper">

                            <div class="avatar">

                                @if(!empty($user['profile_photo']))

                                    <img
                                        src="{{ asset('storage/profile-photos/' . $user['profile_photo']) }}"
                                        alt="Profile Picture"
                                    >

                                @else

                                    <i class="bi bi-bicycle"></i>

                                @endif

                            </div>


                            <div class="camera">
                                <i class="bi bi-camera-fill"></i>
                            </div>

                        </div>


                        <h2>
                            {{ $user['name'] ?? 'Rider' }}
                        </h2>


                        <div class="role-badge">
                            RIDER
                        </div>


                        <p class="profile-description">
                            BoomBuy Delivery Rider
                        </p>


                        {{-- PHOTO UPLOAD --}}

                        <div class="upload-box">

                            <form
                                action="{{ route('rider.profile.photo') }}"
                                method="POST"
                                enctype="multipart/form-data"
                            >

                                @csrf


                                <input
                                    type="file"
                                    name="profile_photo"
                                    id="profile_photo"
                                    class="upload-input"
                                    accept="image/png,image/jpeg,image/webp"
                                    required
                                    onchange="
                                        document.getElementById('file-chosen-label').textContent =
                                        this.files[0]
                                        ? this.files[0].name
                                        : 'Choose Photo';
                                    "
                                >


                                <label
                                    for="profile_photo"
                                    class="upload-label"
                                    id="file-chosen-label"
                                >
                                    Choose Photo
                                </label>


                                <button
                                    type="submit"
                                    class="upload-submit-btn"
                                >
                                    <i class="bi bi-check-circle-fill"></i> Upload Photo
                                </button>

                            </form>

                        </div>

                    </div>


                    <a
                        href="{{ route('rider.deliveries') }}"
                        class="deliveries-btn"
                    >
                        <i class="bi bi-truck"></i> View My Deliveries
                    </a>

                </div>


                {{-- RIGHT SIDE --}}

                <div>


                    {{-- ACCOUNT INFORMATION --}}

                    <div class="card">

                        <div class="card-title">
                            <i class="bi bi-person-fill"></i> Account Information
                        </div>


                        <div class="info">


                            <div class="info-row">

                                <span class="info-label">
                                    Full Name
                                </span>

                                <span class="info-value">
                                    {{ $user['name'] ?? 'Rider' }}
                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Email Address
                                </span>

                                <span class="info-value">
                                    {{ $user['email'] ?? 'No email available' }}
                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Account Role
                                </span>

                                <span class="info-value">
                                    Rider
                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Rider ID
                                </span>

                                <span class="info-value rider-id">
                                    {{ $user['id'] ?? 'N/A' }}
                                </span>

                            </div>


                        </div>

                    </div>


                    {{-- Delivery statistics come from RiderController::profile(). --}}


                    {{-- STATISTICS --}}

                    <div class="card statistics">

                        <div class="card-title">
                            <i class="bi bi-bar-chart-fill"></i> Delivery Statistics
                        </div>


                        <div class="stats">


                            <div class="stat">

                                <div class="stat-icon">
                                    <i class="bi bi-box-seam-fill"></i>
                                </div>

                                <div class="stat-number">
                                    {{ $totalDeliveries }}
                                </div>

                                <div class="stat-label">
                                    Total Deliveries
                                </div>

                            </div>


                            <div class="stat">

                                <div class="stat-icon">
                                    <i class="bi bi-truck"></i>
                                </div>

                                <div class="stat-number">
                                    {{ $activeCount }}
                                </div>

                                <div class="stat-label">
                                    Active Deliveries
                                </div>

                            </div>


                            <div class="stat">

                                <div class="stat-icon">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>

                                <div class="stat-number">
                                    {{ $deliveredCount }}
                                </div>

                                <div class="stat-label">
                                    Delivered
                                </div>

                            </div>


                        </div>

                    </div>


                    {{-- CHANGE PASSWORD --}}

                    <div class="card password-card">

                        <div class="card-title">
                            <i class="bi bi-shield-lock-fill"></i> Change Password
                        </div>

                        <form method="POST" action="{{ route('rider.profile.password') }}" class="password-form">
                            @csrf

                            <label class="pw-field" for="current_password">
                                <span>Current password</span>
                                <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                            </label>

                            <div class="pw-row">
                                <label class="pw-field" for="new_password">
                                    <span>New password</span>
                                    <input type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password" required>
                                    <small>At least 8 characters.</small>
                                </label>

                                <label class="pw-field" for="new_password_confirmation">
                                    <span>Confirm new password</span>
                                    <input type="password" id="new_password_confirmation" name="new_password_confirmation" minlength="8" autocomplete="new-password" required>
                                </label>
                            </div>

                            <button type="submit" class="upload-submit-btn">
                                <i class="bi bi-check-circle-fill"></i> Update Password
                            </button>
                        </form>

                    </div>

                </div>

            </div>

        </div>

    </main>


    @include('partials.pwa-register')

</body>
</html>