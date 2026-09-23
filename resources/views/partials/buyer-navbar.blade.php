<style>
    .bb-navbar {
        width: 100%;
        max-width: 100%;
        height: 72px;

        background: white;
        border-bottom: 1px solid #ffe9e2;

        padding: 16px 7%;

        display: flex;
        align-items: center;
        justify-content: space-between;

        position: sticky;
        top: 0;
        z-index: 100;

        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .bb-navbar a {
        text-decoration: none;
    }

    .bb-logo {
        flex-shrink: 0;

        font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
        font-size: 23px;
        font-weight: 800;
        color: #e8420f;
    }

    .bb-logo span {
        color: #172033;
    }

    .bb-nav-links {
        display: flex;
        align-items: center;

        gap: 22px;

        margin-left: 40px;
        margin-right: auto;

        min-width: 0;
    }

    .bb-nav-links a {
        color: #8d6c62;
        font-size: 13px;
        font-weight: 700;

        padding: 8px 2px;

        white-space: nowrap;

        transition: 0.2s;
    }

    .bb-nav-links a:hover,
    .bb-nav-links a.active {
        color: #e8420f;
    }

    .bb-nav-search {
        display: flex;
        align-items: center;

        background: #fff6f3;
        border: 1px solid #f4ded6;
        border-radius: 12px;

        padding: 0 4px 0 12px;

        width: 200px;
        flex-shrink: 0;

        margin-right: 14px;
    }

    .bb-nav-search input {
        flex: 1;
        min-width: 0;

        border: none;
        background: transparent;
        outline: none;

        padding: 8px 0;

        font-family: inherit;
        font-size: 12px;
        color: #33241f;
    }

    .bb-nav-search input::placeholder {
        color: #b99c93;
    }

    .bb-nav-search button {
        border: none;
        background: transparent;
        cursor: pointer;

        padding: 7px;
        font-size: 13px;
    }

    .bb-nav-right {
        display: flex;
        align-items: center;
        gap: 10px;

        flex-shrink: 0;
    }

    .bb-nav-right form {
        margin: 0;
    }

    .bb-user-chip {
        display: flex;
        align-items: center;
        gap: 8px;

        padding: 6px 12px 6px 6px;

        background: #fff4f1;

        border-radius: 30px;

        font-size: 13px;
        font-weight: 700;

        color: #172033;

        white-space: nowrap;

        transition: 0.2s ease;
    }

    .bb-user-chip:hover {
        background: #ffe4dc;
    }

    .bb-user-avatar {
        width: 26px;
        height: 26px;

        border-radius: 50%;

        background: #e8420f;
        color: white;

        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;

        font-size: 12px;
        font-weight: 800;

        flex-shrink: 0;
    }

    .bb-user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .bb-logout {
        border: none;
        background: #fff3f0;
        color: #dc2626;

        padding: 8px 12px;

        border-radius: 8px;

        cursor: pointer;

        font-size: 12px;
        font-weight: 700;

        white-space: nowrap;
    }

    .bb-logout:hover {
        background: #ffe1e1;
    }

    .bb-cart-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .bb-cart-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        min-width: 20px;
        height: 20px;

        padding: 0 5px;

        background: #ef4444;
        color: white;

        border-radius: 50%;

        font-size: 11px;
        font-weight: 700;
    }

    .bb-login-link {
        color: #e8420f;
        background: #fff;
        border: 1px solid #f7d9cf;

        padding: 9px 16px;
        border-radius: 10px;

        font-size: 12px;
        font-weight: 800;

        white-space: nowrap;
        transition: 0.2s ease;
    }

    .bb-login-link:hover {
        background: #fff3f0;
    }

    .bb-register-link {
        background: #e8420f;
        color: white;

        padding: 10px 16px;
        border-radius: 10px;

        font-size: 12px;
        font-weight: 800;

        white-space: nowrap;
        transition: 0.2s ease;
    }

    .bb-register-link:hover {
        background: #c43408;
    }

    @media (max-width: 1100px) {
        .bb-nav-links {
            gap: 18px;
            margin-left: 25px;
        }

        .bb-nav-links a {
            font-size: 12px;
        }
    }

    @media (max-width: 700px) {
        .bb-navbar {
            height: auto;
            min-height: 72px;

            flex-wrap: wrap;

            gap: 12px;

            padding: 15px 20px;
        }

        .bb-logo {
            font-size: 21px;
        }

        .bb-nav-links {
            order: 3;

            width: 100%;

            justify-content: center;

            margin: 0;

            gap: 15px;

            flex-wrap: wrap;
        }

        .bb-nav-links a {
            font-size: 12px;
        }

        .bb-nav-search {
            order: 4;

            width: 100%;
            margin: 8px 0 0;
        }

        .bb-nav-right {
            margin-left: auto;
        }

        .bb-user-chip .bb-chip-text {
            display: none;
        }
    }

    @media (max-width: 450px) {
        .bb-nav-links {
            gap: 10px;
        }

        .bb-nav-links a {
            font-size: 11px;
        }

        .bb-logout {
            padding: 7px 9px;
            font-size: 11px;
        }
    }
