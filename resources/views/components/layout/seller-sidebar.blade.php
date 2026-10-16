@props([
    'active' => null,
    'user' => [],
])

<aside class="sidebar">

    <a href="{{ route('seller.dashboard') }}" class="bb-side-brand">
        <img src="{{ vasset('images/icon.svg') }}" alt="" class="bb-side-icon" width="36" height="36">
        <span class="bb-side-name"><img src="{{ vasset('images/logo-wordmark.svg') }}" alt="BoomBuy" class="bb-side-wordmark" width="131" height="46"> <small>Seller</small></span>
    </a>

    <nav class="menu">

        <a
            href="{{ route('seller.dashboard') }}"
            @class(['active' => $active === 'dashboard'])
        >
            <i class="bi bi-speedometer2"></i>
            <span class="label-text">
                Dashboard
            </span>
        </a>

        <a
            href="{{ route('seller.products.create') }}"
            @class(['active' => $active === 'products'])
        >
            <i class="bi bi-plus-circle-fill"></i>
            <span class="label-text">
                Add Product
            </span>
        </a>

        <a
            href="{{ route('seller.orders') }}"
            @class(['active' => $active === 'orders'])
        >
            <i class="bi bi-cart-fill"></i>
            <span class="label-text">
                Orders
            </span>

            @if($sellerOrdersToShip > 0)

                <span class="notification-badge">
                    {{ $sellerOrdersToShip > 99
                        ? '99+'
                        : $sellerOrdersToShip }}
                </span>

            @endif

        </a>

        <a
            href="{{ route('seller.reports') }}"
            @class(['active' => $active === 'reports'])
        >
            <i class="bi bi-bar-chart-fill"></i>
            <span class="label-text">
                Reports
            </span>
        </a>


        <a
            href="{{ route('seller.vouchers') }}"
            @class(['active' => $active === 'vouchers'])
        >
            <i class="bi bi-ticket-perforated-fill"></i>
            <span class="label-text">
                Vouchers
            </span>
        </a>

        <a
            href="{{ route('seller.reviews') }}"
            @class(['active' => $active === 'reviews'])
        >
            <i class="bi bi-star-fill"></i>
            <span class="label-text">
                Reviews
            </span>
        </a>

            <a
                href="{{ route('seller.notifications') }}"
                @class([
                    'active' => $active === 'notifications',
                ])
                aria-label="Notifications"
                title="Notifications"
            >
                <i class="bi bi-bell-fill"></i>
                <span class="label-text">
                    Notifications
                </span>

                @if($sellerUnreadNotifications > 0)

                    <span class="notification-badge">
                        {{ $sellerUnreadNotifications > 99
                            ? '99+'
                            : $sellerUnreadNotifications }}
                    </span>

                @endif

            </a>

        <a
            href="{{ route('complaints.index') }}"
            @class(['active' => $active === 'complaints'])
        >
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span class="label-text">
                Complaints
            </span>
        </a>

        <a
            href="{{ route('messages.index') }}"
            @class(['active' => $active === 'messages'])
        >
            <i class="bi bi-chat-dots-fill"></i>
            <span class="label-text">
                Messages
            </span>

            @if($sellerUnreadMessages > 0)

                <span class="notification-badge">
                    {{ $sellerUnreadMessages > 99
                        ? '99+'
                        : $sellerUnreadMessages }}
                </span>

            @endif

        </a>

        <a
            href="{{ route('seller.profile') }}"
            @class(['active' => $active === 'profile'])
        >
            <i class="bi bi-person-fill"></i>
            <span class="label-text">
                My Profile
            </span>
        </a>

    </nav>

    <div class="sidebar-footer">
        <div class="bb-side-user">
            <span class="bb-side-avatar">{{ mb_strtoupper(mb_substr($sellerCardName, 0, 1)) }}</span>
            <span class="bb-side-who">
                <strong>{{ $sellerCardName }}</strong>
                <small>Seller</small>
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
    $sTab = match ($active) {
        'dashboard' => 'home',
        'orders' => 'orders',
        'products' => 'products',
        'messages' => 'messages',
        default => 'more',
    };
    $sCount = fn ($n) => $n > 99 ? '99+' : $n;
@endphp

