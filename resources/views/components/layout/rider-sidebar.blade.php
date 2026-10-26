@props(['active' => null, 'user' => []])

<aside class="rider-sidebar bb-portal-side">

    <div class="rider-sidebar-top">

        <a href="{{ route('rider.dashboard') }}" class="bb-side-brand">
            <img src="{{ vasset('images/icon.svg') }}" alt="" class="bb-side-icon" width="36" height="36">
            <span class="bb-side-name"><img src="{{ vasset('images/logo-wordmark.svg') }}" alt="BoomBuy" class="bb-side-wordmark" width="131" height="46"> <small>Rider</small></span>
        </a>

        <nav class="rider-sidebar-nav">

            <a href="{{ route('rider.dashboard') }}" class="rider-sidebar-link {{ $active === 'dashboard' ? 'active' : '' }}">
                <span class="sidebar-icon"><i class="bi bi-house-door-fill"></i></span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('rider.pickups') }}" class="rider-sidebar-link {{ $active === 'pickups' ? 'active' : '' }}">
                <span class="sidebar-icon"><i class="bi bi-box-arrow-in-down"></i></span>
                <span>Items for Pickup</span>
            </a>

            <a href="{{ route('rider.deliveries') }}" class="rider-sidebar-link {{ $active === 'deliveries' ? 'active' : '' }}">
                <span class="sidebar-icon"><i class="bi bi-truck"></i></span>
                <span>My Deliveries</span>
            </a>

            <a href="{{ route('rider.profit') }}" class="rider-sidebar-link {{ $active === 'profit' ? 'active' : '' }}">
                <span class="sidebar-icon"><i class="bi bi-cash-stack"></i></span>
                <span>Profit</span>
            </a>

            <a href="{{ route('rider.deliveries.history') }}" class="rider-sidebar-link {{ $active === 'history' ? 'active' : '' }}">
                <span class="sidebar-icon"><i class="bi bi-clock-history"></i></span>
                <span>Delivery History</span>
            </a>

            <a href="{{ route('rider.profile') }}" class="rider-sidebar-link {{ $active === 'profile' ? 'active' : '' }}">
                <span class="sidebar-icon"><i class="bi bi-person-fill"></i></span>
                <span>My Profile</span>
            </a>

            <a href="{{ route('rider.notifications') }}" class="rider-sidebar-link {{ $active === 'notifications' ? 'active' : '' }}" aria-label="Notifications" title="Notifications">
                <span class="sidebar-icon"><i class="bi bi-bell-fill"></i></span>
                <span>Notifications</span>

                @if($riderUnreadNotifications > 0)
                    <span class="sidebar-notification-badge">{{ $riderUnreadNotifications }}</span>
                @endif
            </a>

            <a href="{{ route('complaints.index') }}" class="rider-sidebar-link {{ $active === 'complaints' ? 'active' : '' }}">
                <span class="sidebar-icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
                <span>Complaints</span>
            </a>

            <a href="{{ route('messages.index') }}" class="rider-sidebar-link {{ $active === 'messages' ? 'active' : '' }}">
                <span class="sidebar-icon"><i class="bi bi-chat-dots-fill"></i></span>
                <span>Messages</span>

                @if($riderUnreadMessages > 0)
                    <span class="sidebar-notification-badge">{{ $riderUnreadMessages }}</span>
                @endif
            </a>
        </nav>

    </div>

    <div class="rider-sidebar-bottom">

        <div class="bb-side-user">
            <span class="bb-side-avatar">{{ mb_strtoupper(mb_substr($user['name'] ?? 'R', 0, 1)) }}</span>
            <span class="bb-side-who">
                <strong>{{ $user['name'] ?? 'Rider' }}</strong>
                <small>Rider</small>
            </span>
            <form action="{{ route('logout') }}" method="POST" class="bb-side-logout-form" onsubmit="return bbConfirmSubmit(event, this, 'Are you sure you want to log out?');">
                @csrf
                <button type="submit" class="bb-side-logout" aria-label="Log out" title="Log out">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
            </form>
        </div>

    </div>

</aside>

@php
    // Phones get a bottom tab bar instead of the icon rail; the rest of the menu sits under "More".
    $rTab = match ($active) {
        'dashboard' => 'home',
        'pickups' => 'pickups',
        'deliveries' => 'deliveries',
        'profit' => 'profit',
        default => 'more',
    };
    $rCount = fn ($n) => $n > 99 ? '99+' : $n;
    $rUnread = $riderUnreadNotifications + $riderUnreadMessages;
    $rDockI = array_search($rTab, ['home', 'pickups', 'deliveries', 'profit', 'more'], true);
@endphp

