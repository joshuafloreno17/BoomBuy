@props(['active' => null, 'user' => []])

<aside class="rider-sidebar bb-portal-side">

    <div class="rider-sidebar-top">

        <a href="{{ route('rider.dashboard') }}" class="bb-side-brand">
            <img src="{{ asset('images/icon.svg') }}" alt="" width="36" height="36">
            <span class="bb-side-name">BoomBuy <small>Rider</small></span>
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

@include('partials.confirm-modal')