{{-- 📱 Bottom tab bar on phones --}}
<link rel="stylesheet" href="{{ vasset('css/partials/phone-tabbar.css') }}">
<nav class="bb-stabbar" aria-label="Main">
    <a href="{{ route('seller.dashboard') }}" @class(['active' => $sTab === 'home']) @if($sTab === 'home') aria-current="page" @endif>
        <i class="bi {{ $sTab === 'home' ? 'bi-house-door-fill' : 'bi-house-door' }}"></i>Home
    </a>
    <a href="{{ route('seller.orders') }}" @class(['active' => $sTab === 'orders']) @if($sTab === 'orders') aria-current="page" @endif aria-label="Orders{{ $sellerOrdersToShip > 0 ? ' (' . $sellerOrdersToShip . ' to ship)' : '' }}">
        @if($sellerOrdersToShip > 0)
            <span class="bb-stabbar-count">{{ $sCount($sellerOrdersToShip) }}</span>
        @endif
        <i class="bi {{ $sTab === 'orders' ? 'bi-box-seam-fill' : 'bi-box-seam' }}"></i>Orders
    </a>
    <a href="{{ route('seller.dashboard') }}#my-products" @class(['active' => $sTab === 'products']) @if($sTab === 'products') aria-current="page" @endif>
        <i class="bi {{ $sTab === 'products' ? 'bi-grid-fill' : 'bi-grid' }}"></i>Products
    </a>
    <a href="{{ route('messages.index') }}" @class(['active' => $sTab === 'messages']) @if($sTab === 'messages') aria-current="page" @endif aria-label="Chat{{ $sellerUnreadMessages > 0 ? ' (' . $sellerUnreadMessages . ' unread)' : '' }}">
        @if($sellerUnreadMessages > 0)
            <span class="bb-stabbar-count">{{ $sCount($sellerUnreadMessages) }}</span>
        @endif
        <i class="bi {{ $sTab === 'messages' ? 'bi-chat-dots-fill' : 'bi-chat-dots' }}"></i>Chat
    </a>
    <button type="button" @class(['active' => $sTab === 'more']) data-smore-open aria-haspopup="dialog" aria-controls="bbSmore" aria-label="More{{ $sellerUnreadNotifications > 0 ? ' (' . $sellerUnreadNotifications . ' unread notifications)' : '' }}">
        @if($sellerUnreadNotifications > 0)
            <span class="bb-stabbar-dot"></span>
        @endif
        <i class="bi bi-list"></i>More
    </button>
</nav>

<div class="bb-smore" id="bbSmore" role="dialog" aria-modal="true" aria-label="More" hidden>
    <div class="bb-smore-backdrop" data-smore-close></div>
    <div class="bb-smore-sheet">
        <div class="bb-smore-head">
            <span class="bb-side-avatar">{{ mb_strtoupper(mb_substr($sellerCardName, 0, 1)) }}</span>
            <span class="bb-smore-who"><strong>{{ $sellerCardName }}</strong><small>Seller</small></span>
            <button type="button" class="bb-smore-x" data-smore-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="bb-smore-grid">
            <a href="{{ route('seller.products.create') }}" @class(['active' => $active === 'products'])><i class="bi bi-plus-circle-fill"></i>Add product</a>
            <a href="{{ route('seller.notifications') }}" @class(['active' => $active === 'notifications'])>
                <i class="bi bi-bell-fill"></i>Notifications
                @if($sellerUnreadNotifications > 0)
                    <span class="bb-stabbar-count">{{ $sCount($sellerUnreadNotifications) }}</span>
                @endif
            </a>
            <a href="{{ route('seller.reports') }}" @class(['active' => $active === 'reports'])><i class="bi bi-bar-chart-fill"></i>Reports</a>
            <a href="{{ route('seller.vouchers') }}" @class(['active' => $active === 'vouchers'])><i class="bi bi-ticket-perforated-fill"></i>Vouchers</a>
            <a href="{{ route('seller.reviews') }}" @class(['active' => $active === 'reviews'])><i class="bi bi-star-fill"></i>Reviews</a>
            <a href="{{ route('complaints.index') }}" @class(['active' => $active === 'complaints'])><i class="bi bi-exclamation-triangle-fill"></i>Complaints</a>
            <a href="{{ route('seller.profile') }}" @class(['active' => $active === 'profile'])><i class="bi bi-person-fill"></i>My Profile</a>
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


@once
    <link rel="stylesheet" href="{{ vasset('css/partials/panel-wide.css') }}">
@endonce
