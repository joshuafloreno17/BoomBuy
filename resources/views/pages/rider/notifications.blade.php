<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications — BoomBuy Rider</title>

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
            background: #fff8f5;
            color: #222;
        }

        a {
            text-decoration: none;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;

            width: 250px;
            height: 100vh;

            background: #ffffff;

            color: #222;

            padding: 25px 18px;

            border-right: 1px solid #f3e4df;

            z-index: 100;

            display: flex;
            flex-direction: column;
        }

        .logo {
            font-family: 'Baloo 2', sans-serif;

            font-size: 28px;
            font-weight: 800;

            margin-bottom: 35px;

            padding-left: 10px;

            color: #222;
        }

        .logo span {
            color: #e8420f;
        }

        .menu-title {
            font-size: 11px;

            color: #a28f89;

            text-transform: uppercase;

            margin: 22px 10px 10px;

            letter-spacing: 1.2px;

            font-weight: 700;
        }

        .menu {
            display: flex;
            flex-direction: column;
        }

        .menu a {
            position: relative;

            display: flex;
            align-items: center;

            gap: 9px;

            color: #695c57;

            padding: 13px 14px;

            border-radius: 10px;

            margin-bottom: 6px;

            font-size: 14px;

            font-weight: 600;

            transition: 0.2s;
        }

        .menu a:hover {
            background: #fff1eb;
            color: #e8420f;
        }

        .menu a.active {
            background: #e8420f;
            color: #ffffff;

            font-weight: 700;

            box-shadow: 0 5px 14px rgba(232, 66, 15, 0.18);
        }

        /* =========================
           NOTIFICATION BADGE
        ========================= */

        .notification-badge {
            margin-left: auto;

            min-width: 21px;
            height: 21px;

            padding: 0 6px;

            border-radius: 999px;

            background: #ef4444;

            color: white;

            font-size: 10px;

            font-weight: 800;

            display: inline-flex;

            align-items: center;

            justify-content: center;
        }

        /* =========================
           LOGOUT
        ========================= */

        .logout {
            margin-top: auto;

            padding-top: 20px;
        }

        .logout form {
            margin: 0;
        }

        .logout button {
            width: 100%;

            border: none;

            background: transparent;

            color: #e8420f;

            padding: 12px;

            border-radius: 9px;

            cursor: pointer;

            font-size: 14px;

            font-weight: 700;

            font-family: 'Plus Jakarta Sans', sans-serif;

            text-align: left;

            transition: 0.2s;
        }

        .logout button:hover {
            background: #fff1eb;

            color: #c13206;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 250px;

            padding: 35px;

            min-height: 100vh;
        }

        .container {
            max-width: 950px;

            margin: 0 auto;
        }

        /* =========================
           HEADER
        ========================= */

        .page-header {
            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 28px;
        }

        .page-header h1 {
            font-family: 'Baloo 2', sans-serif;

            font-size: 32px;

            font-weight: 800;

            color: #222;

            letter-spacing: -0.01em;
        }

        .page-header p {
            color: #816f6a;

            margin-top: 5px;

            font-size: 14px;
        }

        .profile-top {
            background: white;

            padding: 10px 16px;

            border-radius: 10px;

            border: 1px solid #f3e4df;

            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);

            white-space: nowrap;
        }

        .profile-top strong {
            color: #222;
        }

        /* =========================
           NOTIFICATIONS
        ========================= */

        .notifications {
            display: flex;

            flex-direction: column;

            gap: 12px;
        }

        .notification-form {
            margin: 0;
        }

        .notification-card {
            width: 100%;

            border: 1px solid #eee3df;

            border-radius: 14px;

            background: white;

            padding: 18px;

            text-align: left;

            cursor: pointer;

            transition: 0.2s;

            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .notification-card:hover {
            transform: translateY(-1px);

            box-shadow: 0 6px 20px rgba(232, 66, 15, 0.07);

            border-color: #f4c8b9;
        }

        /* =========================
           UNREAD
        ========================= */

        .notification-card.unread {
            border-left: 4px solid #e8420f;

            background: #fffaf8;
        }

        /* =========================
           READ
        ========================= */

        .notification-card.read {
            cursor: default;

            opacity: 0.72;
        }

        .notification-card.read:hover {
            transform: none;

            box-shadow: none;

            border-color: #eee3df;
        }

        /* =========================
           NOTIFICATION TOP
        ========================= */

        .notification-top {
            display: flex;

            align-items: flex-start;

            gap: 14px;
        }

        .notification-icon {
            width: 46px;
            height: 46px;

            flex-shrink: 0;

            border-radius: 12px;

            background: #fff1eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-family: 'Baloo 2', sans-serif;

            font-size: 18px;

            font-weight: 700;

            color: #222;
        }

        .notification-message {
            color: #816f6a;

            font-size: 13px;

            line-height: 1.6;

            margin-top: 3px;
        }

        .notification-time {
            color: #a28f89;

            font-size: 10px;

            margin-top: 7px;
        }

        /* =========================
           BOTTOM
        ========================= */

        .notification-bottom {
            display: flex;

            align-items: center;

            gap: 8px;

            margin-top: 13px;
        }

        .unread-dot {
            width: 7px;
            height: 7px;

            background: #e8420f;

            border-radius: 50%;
        }

        .notification-type {
            background: #fff1eb;

            color: #d63c0d;

            padding: 5px 8px;

            border-radius: 6px;

            font-size: 9px;

            font-weight: 800;

            text-transform: uppercase;
        }

        .read-label {
            background: #eaf8ef;

            color: #15803d;

            padding: 5px 8px;

            border-radius: 6px;

            font-size: 9px;

            font-weight: 800;
        }

        /* =========================
           EMPTY
        ========================= */

        .empty {
            background: white;

            border: 1px solid #eee3df;

            border-radius: 16px;

            padding: 65px 30px;

            text-align: center;

            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
        }

        .empty-icon {
            font-size: 50px;

            margin-bottom: 12px;
        }

        .empty h3 {
            font-family: 'Baloo 2', sans-serif;

            font-size: 22px;

            color: #523d36;
        }

        .empty p {
            color: #816f6a;

            font-size: 13px;

            margin-top: 6px;
        }

        /* =========================
           BOOMBUY DESIGN OVERRIDES
        ========================= */

        h1,
        h2,
        h3,
        .logo,
        .page-header h1,
        .notification-title,
        .empty h3 {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;

            letter-spacing: -0.01em;
        }

        button {
            border-radius: 12px;

            transition:
                transform 0.15s ease,
                box-shadow 0.15s ease,
                background 0.15s ease;
        }

        ::selection {
            background: #ffd7c2;

            color: #7c1a00;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 700px) {

            .sidebar {
                position: relative;

                width: 100%;

                height: auto;

                padding: 14px 16px;

                border-right: none;

                border-bottom: 1px solid #f3e4df;
            }

            .logo {
                font-size: 21px;

                margin-bottom: 10px;

                padding-left: 2px;
            }

            .menu-title {
                display: none;
            }

            .menu {
                flex-direction: row;

                overflow-x: auto;

                gap: 8px;

                padding-bottom: 2px;

                -webkit-overflow-scrolling: touch;
            }

            .menu a {
                white-space: nowrap;

                flex-shrink: 0;

                margin-bottom: 0;

                padding: 10px 14px;

                font-size: 13px;
            }

            .notification-badge {
                position: absolute;

                top: 4px;

                right: 4px;

                min-width: 17px;

                height: 17px;

                padding: 0 4px;

                font-size: 9px;
            }

            .logout {
                margin-top: 10px;

                padding-top: 0;
            }

            .logout button {
                background: #fff1eb;

                text-align: center;
            }

            .main {
                margin-left: 0;

                padding: 20px;
            }

            .container {
                max-width: none;
            }

            .page-header {
                flex-direction: column;

                gap: 14px;
            }

            .page-header h1 {
                font-size: 28px;
            }

            .profile-top {
                width: 100%;
            }

            .notification-card {
                padding: 15px;
            }
        }

        @media (max-width: 450px) {

            .main {
                padding: 16px;
            }

            .notification-top {
                gap: 10px;
            }

            .notification-icon {
                width: 42px;
                height: 42px;

                font-size: 19px;
            }

            .notification-title {
                font-size: 17px;
            }

            .notification-message {
                font-size: 12px;
            }
        }

    </style>

</head>

<body>

    <!-- =========================
         SIDEBAR
    ========================= -->

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

            <a href="{{ route('rider.profile') }}">
                👤 My Profile
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
                class="active"
            >

                🔔 Notifications

                @if($riderUnreadNotifications > 0)

                    <span class="notification-badge">

                        {{ $riderUnreadNotifications > 99
                            ? '99+'
                            : $riderUnreadNotifications }}

                    </span>

                @endif

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

            <form
                method="POST"
                action="{{ route('logout') }}"
                onsubmit="return confirm('Are you sure you want to log out?');"
            >

                @csrf

                <button type="submit">
                    🚪 Logout
                </button>

            </form>

        </div>

    </aside>


    <!-- =========================
         MAIN
    ========================= -->

    <main class="main">

        <div class="container">

            <div class="page-header">

                <div>

                    <h1>
                        Notifications 🔔
                    </h1>

                    <p>
                        Stay updated with your delivery assignments
                        and rider account status.
                    </p>

                </div>

                <div class="profile-top">

                    🚴

                    <strong>
                        {{ $user['name'] ?? 'Rider' }}
                    </strong>

                </div>

            </div>


            @if($notifications->count() > 0)

                <div class="notifications">

                    @foreach($notifications as $notification)

                        @php

                            $icon = match($notification->type) {

                                'delivery',
                                'order' => '🚚',

                                'delivery_status',
                                'order_status' => '📦',

                                'rider' => '🏍️',

                                default => '🔔',

                            };

                        @endphp


                        <form
                            action="{{ !$notification->read_at
                                ? route(
                                    'rider.notifications.read',
                                    $notification->id
                                )
                                : '#'
                            }}"
                            method="{{ !$notification->read_at
                                ? 'POST'
                                : 'GET'
                            }}"
                            class="notification-form"
                        >

                            @if(!$notification->read_at)

                                @csrf

                            @endif


                            <button
                                type="{{ !$notification->read_at
                                    ? 'submit'
                                    : 'button'
                                }}"
                                class="notification-card
                                    {{ $notification->read_at
                                        ? 'read'
                                        : 'unread'
                                    }}"
                                {{ $notification->read_at
                                    ? 'disabled'
                                    : ''
                                }}
                            >

                                <div class="notification-top">

                                    <div class="notification-icon">
                                        {{ $icon }}
                                    </div>


                                    <div class="notification-content">

                                        <div class="notification-title">

                                            {{ $notification->title }}

                                        </div>

                                        <div class="notification-message">

                                            {{ $notification->message }}

                                        </div>

                                        <div class="notification-time">

                                            {{ \Carbon\Carbon::parse(
                                                $notification->created_at
                                            )->diffForHumans() }}

                                        </div>

                                    </div>

                                </div>


                                <div class="notification-bottom">

                                    @if(!$notification->read_at)

                                        <span class="unread-dot"></span>

                                    @endif


                                    @if($notification->type)

                                        <span class="notification-type">

                                            {{ str_replace(
                                                '_',
                                                ' ',
                                                $notification->type
                                            ) }}

                                        </span>

                                    @endif


                                    @if($notification->read_at)

                                        <span class="read-label">
                                            ✓ Read
                                        </span>

                                    @endif

                                </div>

                            </button>

                        </form>

                    @endforeach

                </div>

            @else

                <div class="empty">

                    <div class="empty-icon">
                        🔔
                    </div>

                    <h3>
                        No Notifications Yet
                    </h3>

                    <p>
                        New delivery assignments and rider updates
                        will appear here.
                    </p>

                </div>

            @endif

        </div>

    </main>


    @include('partials.pwa-register')

</body>

</html>