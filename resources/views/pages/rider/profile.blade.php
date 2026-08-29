
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile — BoomBuy</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #111827;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #111827;
            color: white;
            padding: 25px 18px;
            z-index: 10;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            padding-left: 10px;
            margin-bottom: 38px;
        }

        .logo span {
            color: #f97316;
        }

        .menu-title {
            font-size: 11px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin: 22px 10px 10px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #d1d5db;
            text-decoration: none;
            padding: 13px 14px;
            border-radius: 9px;
            margin-bottom: 6px;
            transition: .2s;
        }

        .menu a:hover {
            background: #1f2937;
            color: white;
        }

        .menu a.active {
            background: #f97316;
            color: white;
            font-weight: 600;
        }

        .logout {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 25px;
        }

        .logout button {
            width: 100%;
            border: none;
            background: #dc2626;
            color: white;
            padding: 12px;
            border-radius: 9px;
            cursor: pointer;
            font-weight: 600;
        }

        .logout button:hover {
            background: #b91c1c;
        }

        /* MAIN */

        .main {
            margin-left: 250px;
            padding: 35px;
            min-height: 100vh;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .topbar h1 {
            font-size: 30px;
        }

        .subtitle {
            color: #6b7280;
            margin-top: 7px;
        }

        .profile-top {
            background: white;
            padding: 10px 16px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
        }

        /* ALERT */

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* PROFILE LAYOUT */

        .profile-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 22px;
            max-width: 1100px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 26px;
            box-shadow: 0 4px 18px rgba(15,23,42,.05);
            border: 1px solid #eef0f4;
        }

        /* PROFILE CARD */

        .profile-card {
            text-align: center;
        }

        .avatar-wrapper {
            position: relative;
            width: 125px;
            height: 125px;
            margin: 0 auto 18px;
        }

        .avatar {
            width: 125px;
            height: 125px;
            border-radius: 50%;
            overflow: hidden;
            background: #fff7ed;
            border: 5px solid #fed7aa;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 58px;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .camera {
            position: absolute;
            right: 0;
            bottom: 4px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #f97316;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid white;
            font-size: 17px;
        }

        .profile-card h2 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .role-badge {
            display: inline-block;
            background: #fff7ed;
            color: #ea580c;
            padding: 7px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .profile-description {
            color: #6b7280;
            font-size: 14px;
            margin-top: 14px;
        }

        /* UPLOAD */

        .upload-box {
            margin-top: 24px;
            padding-top: 22px;
            border-top: 1px solid #e5e7eb;
        }

        .upload-label {
            display: block;
            background: #f97316;
            color: white;
            padding: 12px;
            border-radius: 9px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: .2s;
        }

        .upload-label:hover {
            background: #ea580c;
        }

        .upload-input {
            display: none;
        }

        .upload-note {
            color: #9ca3af;
            font-size: 11px;
            margin-top: 9px;
        }

        /* ACCOUNT */

        .card-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .info {
            border-top: 1px solid #e5e7eb;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 25px;
            padding: 17px 5px;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-label {
            color: #6b7280;
            font-size: 14px;
        }

        .info-value {
            font-weight: 600;
            text-align: right;
            word-break: break-word;
        }

        /* ID */

        .rider-id {
            font-family: monospace;
            font-size: 12px;
            background: #f3f4f6;
            padding: 6px 9px;
            border-radius: 6px;
        }

        /* STATISTICS */

        .statistics {
            margin-top: 22px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .stat {
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 12px;
            padding: 20px 12px;
            text-align: center;
        }

        .stat-icon {
            font-size: 26px;
            margin-bottom: 8px;
        }

        .stat-number {
            font-size: 25px;
            font-weight: 800;
        }

        .stat-label {
            color: #6b7280;
            font-size: 12px;
            margin-top: 5px;
        }

        /* BUTTON */

        .deliveries-btn {
            display: block;
            text-align: center;
            text-decoration: none;
            background: #111827;
            color: white;
            padding: 13px;
            border-radius: 9px;
            font-weight: 700;
            margin-top: 22px;
        }

        .deliveries-btn:hover {
            background: #1f2937;
        }

        /* RESPONSIVE */

        @media (max-width: 950px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding-bottom: 85px;
            }

            .logout {
                bottom: 20px;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .info-row {
                flex-direction: column;
                gap: 7px;
            }

            .info-value {
                text-align: left;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">

    <div class="logo">
        Boom<span>Buy</span>
    </div>

    <div class="menu-title">
        Rider Menu
    </div>

    <div class="menu">

        <a href="{{ route('rider.dashboard') }}">
            🏠 Dashboard
        </a>

        <a href="{{ route('rider.deliveries') }}">
            🚚 My Deliveries
        </a>

        <a href="{{ route('rider.profile') }}" class="active">
            👤 My Profile
        </a>

    </div>

    <div class="menu-title">
        Account
    </div>

    <div class="menu">

        <a href="{{ url('/') }}">
            🛍️ BoomBuy Store
        </a>

    </div>

    <div class="logout">

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit">
                🚪 Logout
            </button>
        </form>

    </div>

</aside>


<main class="main">

    <div class="topbar">

        <div>
            <h1>My Profile</h1>

            <p class="subtitle">
                Manage your rider account and profile.
            </p>
        </div>

        <div class="profile-top">
            🚴
            <strong>
                {{ $user['name'] ?? 'Rider' }}
            </strong>
        </div>

    </div>


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


    <div class="profile-grid">

        <!-- LEFT -->

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


                <div class="upload-box">

               <form action="{{ route('rider.profile.photo') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <input
        type="file"
        name="profile_photo"
        accept="image/png,image/jpeg,image/webp"
        required
    >

    <button type="submit">
        📷 Upload Photo
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


        <!-- RIGHT -->

        <div>

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

                $orders = session()->get('orders', []);

                $riderId = $user['id'] ?? null;

                $myDeliveries = array_filter(
                    $orders,
                    function ($order) use ($riderId) {
                        return ($order['rider_id'] ?? null) === $riderId;
                    }
                );

                $totalDeliveries = count($myDeliveries);

                $deliveredCount = count(
                    array_filter(
                        $myDeliveries,
                        function ($order) {
                            return ($order['status'] ?? '') === 'Delivered';
                        }
                    )
                );

                $activeCount = count(
                    array_filter(
                        $myDeliveries,
                        function ($order) {
                            return in_array(
                                ($order['status'] ?? ''),
                                ['Picked Up', 'On the Way']
                            );
                        }
                    )
                );

            @endphp


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

</main>

</body>
</html>

