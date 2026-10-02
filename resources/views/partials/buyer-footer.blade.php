{{--
    One footer for the landing page and every buyer page. Every link goes to
    exactly what it says; guest and buyer only differ in the Account column.
--}}
@php
    $bbFooterUser = session('user');
    $bbFooterBuyer = $bbFooterUser && ($bbFooterUser['role'] ?? '') === 'buyer';
    $bbSupportId = $bbFooterBuyer
        ? \App\Models\User::where('email', 'admin@boombuy.com')->value('id')
        : null;
@endphp

@once
<style>
    .bb-footer {
        margin-top: 48px;
        border-top: 1px solid #f7e5e0;
        background: #fff;
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        color: #6f5a53;
    }

    .bb-footer-inner {
        max-width: 1320px;
        margin: 0 auto;
        padding: 40px 24px 0;
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) repeat(3, minmax(0, 1fr));
        gap: 32px;
    }

    .bb-footer-brand .bb-footer-logo {
        font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
        font-size: 24px;
        font-weight: 800;
        color: #e8420f;
        text-decoration: none;
    }

    .bb-footer-brand .bb-footer-logo span {
        color: #172033;
    }

    .bb-footer-brand p {
        margin: 8px 0 14px;
        max-width: 320px;
        font-size: 13px;
        line-height: 1.6;
    }

    .bb-footer-promises {
        display: flex;
        flex-direction: column;
        gap: 6px;
        font-size: 12.5px;
    }

    .bb-footer-promises span {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .bb-footer-promises i {
        color: #e8420f;
    }

    .bb-footer h4 {
        margin: 4px 0 12px;
        color: #172033;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .02em;
    }

    .bb-footer ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 9px;
    }

    .bb-footer ul a {
        color: #6f5a53;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
    }

    .bb-footer ul a:hover {
        color: #c43408;
    }

    .bb-footer-bottom {
        max-width: 1320px;
        margin: 28px auto 0;
        padding: 16px 24px 22px;
        border-top: 1px solid #f7e5e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px 20px;
        flex-wrap: wrap;
        font-size: 12.5px;
    }

    .bb-footer-bottom strong {
        color: #172033;
    }

    .bb-footer-sell {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #c43408;
        font-weight: 700;
        text-decoration: none;
    }

    .bb-footer-sell:hover {
        text-decoration: underline;
    }

    @media (max-width: 860px) {
        .bb-footer-inner {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
        }

        .bb-footer-brand {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 560px) {
        .bb-footer-inner {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding-top: 28px;
        }

        /* Room for the buyer bottom tab bar. */
        .has-bb-tabbar .bb-footer-bottom {
            padding-bottom: 24px;
        }
    }
</style>
@endonce

<footer class="bb-footer">
    <div class="bb-footer-inner">

        <div class="bb-footer-brand">
            <a href="{{ $bbFooterBuyer ? route('buyer.dashboard') : route('home') }}" class="bb-footer-logo">Boom<span>Buy</span></a>
            <p>Your everyday online marketplace — products from verified sellers, delivered to your door.</p>
            <div class="bb-footer-promises">
                <span><i class="bi bi-truck"></i> Free shipping on orders ₱{{ number_format(\App\Support\DeliveryFee::FREE_SHIPPING_MIN) }} and up</span>
                <span><i class="bi bi-cash-coin"></i> Cash on Delivery on every order</span>
                <span><i class="bi bi-arrow-counterclockwise"></i> 7-day returns</span>
            </div>
        </div>

        <div>
            <h4>Shop</h4>
            <ul>
                <li><a href="{{ route('products') }}">All products</a></li>
                <li><a href="{{ route('products', ['sort' => 'newest']) }}">New arrivals</a></li>
                {{-- Buyers are sent from the landing page to /buyer, so they get the shop's category chips instead. --}}
                <li><a href="{{ $bbFooterBuyer ? route('products') : route('home') . '#categories' }}">Categories</a></li>
            </ul>
        </div>

        <div>
            <h4>Account</h4>
            <ul>
                @if($bbFooterBuyer)
                    <li><a href="{{ route('buyer.orders') }}">My Orders</a></li>
                    <li><a href="{{ route('cart') }}">Cart</a></li>
                    <li><a href="{{ route('wishlist.index') }}">Wishlist</a></li>
                    <li><a href="{{ route('buyer.profile') }}">Profile &amp; addresses</a></li>
                @else
                    <li><a href="{{ route('login') }}">Log in</a></li>
                    <li><a href="{{ route('register') }}">Create an account</a></li>
                @endif
            </ul>
        </div>

        <div>
            <h4>Help</h4>
            <ul>
                @if($bbFooterBuyer)
                    {{-- Returns are requested from a delivered order. --}}
                    <li><a href="{{ route('buyer.orders', ['tab' => 'delivered']) }}">Request a return</a></li>
                    <li><a href="{{ route('complaints.index') }}">File a complaint</a></li>
                    @if($bbSupportId)
                        <li><a href="{{ route('messages.thread', $bbSupportId) }}">Message support</a></li>
                    @endif
                @endif
                <li><a href="{{ route('policies') }}#returns">Return &amp; refund policy</a></li>
                <li><a href="{{ route('policies') }}#terms">Terms &amp; privacy</a></li>
            </ul>
        </div>

    </div>

    <div class="bb-footer-bottom">
        <span>© {{ date('Y') }} <strong>BoomBuy</strong> · Your Marketplace for Everything</span>
        @unless($bbFooterBuyer)
            <a href="{{ route('register') }}" class="bb-footer-sell"><i class="bi bi-shop"></i> Sell on BoomBuy</a>
        @endunless
    </div>
</footer>
