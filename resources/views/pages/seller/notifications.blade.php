<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Notifications — BoomBuy Seller</title>

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
        }

        /* SIDEBAR */

        .sidebar {
            width: 230px;
            background: white;
            border-right: 1px solid #ffe9e2;
            padding: 25px 18px;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
        }

        .logo {
            font-family: 'Baloo 2', sans-serif;
            font-size: 23px;
            font-weight: 800;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .sidebar-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #b99c93;
            margin-top: 22px;
            margin-bottom: 4px;
            font-weight: 700;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 8px;
        }

        .menu a {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            border-radius: 10px;
            color: #8d6c62;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
        }

        .menu a:hover,
        .menu a.active {
            background: #fff4f1;
            color: #e8420f;
        }

        .notification-badge {
            margin-left: auto;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #e8420f;
            color: white;
            font-size: 10px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid #ffe9e2;
        }

        .user {
            color: #8d6c62;
            font-size: 12px;
        }

        .logout {
            border: none;
            background: #fff3f0;
            color: #dc2626;
            padding: 8px 12px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        /* MAIN */

        .main-content {
            margin-left: 230px;
            width: calc(100% - 230px);
            min-height: 100vh;
        }

        .container {
            width: 86%;
            max-width: 900px;
            margin: 45px auto 80px;
        }

        .page-header {
            background: white;
            border: 1px solid #f7e5e0;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 25px;
        }

        .page-header small {
            color: #db5a33;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 700;
        }

        .page-header h1 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 34px;
            margin-top: 7px;
        }

        .page-header p {
            color: #977970;
            font-size: 13px;
            margin-top: 5px;
        }

        /* NOTIFICATIONS */

        .notifications-box {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .notification-form {
            margin: 0;
        }

        .notification-card {
            width: 100%;
            border: 1px solid #f7e5e0;
            border-radius: 14px;
            background: white;
            padding: 18px;
            text-align: left;
            cursor: pointer;
            transition: 0.2s;
        }

        .notification-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.05);
        }

        .notification-card.unread {
            border-left: 4px solid #e8420f;
            background: #fffaf8;
        }

        .notification-card.read {
            cursor: default;
            opacity: 0.82;
        }

        .notification-top {
            display: flex;
            align-items: flex-start;
            gap: 13px;
        }

        .notification-icon {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            border-radius: 11px;
            background: #ffefea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-family: 'Baloo 2', sans-serif;
            font-size: 17px;
            font-weight: 700;
            color: #172033;
        }

        .notification-message {
            color: #8d6c62;
            font-size: 12px;
            line-height: 1.6;
            margin-top: 3px;
        }

        .notification-time {
            color: #b99c93;
            font-size: 10px;
            margin-top: 7px;
        }

        .notification-bottom {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            padding-left: 57px;
        }

        .unread-dot {
            width: 7px;
            height: 7px;
            background: #e8420f;
            border-radius: 50%;
        }

        .notification-type {
            display: inline-block;
            background: #fff2ee;
            color: #e8420f;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .read-label {
            color: #15803d;
            background: #eaf8ef;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 800;
        }

        /* EMPTY */

        .empty {
            background: white;
            border: 1px solid #f7e5e0;
            border-radius: 14px;
            padding: 60px 30px;
            text-align: center;
        }

        .empty-icon {
            font-size: 45px;
            margin-bottom: 12px;
        }

        .empty h3 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 22px;
        }

        .empty p {
            color: #977970;
            font-size: 12px;
            margin-top: 7px;
        }

        /* FOOTER */

        footer {
            margin-left: 230px;
            background: white;
            border-top: 1px solid #f7e5e0;
            padding: 30px 7%;
            display: flex;
            justify-content: space-between;
            color: #977970;
            font-size: 12px;
        }

        footer strong {
            color: #e8420f;
        }

        /* MOBILE */

        @media (max-width: 900px) {

            .sidebar {
                width: 72px;
                padding: 20px 8px;
            }

            .sidebar .label-text,
            .sidebar-label {
                display: none;
            }

            .menu a {
                justify-content: center;
            }

            .notification-badge {
                position: absolute;
                top: 5px;
                right: 5px;
                min-width: 17px;
                height: 17px;
                padding: 0 4px;
                font-size: 9px;
            }

            .main-content {
                margin-left: 72px;
                width: calc(100% - 72px);
            }

            footer {
                margin-left: 72px;
            }
        }

        @media (max-width: 600px) {

            .container {
                width: 92%;
                margin-top: 25px;
            }

            .page-header {
                padding: 23px;
            }

            .page-header h1 {
                font-size: 28px;
            }

            .notification-card {
                padding: 15px;
            }

            .notification-bottom {
                padding-left: 0;
            }

            footer {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
        }

    </style>

</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <a href="{{ route('seller.dashboard') }}" class="logo">
            Boom<span>Buy</span>
        </a>

        <div class="sidebar-label">
            Seller Panel
        </div>

        <nav class="menu">

            <a href="{{ route('seller.dashboard') }}">
                📊
                <span class="label-text">
                    Dashboard
                </span>
            </a>

            <a href="{{ route('seller.products.create') }}">
                ➕
                <span class="label-text">
                    Add Product
                </span>
            </a>

            <a href="{{ route('seller.orders') }}">
                🛒
                <span class="label-text">
                    Orders
                </span>
            </a>

            @php
                $sellerUnreadNotifications =
                    \App\Models\Notification::where(
                        'user_id',
                        $user['id']
                    )
                    ->whereNull('read_at')
                    ->count();
            @endphp

            <a
                href="{{ route('seller.notifications') }}"
                class="active"
            >
                🔔

                <span class="label-text">
                    Notifications
                </span>

                @if($sellerUnreadNotifications > 0)

                    <span class="notification-badge">
                        {{ $sellerUnreadNotifications > 99
                            ? '99+'
                            : $sellerUnreadNotifications }}
                    </span>

                @endif

            </a>

        </nav>

        <div class="sidebar-footer">

            <div
                class="user"
                style="padding:0 4px; margin-bottom:8px;"
            >
                Seller: {{ $user['name'] ?? 'Seller' }}
            </div>

            <form
                action="{{ route('logout') }}"
                method="POST"
                onsubmit="return confirm('Are you sure you want to log out?');"
            >

                @csrf

                <button
                    type="submit"
                    class="logout"
                    style="width:100%;"
                >
                    Logout
                </button>

            </form>

        </div>

    </aside>


    <!-- MAIN -->

    <main class="main-content">

        <div class="container">

            <section class="page-header">

                <small>
                    Seller Panel
                </small>

                <h1>
                    Notifications 🔔
                </h1>

                <p>
                    Stay updated with your orders, returns, and application status.
                </p>

            </section>


            @if($notifications->count() > 0)

                <div class="notifications-box">

                    @foreach($notifications as $notification)

                        @php

                            $icon = match($notification->type) {

                                'order' => '🛒',

                                'order_status' => '📦',

                                'return_refund' => '🔄',

                                'seller' => '🏪',

                                'rider' => '🏍️',

                                default => '🔔',

                            };

                        @endphp

                        <form
                            action="{{ !$notification->read_at
                                ? route(
                                    'seller.notifications.read',
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
                                        : 'unread' }}"
                                {{ $notification->read_at
                                    ? 'disabled'
                                    : '' }}
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
                        New order and seller updates will appear here.
                    </p>

                </div>

            @endif

        </div>

    </main>

</div>


<footer>

    <div>
        © 2026 <strong>BoomBuy</strong>
    </div>

    <div>
        Seller Notifications
    </div>

</footer>


@include('partials.pwa-register')

</body>

</html>