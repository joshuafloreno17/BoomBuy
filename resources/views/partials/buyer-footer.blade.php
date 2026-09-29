{{-- One footer for every buyer page (was missing on most, and worded differently on the rest). --}}
@once
<style>
    .bb-footer {
        margin-top: 48px;
        border-top: 1px solid #f7e5e0;
        background: #fff;
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .bb-footer-inner {
        max-width: 1320px;
        margin: 0 auto;
        padding: 24px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px 24px;
        flex-wrap: wrap;
        font-size: 13px;
        color: #6f5a53;
    }

    .bb-footer strong {
        color: #172033;
    }

    .bb-footer nav {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 18px;
    }

    .bb-footer nav a {
        color: #6f5a53;
        font-weight: 600;
        text-decoration: none;
    }

    .bb-footer nav a:hover {
        color: #c43408;
    }
</style>
@endonce

<footer class="bb-footer">
    <div class="bb-footer-inner">
        <span>© {{ date('Y') }} <strong>BoomBuy</strong> · Your Marketplace for Everything</span>
        <nav aria-label="Footer">
            <a href="{{ route('products') }}">Shop</a>
            @if(session('user'))
                <a href="{{ route('buyer.orders') }}">My Orders</a>
                <a href="{{ route('buyer.orders', ['tab' => 'delivered']) }}">Returns &amp; Refunds</a>
                <a href="{{ route('complaints.index') }}">Help &amp; Complaints</a>
            @else
                <a href="{{ route('login') }}">Log in</a>
                <a href="{{ route('register') }}">Create an account</a>
            @endif
        </nav>
    </div>
</footer>
