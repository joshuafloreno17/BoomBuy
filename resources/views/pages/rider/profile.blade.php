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

    <style>

        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #fff8f6;
            color: #14213d;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 245px;
            height: 100vh;
            background: #ffffff;
            border-right: 1px solid #f2e3df;
            padding: 30px 16px 20px;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 0 10px;
            margin-bottom: 28px;
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            border-radius: 12px;
            background: #fff0eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .brand-text {
            line-height: 1.05;
        }

        .brand-name {
            font-family: 'Baloo 2', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: #14213d;
        }

        .brand-subtitle {
            font-size: 10px;
            color: #9b817a;
            margin-top: 3px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 11px;
            text-decoration: none;
            color: #5f514d;
            font-size: 13px;
            font-weight: 600;
            transition: .18s ease;
        }

        .menu a:hover {
            background: #fff1ec;
            color: #e8420f;
            transform: translateX(2px);
        }

        .menu a.active {
            background: #e8420f;
            color: white;
            box-shadow: 0 8px 18px rgba(232, 66, 15, .16);
        }

        .menu-icon {
            width: 21px;
            text-align: center;
            font-size: 16px;
        }

        .notification-link {
            justify-content: flex-start;
        }

        .notification-badge {
            margin-left: auto;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 20px;
            background: #e8420f;
            color: white;
            font-size: 10px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu a.active .notification-badge {
            background: white;
            color: #e8420f;
        }

        .logout-area {
            margin-top: auto;
            padding-top: 18px;
            border-top: 1px solid #f1dfda;
        }

        .logout-btn {
            width: 100%;
            border: none;
            background: transparent;
            color: #e8420f;
            text-align: left;
            padding: 12px 14px;
            border-radius: 11px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: .18s ease;
        }

        .logout-btn:hover {
            background: #fff1ec;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 245px;
            min-height: 100vh;
            padding: 44px 34px;
        }

        .content {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* =========================
           TOPBAR
        ========================= */

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .page-kicker {
            color: #e8420f;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .topbar h1 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 34px;
            line-height: 1;
            color: #14213d;
            font-weight: 800;
        }

        .subtitle {
            color: #9b817a;
            font-size: 13px;
            margin-top: 8px;
        }

        .profile-pill {
            background: white;
            border: 1px solid #f1e2de;
            padding: 10px 15px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 13px;
            box-shadow: 0 5px 18px rgba(60, 30, 20, .04);
        }

        .profile-pill-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: #fff0eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* =========================
           ALERT
        ========================= */

        .alert {
            padding: 13px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .success {
            background: #eafaf0;
            color: #15803d;
            border: 1px solid #d3f1dd;
        }

        .error {
            background: #fff0ef;
            color: #c62828;
            border: 1px solid #f7d4d1;
        }

        /* =========================
           PROFILE GRID
        ========================= */

        .profile-grid {
            display: grid;
            grid-template-columns: 330px minmax(0, 1fr);
            gap: 18px;
        }

        .card {
            background: white;
            border: 1px solid #f1e2de;
            border-radius: 17px;
            padding: 25px;
            box-shadow: 0 5px 18px rgba(60, 30, 20, .035);
        }

        /* =========================
           PROFILE CARD
        ========================= */

        .profile-card {
            text-align: center;
        }

        .avatar-wrapper {
            position: relative;
            width: 122px;
            height: 122px;
            margin: 0 auto 18px;
        }

        .avatar {
            width: 122px;
            height: 122px;
            border-radius: 50%;
            overflow: hidden;
            background: #fff0eb;
            border: 5px solid #ffe1d7;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 53px;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .camera {
            position: absolute;
            right: 0;
            bottom: 3px;
            width: 37px;
            height: 37px;
            border-radius: 50%;
            background: #e8420f;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid white;
            font-size: 16px;
        }

        .profile-card h2 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 26px;
            line-height: 1;
            color: #14213d;
            margin-bottom: 9px;
        }

        .role-badge {
            display: inline-block;
            background: #fff0eb;
            color: #e8420f;
            padding: 6px 13px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .7px;
        }

        .profile-description {
            color: #9b817a;
            font-size: 12px;
            margin-top: 12px;
        }

        /* =========================
           UPLOAD
        ========================= */

        .upload-box {
            margin-top: 22px;
            padding-top: 20px;
            border-top: 1px solid #f1e2de;
        }

        .upload-label {
            display: block;
            background: #fff0eb;
            color: #e8420f;
            border: 1px solid #ffd7ca;
            padding: 11px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 800;
            transition: .18s ease;
            text-align: center;
            margin-bottom: 9px;
        }

        .upload-label:hover {
            background: #ffe3db;
        }

        .upload-submit-btn {
            display: block;
            width: 100%;
            background: #e8420f;
            color: white;
            border: none;
            padding: 11px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 800;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: .18s ease;
        }

        .upload-submit-btn:hover {
            background: #cc370b;
            transform: translateY(-1px);
        }

        .upload-input {
            display: none;
        }

        .upload-note {
            color: #aa9791;
            font-size: 10px;
            margin-top: 8px;
        }

        /* =========================
           DELIVERIES BUTTON
        ========================= */

        .deliveries-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            text-decoration: none;
            background: #e8420f;
            color: white;
            padding: 12px;
            border-radius: 11px;
            font-weight: 800;
            font-size: 12px;
            margin-top: 15px;
            transition: .18s ease;
        }

        .deliveries-btn:hover {
            background: #cc370b;
            transform: translateY(-1px);
        }

        /* =========================
           ACCOUNT INFORMATION
        ========================= */

        .card-title {
            font-family: 'Baloo 2', sans-serif;
            font-size: 23px;
            font-weight: 800;
            color: #14213d;
            margin-bottom: 18px;
        }

        .info {
            background: #fffaf8;
            border-radius: 13px;
            padding: 4px 13px;
        }

        .info-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 14px 3px;
            border-bottom: 1px solid #f2e8e5;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #9b817a;
            font-size: 12px;
            font-weight: 600;
        }

        .info-value {
            color: #3d302d;
            font-size: 12px;
            font-weight: 800;
            text-align: right;
            word-break: break-word;
        }

        .rider-id {
            font-family: monospace;
            font-size: 11px;
            background: #f5eeeb;
            padding: 5px 8px;
            border-radius: 7px;
        }

        /* =========================
           STATISTICS
        ========================= */

        .statistics {
            margin-top: 18px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .stat {
            background: #fffaf8;
            border: 1px solid #f2e5e1;
            border-radius: 13px;
            padding: 18px 10px;
            text-align: center;
            transition: .18s ease;
        }

        .stat:hover {
            border-color: #f4c9bc;
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            margin: 0 auto 8px;
            border-radius: 11px;
            background: #fff0eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-number {
            font-family: 'Baloo 2', sans-serif;
            font-size: 27px;
            line-height: 1;
            font-weight: 800;
            color: #14213d;
        }

        .stat-label {
            color: #9b817a;
            font-size: 10px;
            margin-top: 5px;
            font-weight: 600;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .profile-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 700px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 18px 16px;
            }

            .brand {
                margin-bottom: 15px;
            }

            .menu {
                flex-direction: row;
                overflow-x: auto;
                padding-bottom: 3px;
            }

            .menu a {
                flex-shrink: 0;
                white-space: nowrap;
            }

            .logout-area {
                margin-top: 12px;
            }

            .main {
                margin-left: 0;
                padding: 25px 18px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .stats {
                grid-template-columns: repeat(3, 1fr);
            }

        }

        @media (max-width: 500px) {

            .topbar h1 {
                font-size: 29px;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .info-value {
                text-align: left;
            }

            .stats {
                grid-template-columns: 1fr;
            }

        }

        ::selection {
            background: #ffd7c2;
            color: #7c1a00;
        }

    </style>

</head>

<body>


    {{-- =========================
         SIDEBAR
    ========================= --}}

    <aside class="sidebar">

        <div class="brand">

            <div class="brand-icon">
                🛍️
            </div>

            <div class="brand-text">

                <div class="brand-name">
                    BoomBuy
                </div>

                <div class="brand-subtitle">
                    Rider Center
                </div>

            </div>

        </div>


        <nav class="menu">

            <a href="{{ route('rider.dashboard') }}">

                <span class="menu-icon">
                    🏠
                </span>

                <span>
                    Dashboard
                </span>

            </a>


            <a href="{{ route('rider.deliveries') }}">

                <span class="menu-icon">
                    🚚
                </span>

                <span>
                    My Deliveries
                </span>

            </a>


            <a
                href="{{ route('rider.profile') }}"
                class="active"
            >

                <span class="menu-icon">
                    👤
                </span>

                <span>
                    My Profile
                </span>

            </a>


            @php

                $riderUnreadNotifications =
                    \App\Models\Notification::where(
                        'user_id',
                        $user['id']
                    )
                    ->whereNull('read_at')
                    ->count();

            @endphp


            <a
                href="{{ route('rider.notifications') }}"
                class="notification-link"
            >

                <span class="menu-icon">
                    🔔
                </span>

                <span>
                    Notifications
                </span>

                @if($riderUnreadNotifications > 0)

                    <span class="notification-badge">

                        {{
                            $riderUnreadNotifications > 99
                            ? '99+'
                            : $riderUnreadNotifications
                        }}

                    </span>

                @endif

            </a>


            <a
                href="{{ url('/') }}"
                class="store-link"
            >

                <span class="menu-icon">
                    🛍️
                </span>

                <span>
                    Store
                </span>

            </a>

        </nav>


        <div class="logout-area">

            <form
                method="POST"
                action="{{ route('logout') }}"
                onsubmit="return confirm('Are you sure you want to log out?');"
            >

                @csrf

                <button
                    type="submit"
                    class="logout-btn"
                >
                    🚪 &nbsp; Logout
                </button>

            </form>

        </div>

    </aside>


    {{-- =========================
         MAIN
    ========================= --}}

    <main class="main">

        <div class="content">


            <div class="topbar">

                <div>

                    <div class="page-kicker">
                        RIDER CENTER
                    </div>

                    <h1>
                        My Profile 👤
                    </h1>

                    <p class="subtitle">
                        Manage your rider account and profile.
                    </p>

                </div>


                <div class="profile-pill">

                    <div class="profile-pill-icon">
                        🚴
                    </div>

                    <strong>
                        {{ $user['name'] ?? 'Rider' }}
                    </strong>

                </div>

            </div>


            {{-- ALERTS --}}

            @if(session('success'))

                <div class="alert success">
                    ✅ {{ session('success') }}
                </div>

            @endif


            @if(session('error'))

                <div class="alert error">
                    ❌ {{ session('error') }}
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

                                    🚴

                                @endif

                            </div>


                            <div class="camera">
                                📷
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
                                        : '📷 Choose Photo';
                                    "
                                >


                                <label
                                    for="profile_photo"
                                    class="upload-label"
                                    id="file-chosen-label"
                                >
                                    📷 Choose Photo
                                </label>


                                <button
                                    type="submit"
                                    class="upload-submit-btn"
                                >
                                    ✓ Upload Photo
                                </button>

                            </form>

                        </div>

                    </div>


                    <a
                        href="{{ route('rider.deliveries') }}"
                        class="deliveries-btn"
                    >
                        🚚 View My Deliveries
                    </a>

                </div>


                {{-- RIGHT SIDE --}}

                <div>


                    {{-- ACCOUNT INFORMATION --}}

                    <div class="card">

                        <div class="card-title">
                            👤 Account Information
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


                    @php

                        $riderId =
                            $user['id'] ?? null;

                        $myDeliveries =
                            DB::table('orders')
                                ->where(
                                    'rider_id',
                                    $riderId
                                )
                                ->get();

                        $totalDeliveries =
                            $myDeliveries->count();

                        $deliveredCount =
                            $myDeliveries
                                ->where(
                                    'status',
                                    'Delivered'
                                )
                                ->count();

                        $activeCount =
                            $myDeliveries
                                ->whereIn(
                                    'status',
                                    [
                                        'Picked Up',
                                        'On the Way',
                                        'Out for Delivery'
                                    ]
                                )
                                ->count();

                    @endphp


                    {{-- STATISTICS --}}

                    <div class="card statistics">

                        <div class="card-title">
                            📊 Delivery Statistics
                        </div>


                        <div class="stats">


                            <div class="stat">

                                <div class="stat-icon">
                                    📦
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
                                    🚚
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
                                    ✅
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

                </div>

            </div>

        </div>

    </main>


    @include('partials.pwa-register')

</body>
</html>