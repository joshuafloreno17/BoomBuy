<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications | BoomBuy</title>

    @include('partials.pwa-head')

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        .notification-form {
    width: 100%;
    margin: 0;
}

.notification-card {
    width: 100%;
    font-family: 'Plus Jakarta Sans', sans-serif;
    text-align: left;
    cursor: pointer;
}

.notification-card:disabled {
    cursor: default;
}

.notification-card.read {
    opacity: 0.78;
    background: #ffffff;
}

.notification-card.read:hover {
    opacity: 1;
}

.read-type {
    background: #f4f4f4;
    color: #8d6c62;
}

.read-label {
    margin-left: auto;

    color: #9a8a84;

    font-size: 10px;
    font-weight: 700;
}

.notification-card {
    border: 1px solid #f4dfd8;
    outline: none;
    appearance: none;
}

.notification-card:focus {
    outline: none;
}

.notification-card.unread {
    cursor: pointer;
}

.notification-card.read {
    cursor: default;
}


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;

            font-family: 'Plus Jakarta Sans', sans-serif;

            background:
                radial-gradient(
                    circle at top right,
                    rgba(232, 66, 15, 0.10),
                    transparent 28%
                ),
                #fff7f4;

            color: #172033;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* =========================
           NAVBAR
        ========================= */

        .notification-navbar {
            width: 100%;
            height: 72px;

            background: rgba(255, 255, 255, 0.96);

            border-bottom: 1px solid #ffe4da;

            padding: 15px 7%;

            display: flex;
            align-items: center;
            justify-content: space-between;

            position: sticky;
            top: 0;
            z-index: 100;

            backdrop-filter: blur(12px);
        }

        .brand {
            font-family: 'Baloo 2', sans-serif;

            font-size: 24px;
            font-weight: 800;

            color: #e8420f;
        }

        .brand span {
            color: #172033;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            padding: 9px 14px;

            border: 1px solid #f8d8ce;

            background: #fff;

            color: #e8420f;

            border-radius: 10px;

            font-size: 12px;
            font-weight: 800;

            transition: 0.2s ease;
        }

        .back-btn:hover {
            background: #fff1ec;
            transform: translateY(-1px);
        }

        /* =========================
           MAIN
        ========================= */

        .page {
            width: calc(100% - 40px);
            max-width: 900px;

            margin: 45px auto 80px;
        }

        /* =========================
           HEADER
        ========================= */

        .page-header {
            position: relative;

            background: linear-gradient(
                135deg,
                #e8420f,
                #f56a36
            );

            color: white;

            border-radius: 22px;

            padding: 30px 32px;

            margin-bottom: 24px;

            overflow: hidden;

            box-shadow:
                0 14px 35px rgba(232, 66, 15, 0.16);
        }

        .page-header::before {
            content: "";

            position: absolute;

            width: 180px;
            height: 180px;

            right: -55px;
            top: -75px;

            border-radius: 50%;

            background: rgba(255, 255, 255, 0.13);
        }

        .page-header::after {
            content: "";

            position: absolute;

            width: 100px;
            height: 100px;

            right: 95px;
            bottom: -55px;

            border-radius: 50%;

            background: rgba(255, 255, 255, 0.08);
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .header-icon {
            width: 48px;
            height: 48px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: rgba(255, 255, 255, 0.18);

            border: 1px solid rgba(255, 255, 255, 0.25);

            border-radius: 14px;

            font-size: 23px;

            margin-bottom: 14px;
        }

        .page-header h1 {
            font-family: 'Baloo 2', sans-serif;

            font-size: 32px;
            line-height: 1.1;

            margin-bottom: 5px;
        }

        .page-header p {
            font-size: 13px;

            color: rgba(255, 255, 255, 0.88);
        }

        /* =========================
           NOTIFICATION LIST
        ========================= */

        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .notification-card {
            position: relative;

            display: flex;
            align-items: flex-start;

            gap: 15px;

            background: white;

            border: 1px solid #f4dfd8;

            border-radius: 16px;

            padding: 18px 20px;

            box-shadow:
                0 5px 18px rgba(31, 41, 55, 0.045);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }

        .notification-card:hover {
            transform: translateY(-2px);

            border-color: #f3c9bc;

            box-shadow:
                0 10px 28px rgba(232, 66, 15, 0.08);
        }

        /* UNREAD */

        .notification-card.unread {
            background: #fffaf8;

            border-color: #f6cabb;

            box-shadow:
                0 7px 22px rgba(232, 66, 15, 0.07);
        }

        .notification-card.unread::before {
            content: "";

            position: absolute;

            left: 0;
            top: 16px;
            bottom: 16px;

            width: 4px;

            background: #e8420f;

            border-radius: 0 5px 5px 0;
        }

        /* ICON */

        .notification-icon {
            width: 46px;
            height: 46px;

            min-width: 46px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 14px;

            background: #fff0eb;

            color: #e8420f;

            font-size: 20px;

            border: 1px solid #ffe0d6;
        }

        .notification-card.read .notification-icon {
            background: #fff7f4;

            border-color: #f4e5df;

            opacity: 0.75;
        }

        /* CONTENT */

        .notification-content {
            flex: 1;

            min-width: 0;
        }

        .notification-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 15px;

            margin-bottom: 6px;
        }

        .notification-title {
            font-size: 15px;

            font-weight: 800;

            color: #172033;

            line-height: 1.4;
        }

        .notification-card.read .notification-title {
            font-weight: 700;
        }

        .notification-time {
            flex-shrink: 0;

            color: #a88f87;

            font-size: 10px;
            font-weight: 600;

            white-space: nowrap;
        }

        .notification-message {
            color: #75645f;

            font-size: 13px;

            line-height: 1.6;
        }

        /* TYPE */

        .notification-bottom {
            display: flex;
            align-items: center;

            gap: 8px;

            margin-top: 12px;
        }

        .notification-type {
            display: inline-flex;
            align-items: center;

            padding: 4px 9px;

            background: #fff1ec;

            color: #d94713;

            border-radius: 999px;

            font-size: 9px;
            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.5px;
        }

        .unread-dot {
            width: 7px;
            height: 7px;

            background: #e8420f;

            border-radius: 50%;

            box-shadow:
                0 0 0 4px rgba(232, 66, 15, 0.08);
        }

        /* =========================
           EMPTY STATE
        ========================= */

        .empty {
            background: white;

            border: 1px solid #f4dfd8;

            border-radius: 20px;

            padding: 60px 25px;

            text-align: center;

            box-shadow:
                0 7px 22px rgba(31, 41, 55, 0.04);
        }

        .empty-icon {
            width: 70px;
            height: 70px;

            margin: 0 auto 16px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #fff0eb;

            border: 1px solid #ffe0d6;

            border-radius: 20px;

            font-size: 30px;
        }

        .empty h3 {
            font-family: 'Baloo 2', sans-serif;

            font-size: 21px;

            color: #172033;

            margin-bottom: 5px;
        }

        .empty p {
            color: #977970;

            font-size: 12px;
        }

        /* =========================
           FOOTER
        ========================= */

        .footer {
            text-align: center;

            color: #a88f87;

            font-size: 11px;

            margin-top: 35px;
        }

        .footer strong {
            color: #e8420f;
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 650px) {

            .notification-navbar {
                padding: 14px 18px;
            }

            .page {
                width: calc(100% - 24px);

                margin-top: 25px;
            }

            .page-header {
                padding: 25px 22px;

                border-radius: 18px;
            }

            .page-header h1 {
                font-size: 27px;
            }

            .notification-card {
                padding: 16px;
            }

            .notification-top {
                flex-direction: column;

                gap: 3px;
            }

            .notification-time {
                white-space: normal;
            }
        }

        @media (max-width: 430px) {

            .brand {
                font-size: 21px;
            }

            .back-btn {
                padding: 8px 10px;
            }

            .notification-card {
                gap: 11px;
            }

            .notification-icon {
                width: 40px;
                height: 40px;

                min-width: 40px;

                font-size: 17px;
            }

            .notification-title {
                font-size: 14px;
            }

            .notification-message {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

    <!-- =========================
         NAVBAR
    ========================= -->

    <nav class="notification-navbar">

        <a href="{{ route('buyer.dashboard') }}" class="brand">
            Boom<span>Buy</span>
        </a>

        <a
            href="{{ route('buyer.dashboard') }}"
            class="back-btn"
        >
            ← Back to Home
        </a>

    </nav>


    <!-- =========================
         MAIN
    ========================= -->

    <main class="page">

        <!-- HEADER -->

        <section class="page-header">

            <div class="header-content">

                <div class="header-icon">
                    🔔
                </div>

                <h1>
                    Notifications
                </h1>

                <p>
                    Stay updated with your latest BoomBuy activities.
                </p>

            </div>

        </section>


        <!-- NOTIFICATIONS -->

        @if ($notifications->count())

            <div class="notification-list">

                @foreach ($notifications as $notification)

                    @php

                        $icon = match($notification->type) {

                            'order' => '📦',

                            'delivery' => '🚚',

                            'payment' => '💳',

                            'return_refund' => '↩️',

                            'seller' => '🏪',

                            'rider' => '🛵',

                            'system' => '🔔',

                            default => '🔔',

                        };

                    @endphp


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
            {{ $icon }}
        </div>

        <div class="notification-content">

            <div class="notification-top">

                <div class="notification-title">
                    {{ $notification->title }}
                </div>

                <div class="notification-time">
                    {{ $notification->created_at?->format('M d, Y • h:i A') }}
                </div>

            </div>

            <div class="notification-message">
                {{ $notification->message }}
            </div>

            <div class="notification-bottom">

                @if(!$notification->read_at)
                    <span class="unread-dot"></span>
                @endif

                @if($notification->type)
                    <span class="notification-type {{ $notification->read_at ? 'read-type' : '' }}">
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
                @endforeach

            </div>

        @else

            <div class="empty">

                <div class="empty-icon">
                    🔔
                </div>

                <h3>
                    You're all caught up!
                </h3>

                <p>
                    No new notifications for now.
                </p>

            </div>

        @endif


        <div class="footer">
            © 2026 <strong>BoomBuy</strong> — Your Marketplace for Everything
        </div>

    </main>

</body>
</html>