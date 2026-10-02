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

    $sellerUnreadMessages = \App\Models\Message::where(
        'recipient_id',
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

        @endif

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
                onsubmit="return bbConfirmSubmit(event, this, 'Are you sure you want to log out?');"
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

@include('partials.confirm-modal')

@once
    <style>
        /* Narrow icon rail (every seller page collapses the sidebar at 900px):
           only the "B" of the logo fits. Lives here because several seller
           pages carry their own copy of the sidebar CSS. */
        @media (max-width: 900px) {
            aside.sidebar .logo {
                font-size: 0;
                text-align: center;
            }

            aside.sidebar .logo::before {
                content: "B";
                font-size: 25px;
            }
        }
    </style>
@endonce
