<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications — BoomBuy</title>

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
            background: #fff7f4;
            color: #172033;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */

        .sidebar {
            width: 245px;
            background: #ffffff;
            border-right: 1px solid #f7e5e0;
            padding: 25px 18px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
        }

        .logo {
            padding: 0 12px;
            margin-bottom: 35px;
            font-family: 'Baloo 2', sans-serif;
            font-size: 23px;
            font-weight: 700;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .admin-label {
            padding: 0 12px;
            color: #b99c93;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 9px;
            width: 100%;
            padding: 12px;
            border-radius: 9px;
            color: #8d6c62;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .menu a:hover {
            background: #fff4f1;
            color: #e8420f;
        }

        .menu a.active {
            background: #ffefea;
            color: #e8420f;
        }

        .notification-count {
            margin-left: auto;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #e8420f;
            color: #fff;
            font-size: 9px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* LOGOUT */

        .logout {
            margin-top: 35px;
        }

        .logout button {
            transition: 0.2s ease;
        }

        .logout button:hover {
            background: #fff1f2 !important;
        }

        /* MAIN */

        .main {
            margin-left: 245px;
            width: calc(100% - 245px);
            min-height: 100vh;
            padding: 35px 5%;
        }

        /* TOP */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .page-header small {
            color: #db5a33;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 10px;
            font-weight: 700;
        }

        .page-header h1 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 32px;
            margin-top: 7px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 14px;
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 10px;
            color: #8d6c62;
            font-size: 11px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .back-button:hover {
            color: #e8420f;
            background: #fff4f1;
        }

        /* NOTIFICATIONS */

        .notification-panel {
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 16px;
            overflow: hidden;
        }

        .notification-header {
            padding: 22px 24px;
            border-bottom: 1px solid #f7efed;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .notification-header h2 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 18px;
        }

        .notification-header p {
            color: #977970;
            font-size: 11px;
            margin-top: 4px;
        }

        .notification-list {
            display: flex;
            flex-direction: column;
        }

        .notification-form {
            width: 100%;
        }

        .notification-card {
            width: 100%;
            border: none;
            border-bottom: 1px solid #f7efed;
            background: #ffffff;
            padding: 20px 24px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            text-align: left;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .notification-card:hover {
            background: #fffaf8;
        }

        .notification-card.unread {
            background: #fff8f5;
        }

        .notification-card.read {
            background: #ffffff;
            cursor: default;
            opacity: 0.72;
        }

        .notification-icon {
            width: 43px;
            height: 43px;
            border-radius: 12px;
            background: #ffefea;
            color: #e8420f;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-size: 13px;
            font-weight: 800;
            color: #172033;
        }

        .notification-message {
            color: #6f5c56;
            font-size: 11px;
            line-height: 1.6;
            margin-top: 5px;
        }

        .notification-date {
            color: #b99c93;
            font-size: 9px;
            margin-top: 8px;
        }

        .notification-status {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
        }

        .unread-dot {
            width: 7px;
            height: 7px;
            background: #e8420f;
            border-radius: 50%;
        }

        .notification-type {
            display: inline-flex;
            padding: 4px 8px;
            border-radius: 999px;
            background: #fff0eb;
            color: #e8420f;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .read-label {
            display: inline-flex;
            padding: 4px 8px;
            border-radius: 999px;
            background: #f1f5f3;
            color: #5f756b;
            font-size: 8px;
            font-weight: 800;
        }

        .empty-state {
            text-align: center;
            padding: 70px 20px;
            color: #b99c93;
        }

        .empty-icon {
            width: 55px;
            height: 55px;
            margin: 0 auto 14px;
            border-radius: 15px;
            background: #fff1ed;
            color: #e8420f;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-state h3 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 18px;
            color: #66534d;
        }

        .empty-state p {
            font-size: 11px;
            margin-top: 5px;
        }

        /* MOBILE */

        @media (max-width: 750px) {

            .sidebar {
                width: 68px;
                padding: 20px 9px;
            }

            .logo {
                font-size: 0;
                text-align: center;
                padding: 0;
            }

            .logo::before {
                content: "B";
                font-family: 'Baloo 2', sans-serif;
                font-size: 25px;
                font-weight: 800;
                color: #e8420f;
            }

            .admin-label {
                display: none;
            }

            .menu a {
                justify-content: center;
                padding: 12px 8px;
                font-size: 18px;
            }

            .menu a span {
                display: none;
            }

            .notification-count {
                position: absolute;
                margin-left: 25px;
                margin-top: -20px;
            }

            .logout button span {
                display: none;
            }

            .main {
                margin-left: 68px;
                width: calc(100% - 68px);
                padding: 25px 18px;
            }

            .page-header {
                align-items: flex-start;
            }

            .page-header h1 {
                font-size: 27px;
            }
        }

        @media (max-width: 480px) {

            .sidebar {
                width: 58px;
            }

            .main {
                margin-left: 58px;
                width: calc(100% - 58px);
                padding: 20px 12px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .notification-card {
                padding: 17px;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">
            Boom<span>Buy</span>
        </div>

        <div class="admin-label">
            Administration
        </div>

        <nav class="menu">

            <a href="{{ route('admin.dashboard') }}">
                <span>▦</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('admin.accounts') }}">
                <span>♙</span>
                <span>Accounts</span>
            </a>

            <a href="{{ route('admin.applications') }}">
                <span>✓</span>
                <span>Applications</span>
            </a>

            <a href="{{ route('admin.reports') }}">
                <span>↗</span>
                <span>Reports</span>
            </a>

            <a href="{{ route('admin.settings') }}">
                <span>☷</span>
                <span>Settings</span>
            </a>

            <!-- NOTIFICATIONS -->

            <a
                href="{{ route('notifications.index') }}"
                class="active"
            >
                <span>🔔</span>

                <span>
                    Notifications
                </span>

                @if($unreadCount > 0)
                    <span class="notification-count">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </a>

        </nav>

        <div class="logout">

            <form
                action="{{ route('admin.logout') }}"
                method="POST"
                onsubmit="return confirm('Are you sure you want to log out?');"
            >

                @csrf

                <button
                    type="submit"
                    style="
                        width:100%;
                        border:none;
                        background:transparent;
                        text-align:left;
                        padding:12px;
                        border-radius:9px;
                        color:#ef4444;
                        font-size:13px;
                        font-weight:600;
                        cursor:pointer;
                        display:flex;
                        align-items:center;
                        gap:9px;
                    "
                >
                    🚪
                    <span>
                        Logout
                    </span>
                </button>

            </form>

        </div>

    </aside>


    <!-- MAIN -->

    <main class="main">

        <div class="page-header">

            <div>

                <small>
                    BoomBuy Administration
                </small>

                <h1>
                    Notifications
                </h1>

            </div>

            <a
                href="{{ route('admin.dashboard') }}"
                class="back-button"
            >
                ← Back to Dashboard
            </a>

        </div>


        <!-- NOTIFICATION PANEL -->

        <section class="notification-panel">

            <div class="notification-header">

                <div>

                    <h2>
                        Admin Notifications
                    </h2>

                    <p>
                        Updates and alerts for your BoomBuy administration account.
                    </p>

                </div>

                <div>
                    @if($unreadCount > 0)
                        <span class="notification-type">
                            {{ $unreadCount }} Unread
                        </span>
                    @else
                        <span class="read-label">
                            All Caught Up
                        </span>
                    @endif
                </div>

            </div>


            <div class="notification-list">

                @forelse($notifications as $notification)

                    <form
                        action="{{ !$notification->read_at
                            ? route('notifications.read', $notification->id)
                            : '#'
                        }}"
                        method="{{ !$notification->read_at ? 'POST' : 'GET' }}"
                        class="notification-form"
                    >

                        @if(!$notification->read_at)
                            @csrf
                        @endif

                        <button
                            type="{{ !$notification->read_at ? 'submit' : 'button' }}"
                            class="notification-card {{ $notification->read_at ? 'read' : 'unread' }}"
                            {{ $notification->read_at ? 'disabled' : '' }}
                        >

                            <div class="notification-icon">

                                @if($notification->type === 'seller')
                                    🏪
                                @elseif($notification->type === 'rider')
                                    🛵
                                @elseif($notification->type === 'order')
                                    📦
                                @else
                                    🔔
                                @endif

                            </div>


                            <div class="notification-content">

                                <div class="notification-title">
                                    {{ $notification->title }}
                                </div>

                                <div class="notification-message">
                                    {{ $notification->message }}
                                </div>

                                <div class="notification-date">
                                    {{ $notification->created_at?->format('M d, Y • h:i A') }}
                                </div>

                                <div class="notification-status">

                                    @if(!$notification->read_at)
                                        <span class="unread-dot"></span>
                                    @endif

                                    @if($notification->type)
                                        <span class="notification-type">
                                            {{ str_replace('_', ' ', $notification->type) }}
                                        </span>
                                    @endif

                                    @if($notification->read_at)
                                        <span class="read-label">
                                            ✓ Read
                                        </span>
                                    @endif

                                </div>

                            </div>

                        </button>

                    </form>

                @empty

                    <div class="empty-state">

                        <div class="empty-icon">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="25"
                                height="25"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>

                        </div>

                        <h3>
                            No notifications yet
                        </h3>

                        <p>
                            New BoomBuy administration updates will appear here.
                        </p>

                    </div>

                @endforelse

            </div>

        </section>

    </main>

</div>

@include('partials.pwa-register')

</body>
</html>