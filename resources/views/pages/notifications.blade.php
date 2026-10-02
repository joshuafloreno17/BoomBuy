<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications | BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: var(--font-body);
            background: var(--cream);
            color: var(--ink);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* Only for non-buyers who end up here. */
        .notification-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px;
            background: #fff;
            border-bottom: 1px solid var(--line);
        }

        .notification-navbar .brand {
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 800;
            color: var(--accent);
        }

        .notification-navbar .brand span {
            color: var(--ink);
        }

        .notification-navbar .back-btn {
            font-size: 13px;
            font-weight: 700;
            color: var(--accent-dark);
        }

        /* =========================
           PAGE
        ========================= */

        .page {
            width: calc(100% - 40px);
            max-width: 860px;
            flex: 1 0 auto;
            margin: 28px auto 64px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .page-crumbs {
            font-size: 12.5px;
            font-weight: 600;
            color: #6f5a53;
        }

        .page-crumbs a:hover {
            color: var(--accent-dark);
        }

        .page-crumbs span {
            color: var(--ink);
        }

        .page-head {
            margin-top: 6px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-title {
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 10px 12px;
        }

        .page-title h1 {
            font-family: var(--font-display);
            font-size: 34px;
            font-weight: 800;
            line-height: 1.1;
        }

        .page-count {
            font-size: 14px;
            font-weight: 600;
            color: #6f5a53;
        }

        .mark-all button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 38px;
            padding: 0 14px;
            border: 1px solid #f0d9d1;
            border-radius: 10px;
            background: #fff;
            color: var(--accent-dark);
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .mark-all button:hover {
            background: #fff4f0;
            border-color: #e8b5a4;
        }

        .page-alert {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 11px 14px;
            border-radius: 12px;
            background: var(--teal-bg);
            color: var(--teal-dark);
            font-size: 13px;
            font-weight: 600;
        }

        /* =========================
           FILTER CHIPS
        ========================= */

        .chip-row {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            scrollbar-width: thin;
        }

        .chip {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 16px 0 13px;
            border-radius: 999px;
            border: 1px solid #f0d9d1;
            background: #fff;
            color: var(--ink);
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            transition: background 0.15s ease, border-color 0.15s ease;
        }

        .chip:hover {
            border-color: #e8b5a4;
            background: #fffaf8;
        }

        .chip i {
            color: var(--accent-dark);
        }

        .chip small {
            font-size: 11.5px;
            font-weight: 700;
            opacity: 0.65;
        }

        .chip.active {
            background: var(--ink);
            border-color: var(--ink);
            color: #fff;
        }

        .chip.active i {
            color: #fff;
        }

        /* =========================
           LIST
        ========================= */

        .day-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .day-group + .day-group {
            margin-top: 10px;
        }

        .day-label {
            font-size: 11.5px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .day-list {
            background: #fff;
            border: 1px solid #f6e1db;
            border-radius: 16px;
            overflow: hidden;
        }

        .note {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 16px 18px;
            transition: background 0.15s ease;
        }

        .note + .note {
            border-top: 1px solid #f8ebe7;
        }

        .note:hover {
            background: #fffaf8;
        }

        .note.unread {
            background: #fff5f1;
        }

        .note.unread:hover {
            background: #ffefe8;
        }

        .note-icon {
            flex-shrink: 0;
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #fff0eb;
            color: var(--accent);
            font-size: 18px;
        }

        .note-icon.is-return { background: #fff6e0; color: #b7791f; }
        .note-icon.is-account { background: #eef2ff; color: #4f46e5; }
        .note-icon.is-delivery { background: var(--teal-bg); color: var(--teal-dark); }

        .note-body {
            display: block;
            flex: 1;
            min-width: 0;
        }

        .note-top {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
        }

        .note-title {
            font-size: 14.5px;
            font-weight: 600;
            color: var(--ink);
        }

        .note.unread .note-title {
            font-weight: 800;
        }

        .note-time {
            flex-shrink: 0;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted-2);
            white-space: nowrap;
        }

        .note.unread .note-time {
            color: var(--accent-dark);
        }

        .note-message {
            display: block;
            margin-top: 3px;
            font-size: 13px;
            line-height: 1.5;
            color: #6f5a53;
            overflow-wrap: anywhere;
        }

        .note-side {
            flex-shrink: 0;
            align-self: center;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .unread-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--accent);
        }

        .note-go {
            color: #c9aaa0;
            font-size: 14px;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .note:hover .note-go {
            color: var(--accent);
            transform: translateX(3px);
        }

        /* =========================
           EMPTY
        ========================= */

        .empty {
            padding: 48px 20px;
            text-align: center;
            background: #fff;
            border: 1px solid #f6e1db;
            border-radius: 16px;
        }

        .empty-icon {
            display: inline-grid;
            place-items: center;
            width: 64px;
            height: 64px;
            margin-bottom: 14px;
            border-radius: 18px;
            background: #fff0eb;
            color: var(--accent);
            font-size: 28px;
        }

        .empty h3 {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 800;
        }

        .empty p {
            margin-top: 4px;
            font-size: 13.5px;
            color: #6f5a53;
        }

        .empty a {
            display: inline-flex;
            margin-top: 16px;
            padding: 10px 16px;
            border-radius: 10px;
            background: var(--accent);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
        }

        /* =========================
           PHONES
        ========================= */

        @media (max-width: 650px) {
            .page {
                width: calc(100% - 32px);
                margin-top: 16px;
                gap: 14px;
            }

            .page-crumbs {
                display: none;
            }

            .page-title h1 {
                font-size: 25px;
            }

            .chip-row {
                margin-right: -16px;
                padding-right: 16px;
            }

            .note {
                gap: 12px;
                padding: 14px;
            }

            .note-icon {
                width: 38px;
                height: 38px;
                font-size: 16px;
            }

            .note-title {
                font-size: 14px;
            }

            .note-message {
                font-size: 12.5px;
            }

            .note-go {
                display: none;
            }
        }
    </style>
</head>

<body>

@php
    $isBuyer = (session('user.role') ?? null) === 'buyer';

    $chips = [
        'all' => ['All', 'bi-bell-fill'],
        'unread' => ['Unread', 'bi-circle-fill'],
        'orders' => ['Orders', 'bi-box-seam-fill'],
        'returns' => ['Returns', 'bi-arrow-return-left'],
        'account' => ['Account', 'bi-person-fill'],
    ];

    // Today / Yesterday / the date, for the notifications on this page.
    $groups = $notifications->getCollection()->groupBy(function ($n) {
        $day = $n->created_at;
        return match (true) {
            !$day => 'Earlier',
            $day->isToday() => 'Today',
            $day->isYesterday() => 'Yesterday',
            $day->isCurrentYear() => $day->format('F j'),
            default => $day->format('F j, Y'),
        };
    });

    $tone = fn ($type) => match ($type) {
        'return_refund' => 'is-return',
        'account_status', 'complaint', 'buyer' => 'is-account',
        'delivery', 'delivery_status' => 'is-delivery',
        default => '',
    };

    $emptyText = [
        'unread' => ["You're all caught up!", 'No unread notifications.'],
        'orders' => ['No order updates yet', 'Updates about your orders and deliveries show up here.'],
        'returns' => ['No return updates', 'Updates about your return and refund requests show up here.'],
        'account' => ['No account updates', 'Updates about your account and complaints show up here.'],
    ][$filter] ?? ['No notifications yet', "We'll let you know when something happens with your orders."];
@endphp

@if($isBuyer)
    @include('partials.buyer-navbar', ['activeNav' => 'notifications'])
@else
    <nav class="notification-navbar">
        <a href="{{ route('buyer.dashboard') }}" class="brand">Boom<span>Buy</span></a>
        <a href="{{ route('buyer.dashboard') }}" class="back-btn">← Back to Home</a>
    </nav>
@endif

<main class="page">

    <header>
        <nav class="page-crumbs" aria-label="Breadcrumb">
            <a href="{{ route('buyer.dashboard') }}">Home</a> / <span>Notifications</span>
        </nav>

        <div class="page-head">
            <div class="page-title">
                <h1>Notifications</h1>
                <span class="page-count">
                    {{ $unreadCount > 0 ? number_format($unreadCount) . ' unread' : 'All caught up' }}
                </span>
            </div>

            @if($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}" class="mark-all">
                    @csrf
                    <button type="submit"><i class="bi bi-check2-all"></i> Mark all as read</button>
                </form>
            @endif
        </div>
    </header>

    @if(session('success'))
        <div class="page-alert" role="status"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
    @endif

    {{-- Kinds the buyer has none of stay hidden (unless picked). --}}
    <nav class="chip-row" aria-label="Filter notifications">
        @foreach($chips as $key => [$label, $icon])
            @continue(!in_array($key, ['all', 'unread']) && !$counts[$key] && $filter !== $key)
            <a
                href="{{ route('notifications', $key === 'all' ? [] : ['filter' => $key]) }}"
                class="chip {{ $filter === $key ? 'active' : '' }}"
                @if($filter === $key) aria-current="true" @endif
            >
                <i class="bi {{ $icon }}" @if($key === 'unread') style="font-size:8px" @endif></i>
                {{ $label }} <small>{{ number_format($counts[$key]) }}</small>
            </a>
        @endforeach
    </nav>

    @if($notifications->count())

        @foreach($groups as $day => $items)
            <section class="day-group">
                <h2 class="day-label">{{ $day }}</h2>

                <div class="day-list">
                    @foreach($items as $notification)
                        {{-- Opens what the notification is about (and marks it read). --}}
                        <a
                            href="{{ route('notifications.open', $notification->id) }}"
                            class="note {{ $notification->read_at ? 'read' : 'unread' }}"
                        >
                            <span class="note-icon {{ $tone($notification->type) }}">
                                <i class="bi {{ $notification->iconClass() }}"></i>
                            </span>

                            <span class="note-body">
                                <span class="note-top">
                                    <span class="note-title">{{ $notification->title }}</span>
                                    <time class="note-time" datetime="{{ $notification->created_at?->toIso8601String() }}" title="{{ $notification->created_at?->format('M d, Y • h:i A') }}">
                                        @if($notification->created_at?->isToday())
                                            {{ $notification->created_at->diffForHumans(null, true, true) }} ago
                                        @else
                                            {{ $notification->created_at?->format('g:i A') }}
                                        @endif
                                    </time>
                                </span>
                                <span class="note-message">{{ $notification->message }}</span>
                            </span>

                            <span class="note-side">
                                @unless($notification->read_at)
                                    <span class="unread-dot" aria-label="Unread"></span>
                                @endunless
                                <i class="bi bi-chevron-right note-go"></i>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach

        @include('partials.simple-pager', ['paginator' => $notifications])

    @else

        <div class="empty">
            <div class="empty-icon"><i class="bi bi-bell-fill"></i></div>
            <h3>{{ $emptyText[0] }}</h3>
            <p>{{ $emptyText[1] }}</p>
            @if($filter !== 'all')
                <a href="{{ route('notifications') }}">See all notifications</a>
            @endif
        </div>

    @endif

</main>

@include('partials.buyer-footer')

<script>
    // On phones the chip row scrolls sideways — keep the picked chip in view.
    (function () {
        var row = document.querySelector('.chip-row');
        var active = row && row.querySelector('.chip.active');
        if (active && row.scrollWidth > row.clientWidth) {
            row.scrollLeft = active.offsetLeft - row.offsetLeft - 16;
        }
    })();
</script>

</body>
</html>