{{-- 📱 Bottom tab bar on phones --}}
<link rel="stylesheet" href="{{ vasset('css/partials/phone-tabbar.css') }}">
<nav class="bb-dock has-bubble" data-dock="rider" style="--dock-i: {{ (int) $rDockI }}" aria-label="Main">
    <span class="bb-dock-bubble" aria-hidden="true"></span>
    <a href="{{ route('rider.dashboard') }}" data-dock-tab @class(['active is-lift' => $rTab === 'home']) @if($rTab === 'home') aria-current="page" @endif>
        <span class="bb-dock-ico"><i class="bi {{ $rTab === 'home' ? 'bi-house-door-fill' : 'bi-house-door' }}"></i></span>Home
    </a>
    <a href="{{ route('rider.pickups') }}" data-dock-tab @class(['active is-lift' => $rTab === 'pickups']) @if($rTab === 'pickups') aria-current="page" @endif>
        <span class="bb-dock-ico"><i class="bi bi-box-arrow-in-down"></i></span>Pickups
    </a>
    <a href="{{ route('rider.deliveries') }}" data-dock-tab @class(['active is-lift' => $rTab === 'deliveries']) @if($rTab === 'deliveries') aria-current="page" @endif>
        <span class="bb-dock-ico"><i class="bi {{ $rTab === 'deliveries' ? 'bi-truck-front-fill' : 'bi-truck' }}"></i></span>Deliveries
    </a>
    <a href="{{ route('rider.profit') }}" data-dock-tab @class(['active is-lift' => $rTab === 'profit']) @if($rTab === 'profit') aria-current="page" @endif>
        <span class="bb-dock-ico"><i class="bi bi-cash-stack"></i></span>Profit
    </a>
    <button type="button" data-dock-tab @class(['active is-lift' => $rTab === 'more']) data-smore-open aria-haspopup="dialog" aria-controls="bbSmore" aria-label="More{{ $rUnread > 0 ? ' (' . $rUnread . ' unread)' : '' }}">
        <span class="bb-dock-ico">
            <i class="bi bi-list"></i>
            @if($rUnread > 0)
                <span class="bb-dock-dot"></span>
            @endif
        </span>More
    </button>
</nav>
@include('partials.phone-dock-script')

<div class="bb-smore" id="bbSmore" role="dialog" aria-modal="true" aria-label="More" hidden>
    <div class="bb-smore-backdrop" data-smore-close></div>
    <div class="bb-smore-sheet">
        <div class="bb-smore-head">
            <span class="bb-side-avatar">{{ mb_strtoupper(mb_substr($user['name'] ?? 'R', 0, 1)) }}</span>
            <span class="bb-smore-who"><strong>{{ $user['name'] ?? 'Rider' }}</strong><small>Rider</small></span>
            <button type="button" class="bb-smore-x" data-smore-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="bb-smore-grid">
            <a href="{{ route('messages.index') }}" @class(['active' => $active === 'messages'])>
                <i class="bi bi-chat-dots-fill"></i>Messages
                @if($riderUnreadMessages > 0)
                    <span class="bb-stabbar-count">{{ $rCount($riderUnreadMessages) }}</span>
                @endif
            </a>
            <a href="{{ route('rider.notifications') }}" @class(['active' => $active === 'notifications'])>
                <i class="bi bi-bell-fill"></i>Notifications
                @if($riderUnreadNotifications > 0)
                    <span class="bb-stabbar-count">{{ $rCount($riderUnreadNotifications) }}</span>
                @endif
            </a>
            <a href="{{ route('rider.deliveries.history') }}" @class(['active' => $active === 'history'])><i class="bi bi-clock-history"></i>History</a>
            <a href="{{ route('complaints.index') }}" @class(['active' => $active === 'complaints'])><i class="bi bi-exclamation-triangle-fill"></i>Complaints</a>
            <a href="{{ route('rider.profile') }}" @class(['active' => $active === 'profile'])><i class="bi bi-person-fill"></i>My Profile</a>
            <form action="{{ route('logout') }}" method="POST" onsubmit="return bbConfirmSubmit(event, this, 'Are you sure you want to log out?');">
                @csrf
                <button type="submit"><i class="bi bi-box-arrow-right"></i>Log out</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.body.classList.add('has-bb-stabbar');

    (function () {
        var sheet = document.getElementById('bbSmore');
        var opener = document.querySelector('[data-smore-open]');
        if (!sheet || !opener) return;

        function close() {
            sheet.hidden = true;
            if (window.bbDock) window.bbDock.reset();
            document.body.classList.remove('bb-smore-open');
            opener.focus();
        }

        opener.addEventListener('click', function () {
            sheet.hidden = false;
            document.body.classList.add('bb-smore-open');
            sheet.querySelector('.bb-smore-x').focus();
        });

        sheet.addEventListener('click', function (e) {
            if (e.target.closest('[data-smore-close]')) close();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !sheet.hidden) close();
        });
    })();
</script>

@include('partials.confirm-modal')
@include('partials.rider-location', ['user' => $user])
