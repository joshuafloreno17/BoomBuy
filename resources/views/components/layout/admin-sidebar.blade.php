@props(['active' => null])

<aside class="sidebar">

    <a href="{{ route('admin.dashboard') }}" class="bb-side-brand">
        <img src="{{ asset('images/icon.svg') }}" alt="" width="36" height="36">
        <span class="bb-side-name">BoomBuy <small>Admin</small></span>
    </a>

    <nav class="menu">

        <a href="{{ route('admin.dashboard') }}" @class(['active' => $active === 'dashboard'])>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span>Dashboard</span>
        </a>

        <div class="menu-group">People</div>

        <a href="{{ route('admin.buyers') }}" @class(['active' => $active === 'buyers'])>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <span>Buyers</span>
            @if($pendingApplications['buyer'] > 0)
                <span class="notification-badge">{{ $pendingApplications['buyer'] }}</span>
            @endif
        </a>

        <a href="{{ route('admin.sellers') }}" @class(['active' => $active === 'sellers'])>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l1-5h16l1 5"/><path d="M4 9v11h16V9"/><path d="M9 20v-6h6v6"/></svg>
            <span>Sellers</span>
            @if($pendingApplications['seller'] > 0)
                <span class="notification-badge">{{ $pendingApplications['seller'] }}</span>
            @endif
        </a>

        <a href="{{ route('admin.logistics') }}" @class(['active' => $active === 'logistics'])>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            <span>Logistics</span>
            @if($pendingApplications['logistics'] > 0)
                <span class="notification-badge">{{ $pendingApplications['logistics'] }}</span>
            @endif
        </a>

        <div class="menu-group">Marketplace</div>

        <a href="{{ route('admin.orders') }}" @class(['active' => $active === 'orders'])>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span>Orders</span>
        </a>

        <a href="{{ route('admin.products') }}" @class(['active' => $active === 'products'])>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l1-5h16l1 5"/><path d="M3 9a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0"/><path d="M4 9v9a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/></svg>
            <span>Products</span>
        </a>

        <a href="{{ route('admin.returns') }}" @class(['active' => $active === 'returns'])>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
            <span>Returns</span>

            @if($returnsWaitingCount > 0)
                <span class="notification-badge">
                    {{ $returnsWaitingCount > 99 ? '99+' : $returnsWaitingCount }}
                </span>
            @endif
        </a>

        <a href="{{ route('admin.reports') }}" @class(['active' => $active === 'reports'])>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            <span>Reports</span>
        </a>


        <div class="menu-group">Trust &amp; Safety</div>

        <a href="{{ route('admin.compliance') }}" @class(['active' => $active === 'compliance'])>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V4l-8 2-8-2v8c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>Compliance</span>
        </a>

        <a href="{{ route('admin.complaints') }}" @class(['active' => $active === 'complaints'])>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span>Complaints</span>

            @if($pendingComplaintsCount > 0)
                <span class="notification-badge">
                    {{ $pendingComplaintsCount > 99 ? '99+' : $pendingComplaintsCount }}
                </span>
            @endif
        </a>

        <div class="menu-group">Inbox</div>

        <a href="{{ route('messages.index') }}" @class(['active' => $active === 'messages'])>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span>Messages</span>

            @if($adminUnreadMessages > 0)
                <span class="notification-badge">
                    {{ $adminUnreadMessages > 99 ? '99+' : $adminUnreadMessages }}
                </span>
            @endif
        </a>

        <a href="{{ route('admin.notifications') }}" @class(['active' => $active === 'notifications']) aria-label="Notifications" title="Notifications">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <span>Notifications</span>

            @if($adminUnreadNotifications > 0)
                <span class="notification-badge">
                    {{ $adminUnreadNotifications > 99 ? '99+' : $adminUnreadNotifications }}
                </span>
            @endif
        </a>

        <div class="menu-group">System</div>

        <a href="{{ route('admin.settings') }}" @class(['active' => $active === 'settings'])>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
            <span>Settings</span>
        </a>

        <a href="{{ route('admin.account') }}" @class(['active' => $active === 'account'])>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>My Account</span>
        </a>

    </nav>

    <div class="logout">
        <div class="bb-side-user">
            @php
                $adminName = \App\Support\SupportAccount::user()?->name ?: 'Admin';
            @endphp
            <span class="bb-side-avatar">{{ strtoupper(mb_substr($adminName, 0, 1)) }}</span>
            <span class="bb-side-who">
                <strong>{{ $adminName }}</strong>
                <small>Administrator</small>
            </span>
            <form action="{{ route('admin.logout') }}" method="POST" class="bb-side-logout-form" onsubmit="return bbConfirmSubmit(event, this, 'Are you sure you want to log out?');">
                @csrf
                <button type="submit" class="bb-side-logout" aria-label="Log out" title="Log out">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
            </form>
        </div>
    </div>
</aside>

@include('partials.confirm-modal')

@once
    <link rel="stylesheet" href="{{ vasset('css/partials/panel-wide.css') }}">
@endonce
