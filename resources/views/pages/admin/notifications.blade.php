<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/admin-sidebar.css') }}">

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


            .page-header {
                align-items: flex-start;
            }

            .page-header h1 {
                font-size: 27px;
            }
        }

        @media (max-width: 480px) {


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

    <x-layout.admin-sidebar active="notifications" />


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
                            ? route('admin.notifications.read', $notification->id)
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
                                    <i class="bi bi-shop"></i>
                                @elseif($notification->type === 'rider')
                                    <i class="bi bi-bicycle"></i>
                                @elseif($notification->type === 'order')
                                    <i class="bi bi-box-seam-fill"></i>
                                @else
                                    <i class="bi bi-bell-fill"></i>
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
                                            <i class="bi bi-check2"></i> Read
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