@props(['active' => null, 'user' => []])

<aside class="logistics-sidebar bb-portal-side">

    <div class="logistics-sidebar-top">

        <a href="{{ route('logistics.dashboard') }}" class="bb-side-brand">
            <img src="{{ asset('images/icon.svg') }}" alt="" width="36" height="36">
            <span class="bb-side-name">BoomBuy <small>Logistics</small></span>
        </a>

        <nav class="logistics-sidebar-nav">

            <a href="{{ route('logistics.dashboard') }}" class="logistics-sidebar-link {{ $active === 'dashboard' ? 'active' : '' }}">
                <span class="sidebar-icon"><i class="bi bi-house-door-fill"></i></span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('logistics.riders') }}" class="logistics-sidebar-link {{ $active === 'riders' ? 'active' : '' }}">
                <span class="sidebar-icon"><i class="bi bi-bicycle"></i></span>
                <span>Rider Management</span>
            </a>

            <a href="{{ route('logistics.parcels') }}" class="logistics-sidebar-link {{ $active === 'parcels' ? 'active' : '' }}">
                <span class="sidebar-icon"><i class="bi bi-box-seam-fill"></i></span>
                <span>Incoming Parcels</span>
            </a>

            <a href="{{ route('logistics.profile') }}" class="logistics-sidebar-link {{ $active === 'profile' ? 'active' : '' }}">
                <span class="sidebar-icon"><i class="bi bi-person-fill"></i></span>
                <span>My Profile</span>
            </a>

            <a href="{{ route('logistics.notifications') }}" class="logistics-sidebar-link {{ $active === 'notifications' ? 'active' : '' }}" aria-label="Notifications" title="Notifications">
                <span class="sidebar-icon"><i class="bi bi-bell-fill"></i></span>
                <span>Notifications</span>
                @if($logisticsUnreadNotifications > 0)
                    <span class="sidebar-notification-badge">
                        {{ $logisticsUnreadNotifications > 99 ? '99+' : $logisticsUnreadNotifications }}
                    </span>
                @endif
            </a>

            <a href="{{ route('messages.index') }}" class="logistics-sidebar-link {{ $active === 'messages' ? 'active' : '' }}">
                <span class="sidebar-icon"><i class="bi bi-chat-dots-fill"></i></span>
                <span>Messages</span>
                @if($logisticsUnreadMessages > 0)
                    <span class="sidebar-notification-badge">
                        {{ $logisticsUnreadMessages > 99 ? '99+' : $logisticsUnreadMessages }}
                    </span>
                @endif
            </a>
        </nav>

    </div>

    <div class="logistics-sidebar-bottom">

        <div class="bb-side-user">
            <span class="bb-side-avatar">{{ mb_strtoupper(mb_substr($user['name'] ?? 'L', 0, 1)) }}</span>
            <span class="bb-side-who">
                <strong>{{ $user['name'] ?? 'Logistics' }}</strong>
                <small>Logistics staff</small>
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
