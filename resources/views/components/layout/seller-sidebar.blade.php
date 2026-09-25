@props([
    'active' => null,
    'user' => [],
    'logoHref' => null,
    'notifLinkFix' => false,
    'showNotifications' => true,
    'footer' => 'account',
])

@php
    $sellerUnreadNotifications = \App\Models\Notification::where(
        'user_id',
        $user['id'] ?? null
    )->whereNull('read_at')->count();
@endphp

<aside class="sidebar">

    <a href="{{ $logoHref ?? route('seller.dashboard') }}" class="logo">
        Boom<span>Buy</span>
    </a>

    <div class="sidebar-label">
        Seller Panel
    </div>

    <nav class="menu">

        <a
            href="{{ route('seller.dashboard') }}"
            @class(['active' => $active === 'dashboard'])
        >
            📊
            <span class="label-text">
                Dashboard
            </span>
        </a>

        <a
            href="{{ route('seller.products.create') }}"
            @class(['active' => $active === 'products'])
        >
            ➕
            <span class="label-text">
                Add Product
            </span>
        </a>

        <a
            href="{{ route('seller.orders') }}"
            @class(['active' => $active === 'orders'])
        >
            🛒
            <span class="label-text">
                Orders
            </span>
        </a>

        <a
            href="{{ route('seller.reports') }}"
            @class(['active' => $active === 'reports'])
        >
            📊
            <span class="label-text">
                Reports
            </span>
        </a>

        <a
            href="{{ route('seller.vouchers') }}"
            @class(['active' => $active === 'vouchers'])
        >
            🎟️
            <span class="label-text">
                Vouchers
            </span>
        </a>

        <a
            href="{{ route('seller.reviews') }}"
            @class(['active' => $active === 'reviews'])
        >
            ⭐
            <span class="label-text">
                Reviews
            </span>
        </a>

        @if($showNotifications)

            <a
                href="{{ route('seller.notifications') }}"
                @class([
                    'active' => $active === 'notifications',
                    'notification-link' => $notifLinkFix,
                ])
                aria-label="Notifications"
                title="Notifications"
            >
                🔔

                @if($sellerUnreadNotifications > 0)

                    <span class="notification-badge">
                        {{ $sellerUnreadNotifications > 99
                            ? '99+'
                            : $sellerUnreadNotifications }}
                    </span>

                @endif

            </a>

        @endif

        <a
            href="{{ route('complaints.index') }}"
            @class(['active' => $active === 'complaints'])
        >
            ⚠️
            <span class="label-text">
                Complaints
            </span>
        </a>

        <a
            href="{{ route('messages.index') }}"
            @class(['active' => $active === 'messages'])
        >
            💬
            <span class="label-text">
                Messages
            </span>
        </a>

        <a
            href="{{ route('seller.profile') }}"
            @class(['active' => $active === 'profile'])
        >
            👤
            <span class="label-text">
                My Profile
            </span>
        </a>

    </nav>

    <div class="sidebar-footer">

        @if($footer === 'back')

            <a href="{{ route('seller.dashboard') }}">
                ← <span class="label-text">Back to Dashboard</span>
            </a>

        @else

            <div
                class="user"
                style="padding:0 4px; margin-bottom:8px;"
            >
                Seller: {{ $user['name'] ?? 'Seller' }}
            </div>

            <form
                action="{{ route('logout') }}"
                method="POST"
                onsubmit="return confirm('Are you sure you want to log out?');"
            >

                @csrf

                <button
                    type="submit"
                    class="logout"
                    style="width:100%;"
                >
                    Logout
                </button>

            </form>

        @endif

    </div>

</aside>
