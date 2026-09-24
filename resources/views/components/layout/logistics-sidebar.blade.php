@props(['active' => null, 'user' => []])

@php
    $logisticsUnreadNotifications = \App\Models\Notification::where(
        'user_id',
        $user['id'] ?? null
    )->whereNull('read_at')->count();
@endphp

<aside class="logistics-sidebar">

    <div class="logistics-sidebar-top">

        <a href="{{ route('logistics.dashboard') }}" class="logistics-brand">

            <div class="logistics-brand-icon">📦</div>

            <div>
                <div class="logistics-brand-name">BoomBuy</div>
                <div class="logistics-brand-role">Logistics Center</div>
            </div>

        </a>

        <nav class="logistics-sidebar-nav">

            <a href="{{ route('logistics.dashboard') }}" class="logistics-sidebar-link {{ $active === 'dashboard' ? 'active' : '' }}">
                <span class="sidebar-icon">🏠</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('logistics.riders') }}" class="logistics-sidebar-link {{ $active === 'riders' ? 'active' : '' }}">
                <span class="sidebar-icon">🛵</span>
                <span>Rider Management</span>
            </a>

            <a href="{{ route('logistics.parcels') }}" class="logistics-sidebar-link {{ $active === 'parcels' ? 'active' : '' }}">
                <span class="sidebar-icon">📦</span>
                <span>Incoming Parcels</span>
            </a>

            <a href="{{ route('logistics.profile') }}" class="logistics-sidebar-link {{ $active === 'profile' ? 'active' : '' }}">
                <span class="sidebar-icon">👤</span>
                <span>My Profile</span>
            </a>

            <a href="{{ route('logistics.notifications') }}" class="logistics-sidebar-link {{ $active === 'notifications' ? 'active' : '' }}">
                <span class="sidebar-icon">🔔</span>
                <span>Notifications</span>
                @if($logisticsUnreadNotifications > 0)
                    <span class="sidebar-notification-badge">
                        {{ $logisticsUnreadNotifications > 99 ? '99+' : $logisticsUnreadNotifications }}
                    </span>
                @endif
            </a>

            <a href="{{ route('messages.index') }}" class="logistics-sidebar-link {{ $active === 'messages' ? 'active' : '' }}">
                <span class="sidebar-icon">💬</span>
                <span>Messages</span>
            </a>

            <a href="{{ url('/') }}" class="logistics-sidebar-link">
                <span class="sidebar-icon">🛒</span>
                <span>Store</span>
            </a>

        </nav>

    </div>

    <div class="logistics-sidebar-bottom">

        <div class="logistics-sidebar-divider"></div>

        <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Are you sure you want to log out?');">
            @csrf
            <button type="submit" class="logistics-sidebar-logout">
                <span class="sidebar-icon">🚪</span>
                <span>Logout</span>
            </button>
        </form>

    </div>

</aside>
