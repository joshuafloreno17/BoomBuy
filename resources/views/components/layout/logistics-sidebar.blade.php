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
        </div>

        <form action="{{ route('logout') }}" method="POST" onsubmit="return bbConfirmSubmit(event, this, 'Are you sure you want to log out?');">
            @csrf
            <button type="submit" class="logistics-sidebar-logout">
                <span class="sidebar-icon"><i class="bi bi-box-arrow-right"></i></span>
                <span>Log out</span>
            </button>
        </form>

    </div>

</aside>

@include('partials.confirm-modal')
