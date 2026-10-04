<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Notifications | BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/notifications.css') }}">
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
        <a href="{{ route('buyer.dashboard') }}" class="brand"><img src="{{ asset('images/icon.svg') }}" alt="" class="bb-logo-mark" width="32" height="32"><span class="bb-logo-word">BoomBuy</span></a>
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
