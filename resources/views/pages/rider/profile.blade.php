
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

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: #fbf6f5;
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
            color: #f9bc16;
        }

        .menu-title {
            font-size: 11px;
            color: #b0a09b;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin: 22px 10px 10px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #dbd3d1;
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
            background: #f9bc16;
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
            color: #816f6a;
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
            border: 1px solid #f4efee;
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
            background: #fffaed;
            border: 5px solid #fee8aa;
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
            background: #f9bc16;
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
            background: #fffaed;
            color: #eaaf0c;
            padding: 7px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .profile-description {
            color: #816f6a;
            font-size: 14px;
            margin-top: 14px;
        }

        /* UPLOAD */

        .upload-box {
            margin-top: 24px;
            padding-top: 22px;
            border-top: 1px solid #ebe6e5;
        }

        .upload-label {
            display: block;
            background: #f9bc16;
            color: white;
            padding: 12px;
            border-radius: 9px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: .2s;
            text-align: center;
            margin-bottom: 10px;
        }

        .upload-label:hover {
            background: #eaaf0c;
        }

        .upload-submit-btn {
            display: block;
            width: 100%;
            background: #e8420f;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 9px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: .2s;
        }

        .upload-submit-btn:hover {
            background: #c13206;
        }

        .upload-input {
            display: none;
        }

        .upload-note {
            color: #b0a09b;
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
            border-top: 1px solid #ebe6e5;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 25px;
            padding: 17px 5px;
            border-bottom: 1px solid #f9f3f1;
        }

        .info-label {
            color: #816f6a;
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
            background: #f6f4f3;
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
            background: #fcf9f8;
            border: 1px solid #f7f0ee;
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
            color: #816f6a;
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
                display: flex;
                flex-direction: column;
                padding: 14px 16px;
            }

            .logo {
                font-size: 20px;
                margin-bottom: 10px;
            }

            .menu-title {
                display: none;
            }

            .menu {
                display: flex;
                flex-direction: row;
                overflow-x: auto;
                gap: 8px;
                margin-bottom: 4px;
                -webkit-overflow-scrolling: touch;
            }

            .menu a {
                white-space: nowrap;
                margin-bottom: 0;
                flex-shrink: 0;
                font-size: 13px;
                padding: 10px 14px;
            }

            .logout {
                position: static;
                margin-top: 10px;
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
    
/* ===== BoomBuy Vibrant Design System Overrides ===== */
h1, h2, h3, .logo, .hero-title, .hero h1, .section-title, .page-title,
.product-title, .price, .cta, .cta-title, .brand, .checkout-title,
.card-title, .modal-title, .auth-title, .form-title, .empty-title,
.step-title, .order-title, .stat-title, .stat-value, .banner-title {
    font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
    letter-spacing: -0.01em;
}
button, .btn, [class*="btn-"], .add-to-cart, .buy-now, .checkout-btn,
.register-btn, .login-btn, .submit-btn, .primary-btn {
    border-radius: 12px !important;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}
button:hover, .btn:hover, [class*="btn-"]:hover, .add-to-cart:hover,
.buy-now:hover, .primary-btn:hover {
    transform: translateY(-1px);
}
.card, [class*="-card"], .product-card {
    border-radius: 16px !important;
}
::selection {
    background: #ffd7c2;
    color: #7c1a00;
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

       <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Are you sure you want to log out?');">
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
        id="profile_photo"
        class="upload-input"
        accept="image/png,image/jpeg,image/webp"
        required
        onchange="document.getElementById('file-chosen-label').textContent = this.files[0] ? this.files[0].name : '📷 Choose Photo';"
    >

    <label for="profile_photo" class="upload-label" id="file-chosen-label">
        📷 Choose Photo
    </label>

    <button type="submit" class="upload-submit-btn">
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
    $riderId = $user['id'] ?? null;

    $myDeliveries = DB::table('orders')
        ->where('rider_id', $riderId)
        ->get();

    $totalDeliveries = $myDeliveries->count();

    $deliveredCount = $myDeliveries
        ->where('status', 'Delivered')
        ->count();

    $activeCount = $myDeliveries
        ->whereIn('status', [
            'Picked Up',
            'On the Way',
            'Out for Delivery'
        ])
        ->count();
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

    @include('partials.pwa-register')

</body>
</html>

