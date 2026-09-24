@props(['active' => null, 'user' => []])

@php
    $riderUnreadNotifications = \App\Models\Notification::where('user_id', $user['id'] ?? null)
        ->whereNull('read_at')
        ->count();
@endphp

<aside class="rider-sidebar">

    <div class="rider-sidebar-top">

        <a href="{{ route('rider.dashboard') }}" class="rider-brand">

            <div class="rider-brand-icon">🛍️</div>

            <div>
                <div class="rider-brand-name">BoomBuy</div>
                <div class="rider-brand-role">Rider Center</div>
            </div>

        </a>

        <nav class="rider-sidebar-nav">

            <a href="{{ route('rider.dashboard') }}" class="rider-sidebar-link {{ $active === 'dashboard' ? 'active' : '' }}">
                <span class="sidebar-icon">🏠</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('rider.deliveries') }}" class="rider-sidebar-link {{ $active === 'deliveries' ? 'active' : '' }}">
                <span class="sidebar-icon">🚚</span>
                <span>My Deliveries</span>
            </a>

            <a href="{{ route('rider.profit') }}" class="rider-sidebar-link {{ $active === 'profit' ? 'active' : '' }}">
                <span class="sidebar-icon">💰</span>
                <span>Profit</span>
            </a>

            <a href="{{ route('rider.profile') }}" class="rider-sidebar-link {{ $active === 'profile' ? 'active' : '' }}">
                <span class="sidebar-icon">👤</span>
                <span>My Profile</span>
            </a>

            <a href="{{ route('rider.notifications') }}" class="rider-sidebar-link {{ $active === 'notifications' ? 'active' : '' }}">
                <span class="sidebar-icon">🔔</span>
                <span>Notifications</span>

                @if($riderUnreadNotifications > 0)
                    <span class="sidebar-notification-badge">{{ $riderUnreadNotifications }}</span>
                @endif
            </a>

            <a href="{{ route('complaints.index') }}" class="rider-sidebar-link {{ $active === 'complaints' ? 'active' : '' }}">
                <span class="sidebar-icon">⚠️</span>
                <span>Complaints</span>
            </a>

            <a href="{{ route('messages.index') }}" class="rider-sidebar-link {{ $active === 'messages' ? 'active' : '' }}">
                <span class="sidebar-icon">💬</span>
                <span>Messages</span>
            </a>

            <a href="{{ url('/') }}" class="rider-sidebar-link">
                <span class="sidebar-icon">🛒</span>
                <span>Store</span>
            </a>

        </nav>

    </div>

    <div class="rider-sidebar-bottom">

        <div class="rider-sidebar-divider"></div>

        <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Are you sure you want to log out?');">
            @csrf
            <button type="submit" class="rider-sidebar-logout">
                <span class="sidebar-icon">🚪</span>
                <span>Logout</span>
            </button>
        </form>

    </div>

</aside>