</style>

@php
    $bbNavUser = session()->get('user');
    $bbCartCount = array_sum(session()->get('cart', []));
    $bbActive = $activeNav ?? null;

    $bbNotificationCount = 0;

    if ($bbNavUser) {
        $bbNotificationCount = \App\Models\Notification::where(
            'user_id',
            $bbNavUser['id']
        )
        ->whereNull('read_at')
        ->count();
    }
@endphp

<nav class="bb-navbar">

    <a href="{{ $bbNavUser ? route('buyer.dashboard') : route('home') }}" class="bb-logo">
        Boom<span>Buy</span>
    </a>

    <div class="bb-nav-links">

        <a href="{{ $bbNavUser ? route('buyer.dashboard') : route('home') }}" class="{{ $bbActive === 'home' ? 'active' : '' }}">
            Home
        </a>

        <a href="{{ route('products') }}" class="{{ $bbActive === 'shop' ? 'active' : '' }}">
            Shop
        </a>

@if($bbNavUser)

    <a
        href="{{ route('buyer.orders') }}"
        class="{{ $bbActive === 'orders' ? 'active' : '' }}"
    >
        My Orders
    </a>

    <a
        href="{{ route('wishlist.index') }}"
        class="{{ $bbActive === 'wishlist' ? 'active' : '' }}"
    >
        Wishlist
    </a>

    <a
        href="{{ route('notifications') }}"
        class="bb-cart-link {{ $bbActive === 'notifications' ? 'active' : '' }}"
    >
        🔔 Notifications

        <span
            class="bb-cart-number"
            style="{{ $bbNotificationCount > 0 ? '' : 'display:none;' }}"
        >
            {{ $bbNotificationCount }}
        </span>
    </a>

@endif

        <a href="{{ route('cart') }}" class="bb-cart-link {{ $bbActive === 'cart' ? 'active' : '' }}">
            Cart

            <span
                class="bb-cart-number"
                id="cartCount"
                style="{{ $bbCartCount > 0 ? '' : 'display:none;' }}"
            >{{ $bbCartCount }}</span>
        </a>

    </div>

    <form class="bb-nav-search" action="{{ route('products') }}" method="GET">
        <input
            type="text"
            name="search"
            placeholder="Search products..."
            aria-label="Search products"
        >
        <button type="submit" aria-label="Search">
            🔍
        </button>
    </form>

    <div class="bb-nav-right">

        @if($bbNavUser)

            @php
                $bbDisplayName = $bbNavUser['name'] ?? 'Buyer';
                $bbInitial = strtoupper(substr($bbDisplayName, 0, 1));
            @endphp

            <a href="{{ route('buyer.profile') }}" class="bb-user-chip">
                <span class="bb-user-avatar">
                    @if(!empty($bbNavUser['profile_photo']))
                        <img src="{{ asset('storage/profile-photos/' . $bbNavUser['profile_photo']) }}" alt="Profile Picture">
                    @else
                        {{ $bbInitial }}
                    @endif
                </span>
                <span class="bb-chip-text">{{ $bbDisplayName }}</span>
            </a>

            <form
                action="{{ route('logout') }}"
                method="POST"
                onsubmit="return confirm('Are you sure you want to log out?');"
            >
                @csrf
                <button class="bb-logout" type="submit">
                    Logout
                </button>
            </form>

        @else

            <a href="{{ route('login') }}" class="bb-login-link">
                Login
            </a>

            <a href="{{ route('register') }}" class="bb-register-link">
                Register
            </a>

        @endif

    </div>

</nav>
