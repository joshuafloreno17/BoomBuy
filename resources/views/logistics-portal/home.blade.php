<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'BoomBuy Logistics'])
    <meta name="description" content="BoomBuy Logistics — the delivery side of BoomBuy. Parcels picked up from sellers, sorted at sorting centers and delivered to buyers.">

    <link rel="stylesheet" href="{{ vasset('css/views/logistics-portal.css') }}">
</head>
<body>

    <header class="lg-nav">
        <div class="lg-wrap lg-nav-row">
            <a href="{{ route('logistics.home') }}" class="lg-brand">
                <img src="{{ vasset('images/logo-wordmark.svg') }}" alt="BoomBuy" class="bb-logo-wordmark" width="131" height="46">
                <span class="lg-tag">Logistics</span>
            </a>

            <nav class="lg-links" aria-label="BoomBuy Logistics">
                <a href="#how">How it works</a>
                <a href="#centers">Sorting centers</a>
            </nav>

            <div class="lg-actions">
                <a href="{{ route('logistics.login') }}" class="lg-btn lg-btn-ghost">Log in</a>
                <a href="{{ route('logistics.register') }}" class="lg-btn lg-btn-primary">Join</a>
            </div>
        </div>
    </header>

    <main>

        <section class="lg-hero">
            <div class="lg-wrap lg-hero-grid">
                <div>
                    <span class="lg-pill"><i class="bi bi-truck"></i> The delivery side of BoomBuy</span>
                    <h1>Every parcel,<br><span>on its way.</span></h1>
                    <p class="lg-lead">
                        BoomBuy Logistics picks up orders from sellers, sorts them at
                        sorting centers across the country and delivers them to buyers'
                        doors, cash on delivery included.
                    </p>
                    <div class="lg-hero-actions">
                        <a href="{{ route('logistics.register') }}" class="lg-btn lg-btn-primary lg-btn-lg">Register a sorting center <i class="bi bi-arrow-right"></i></a>
                        <a href="{{ route('logistics.login') }}" class="lg-btn lg-btn-ghost lg-btn-lg">Log in</a>
                    </div>
                </div>

                {{-- A parcel's journey, the same steps the dashboards track. --}}
                <div class="lg-track" aria-label="Example parcel">
                    <div class="lg-track-head">
                        <span class="lg-track-icon"><i class="bi bi-box-seam-fill"></i></span>
                        <div>
                            <strong>Parcel BB-2026-0142</strong>
                            <span>Wireless earbuds · COD ₱1,299</span>
                        </div>
                    </div>
                    <ol class="lg-steps">
                        <li class="is-done"><span></span><div><strong>Picked up from seller</strong><small>Courier collected the packed parcel</small></div></li>
                        <li class="is-done"><span></span><div><strong>At sorting center</strong><small>Scanned and sorted by area</small></div></li>
                        <li class="is-now"><span></span><div><strong>Out for delivery</strong><small>On the way to the buyer</small></div></li>
                        <li><span></span><div><strong>Delivered</strong><small>Photo proof + COD collected</small></div></li>
                    </ol>
                </div>
            </div>
        </section>

        <section class="lg-strip" aria-label="BoomBuy Logistics at a glance">
            <div class="lg-wrap lg-strip-row">
                <div><strong>{{ number_format($centerCount) }}</strong><span>sorting centers</span></div>
                <div><strong>Door to door</strong><span>seller pickup to buyer delivery</span></div>
                <div><strong>Proof of delivery</strong><span>photo on every drop-off</span></div>
                <div><strong>COD remittance</strong><span>collected and remitted to the center</span></div>
            </div>
        </section>

        <section class="lg-section" id="how">
            <div class="lg-wrap">
                <h2 class="lg-h2">How a parcel moves</h2>
                <p class="lg-sub">From the seller's shop to the buyer's door in four steps.</p>

                <div class="lg-flow">
                    <div class="lg-flow-step"><span class="lg-num">1</span><i class="bi bi-shop"></i><strong>Pickup</strong><p>The seller packs the order and books a pickup. A courier in the area collects the parcel.</p></div>
                    <div class="lg-flow-step"><span class="lg-num">2</span><i class="bi bi-upc-scan"></i><strong>Sorting</strong><p>The parcel is scanned in at the sorting center and sorted by the buyer's area.</p></div>
                    <div class="lg-flow-step"><span class="lg-num">3</span><i class="bi bi-truck"></i><strong>Delivery</strong><p>The center assigns a courier who takes the parcel out for delivery.</p></div>
                    <div class="lg-flow-step"><span class="lg-num">4</span><i class="bi bi-camera"></i><strong>Proof &amp; COD</strong><p>A photo is taken at drop-off, and cash on delivery is collected and remitted to the center.</p></div>
                </div>
            </div>
        </section>

        <section class="lg-section lg-section-alt">
            <div class="lg-wrap">

                <article class="lg-role lg-role-wide" id="centers">
                    <div class="lg-role-intro">
                        <span class="lg-role-icon is-teal"><i class="bi bi-buildings"></i></span>
                        <h3>For sorting centers</h3>
                        <p>Run the hub for your area from your BoomBuy Logistics dashboard.</p>
                        <a href="{{ route('logistics.register') }}" class="lg-btn lg-btn-primary">Register a sorting center</a>
                    </div>
                    <ul>
                        <li><i class="bi bi-check2-circle"></i> Scan parcels in and dispatch them by area</li>
                        <li><i class="bi bi-check2-circle"></i> Manage your delivery team and their service areas</li>
                        <li><i class="bi bi-check2-circle"></i> Track every parcel from pickup to delivery</li>
                        <li><i class="bi bi-check2-circle"></i> Receive COD remittances and handle returns</li>
                        <li><i class="bi bi-check2-circle"></i> Open branches and add staff for your towns</li>
                        <li><i class="bi bi-check2-circle"></i> See your center's numbers on one dashboard</li>
                    </ul>
                </article>

            </div>
        </section>

        <section class="lg-section">
            <div class="lg-wrap">
                <div class="lg-cta">
                    <div>
                        <h2>Ready to move parcels with us?</h2>
                        <p>Applications are reviewed before your account is activated.</p>
                    </div>
                    <div class="lg-cta-actions">
                        <a href="{{ route('logistics.register') }}" class="lg-btn lg-btn-light lg-btn-lg">Join BoomBuy Logistics</a>
                        <a href="{{ route('logistics.login') }}" class="lg-btn lg-btn-outline lg-btn-lg">Log in</a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer class="lg-footer">
        <div class="lg-wrap lg-footer-row">
            <span>© 2026 BoomBuy Logistics</span>
            <span>Shopping instead? <a href="{{ route('home') }}">Go to the BoomBuy marketplace →</a></span>
        </div>
    </footer>

    @include('partials.pwa-register')

</body>
</html>
