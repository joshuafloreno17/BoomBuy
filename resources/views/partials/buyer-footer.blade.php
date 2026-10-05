{{--
    One footer for the landing page and every buyer page. Every link goes to
    exactly what it says; guest and buyer only differ in the Account column.
--}}
@once
<link rel="stylesheet" href="{{ vasset('css/partials/buyer-footer.css') }}">
@endonce

<footer class="bb-footer">
    <div class="bb-footer-inner">

        <div class="bb-footer-brand">
            <a href="{{ $bbFooterBuyer ? route('buyer.dashboard') : route('home') }}" class="bb-footer-logo"><img src="{{ asset('images/icon.svg') }}" alt="" width="30" height="30"> BoomBuy</a>
            <p>Your everyday online marketplace — products from verified sellers, delivered to your door.</p>
            <div class="bb-footer-promises">
                <span><i class="bi bi-truck"></i> Free shipping on orders ₱{{ number_format(\App\Support\DeliveryFee::FREE_SHIPPING_MIN) }} and up</span>
                <span><i class="bi bi-wallet2"></i> GCash, Maya, card or cash on delivery</span>
                <span><i class="bi bi-arrow-counterclockwise"></i> 7-day returns</span>
            </div>
        </div>

        <details class="bb-footer-col" open>
            <summary><h4>Shop</h4><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
            <ul>
                <li><a href="{{ route('products') }}">All products</a></li>
                <li><a href="{{ route('products', ['sort' => 'newest']) }}">New arrivals</a></li>
                {{-- Buyers are sent from the landing page to /buyer, so they get the shop's category chips instead. --}}
                <li><a href="{{ $bbFooterBuyer ? route('products') : route('home') . '#categories' }}">Categories</a></li>
            </ul>
        </details>

        <details class="bb-footer-col" open>
            <summary><h4>Account</h4><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
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
        </details>

        <details class="bb-footer-col" open>
            <summary><h4>Help</h4><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
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
        </details>

    </div>

    <div class="bb-footer-bottom">
        <span>© {{ date('Y') }} <strong>BoomBuy</strong> · Your Marketplace for Everything</span>
        @unless($bbFooterBuyer)
            <a href="{{ route('register') }}" class="bb-footer-sell"><i class="bi bi-shop"></i> Sell on BoomBuy</a>
        @endunless
    </div>
</footer>

@once
<script>
    // Runs right after the footer is parsed, so phones never paint the groups open.
    (function () {
        var phone = window.matchMedia('(max-width: 560px)');
        function sync() {
            document.querySelectorAll('.bb-footer-col').forEach(function (d) { d.open = !phone.matches; });
        }
        sync();
        phone.addEventListener('change', sync);
    })();

    // Short pages: push the footer down to the bottom of the screen instead of
    // leaving it floating under the content.
    (function () {
        var footer = document.querySelector('.bb-footer');
        if (!footer) return;

        function fit() {
            footer.style.marginTop = '';
            var gap = window.innerHeight - (footer.getBoundingClientRect().bottom + window.scrollY);
            if (gap > 0) {
                footer.style.marginTop = (parseFloat(getComputedStyle(footer).marginTop) + gap) + 'px';
            }
        }

        fit();
        window.addEventListener('load', fit);
        window.addEventListener('resize', fit);
        if (window.ResizeObserver) {
            new ResizeObserver(fit).observe(document.body);
        }
    })();
</script>
@endonce
