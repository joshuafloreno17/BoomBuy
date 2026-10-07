@props([
    'active' => null,
    'user' => [],
])

<aside class="sidebar">

    <a href="{{ route('seller.dashboard') }}" class="bb-side-brand">
        <img src="{{ asset('images/icon.svg') }}" alt="" width="36" height="36">
        <span class="bb-side-name">BoomBuy <small>Seller</small></span>
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
            href="{{ route('seller.payouts') }}"
            @class(['active' => $active === 'payouts'])
        >
            <i class="bi bi-wallet2"></i>
            <span class="label-text">
                Payouts
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
        </div>

        <form
            action="{{ route('logout') }}"
            method="POST"
            onsubmit="return bbConfirmSubmit(event, this, 'Are you sure you want to log out?');"
        >
            @csrf
            <button type="submit" class="logout" aria-label="Log out">
                <i class="bi bi-box-arrow-right"></i>
                <span class="label-text">Log out</span>
            </button>
        </form>
    </div>
</aside>

@include('partials.confirm-modal')

