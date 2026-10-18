<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'BoomBuy Logistics'])
    <meta name="description" content="BoomBuy Logistics — the delivery side of BoomBuy. Parcels picked up from sellers, sorted at province sorting centers and delivered to buyers.">

    <link rel="stylesheet" href="{{ vasset('css/views/logistics-portal.css') }}">
</head>
<body>

    @php
        // Line icons (24×24, stroked) shared by the stats and the journey.
        $icon = [
            'hub' => 'M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8',
            'branch' => 'M3 21h18M5 21V9l7-5 7 5v12M10 21v-6h4v6',
            'camera' => 'M4 8h3l2-3h6l2 3h3v11H4zM12 17a3.5 3.5 0 100-7 3.5 3.5 0 000 7',
            'qr' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 18h2v2h-2z',
            'store' => 'M4 9h16l-2-5H6zM5 9v11h14V9M10 20v-5h4v5',
            'moto' => 'M5 17a2 2 0 104 0 2 2 0 00-4 0M15 17a2 2 0 104 0 2 2 0 00-4 0M7 17l3-7h4l3 7M12 6h3',
            'home' => 'M3 11l9-8 9 8M5 10v10h14V10M10 20v-6h4v6',
        ];

        $stats = [
            ['n' => number_format($centerCount), 'label' => 'Province sorting centers', 'icon' => $icon['hub']],
            ['n' => number_format($branchCount), 'label' => 'Town branches', 'icon' => $icon['branch']],
            ['n' => 'Photo', 'label' => 'Proof on every delivery', 'icon' => $icon['camera']],
            ['n' => 'QR', 'label' => 'On every shipping label', 'icon' => $icon['qr']],
        ];

        // The statuses a parcel really goes through, in order.
        $stops = [
            ['title' => 'Seller', 'note' => 'Packs and books a pickup', 'status' => 'Ready for Pickup', 'icon' => $icon['store']],
            ['title' => 'Pickup courier', 'note' => 'Collects at the door', 'status' => 'Picked Up', 'icon' => $icon['moto']],
            ['title' => 'Sorting center', 'note' => 'Scanned and sorted', 'status' => 'Sorted', 'icon' => $icon['hub'], 'strong' => true],
            ['title' => 'Branch', 'note' => 'Closer to the buyer', 'status' => 'At Branch', 'icon' => $icon['branch']],
            ['title' => 'Delivery courier', 'note' => 'On the way', 'status' => 'Out for Delivery', 'icon' => $icon['moto'], 'pill' => 'is-info'],
            ['title' => 'Buyer', 'note' => 'Photo taken at the door', 'status' => 'Delivered', 'icon' => $icon['home'], 'strong' => true, 'pill' => 'is-ok'],
        ];

        $ticker = [
            ['text' => 'Picked up from the seller', 'dot' => 'is-violet'],
            ['text' => 'Scanned in at the sorting center', 'dot' => 'is-lilac'],
            ['text' => 'Sorted by the buyer\'s area', 'dot' => 'is-lilac'],
            ['text' => 'Trucked to the town branch', 'dot' => 'is-violet'],
            ['text' => 'Out for delivery', 'dot' => 'is-blue'],
            ['text' => 'Delivered with photo proof', 'dot' => 'is-green'],
            ['text' => 'Cash on delivery handed in', 'dot' => 'is-amber'],
        ];

        // One parcel route per side: seller → center → branch/courier → buyer.
        $routeTop = 'M80 120 C175 120 205 218 300 250 C362 226 384 100 455 100 C500 100 530 135 545 175';
        $routeBottom = 'M80 380 C175 380 205 282 300 250 C362 274 384 400 455 400 C500 400 530 365 545 325';
        $segments = [
            'M80 120 C175 120 205 218 300 250', 'M80 380 C175 380 205 282 300 250',
            'M300 250 C362 226 384 100 455 100', 'M455 100 C500 100 530 135 545 175',
            'M300 250 C362 274 384 400 455 400', 'M455 400 C500 400 530 365 545 325',
        ];
    @endphp

    <section class="lg-hero">
        <span class="lg-orb lg-orb-a" aria-hidden="true"></span>
        <span class="lg-orb lg-orb-b" aria-hidden="true"></span>

        <header class="lg-wrap lg-nav">
            <a href="{{ route('logistics.home') }}" class="lg-brand">
                <img src="{{ vasset('images/logo-wordmark-white.svg') }}" alt="BoomBuy" width="125" height="44">
                <span class="lg-tag">Logistics</span>
            </a>

            <nav class="lg-links" aria-label="BoomBuy Logistics">
                <a href="#how">How it works</a>
                <a href="#portal">The portal</a>
                <a href="#who">Who it's for</a>
                <a href="#faq">FAQ</a>
            </nav>

            <div class="lg-actions">
                <a href="{{ route('logistics.login') }}" class="lg-btn lg-btn-outline">Log in</a>
                <a href="{{ route('logistics.register') }}" class="lg-btn lg-btn-lilac">Register a center</a>
            </div>
        </header>

        <div class="lg-wrap lg-hero-grid">
            <div class="lg-hero-copy">
                <span class="lg-pill"><span class="lg-live"></span>The network behind every BoomBuy order</span>
                <h1>Every parcel. <span>Every province.</span> One portal.</h1>
                <p class="lg-lead">
                    {{ number_format($centerCount) }} province sorting centers and their town branches receive,
                    sort and deliver BoomBuy parcels across the Philippines. Run yours from here.
                </p>
                <div class="lg-hero-actions">
                    <a href="{{ route('logistics.login') }}" class="lg-btn lg-btn-white lg-btn-lg">Log in to your center <i class="bi bi-arrow-right"></i></a>
                    <a href="{{ route('logistics.register') }}" class="lg-btn lg-btn-violet lg-btn-lg">Register a sorting center</a>
                </div>
                <ul class="lg-checks">
                    <li><i class="bi bi-check-lg"></i> Approved by BoomBuy</li>
                    <li><i class="bi bi-check-lg"></i> Photo proof on every drop-off</li>
                    <li><i class="bi bi-check-lg"></i> COD tracked to the peso</li>
                </ul>
            </div>

            {{-- Sellers → sorting center → branch / courier → buyers, with parcels moving along it. --}}
            <div class="lg-net-wrap" aria-hidden="true">
                <svg class="lg-net" viewBox="0 0 640 500">
                    <defs>
                        <pattern id="lgDots" width="22" height="22" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.6" fill="#3a2560"/></pattern>
                        <radialGradient id="lgFade" cx="50%" cy="50%" r="50%"><stop offset="55%" stop-color="#2a1846" stop-opacity="0"/><stop offset="100%" stop-color="#2a1846" stop-opacity="1"/></radialGradient>
                        <filter id="lgGlow" x="-80%" y="-80%" width="260%" height="260%"><feGaussianBlur stdDeviation="5"/></filter>
                        <clipPath id="lgBoxClip"><path d="M-22 -15 L0 -27 L22 -15 L22 11 L0 23 L-22 11 Z"/></clipPath>
                    </defs>
                    <rect x="-30" y="-30" width="700" height="560" fill="url(#lgDots)"/>
                    <rect x="-30" y="-30" width="700" height="560" fill="url(#lgFade)"/>

                    <g fill="none" stroke-linecap="round">
                        <g stroke="#3f2868" stroke-width="12">
                            @foreach($segments as $d)<path d="{{ $d }}"/>@endforeach
                        </g>
                        <g class="lg-flow" stroke="#8a6ae6" stroke-width="4">
                            @foreach($segments as $d)<path d="{{ $d }}"/>@endforeach
                        </g>
                    </g>

                    {{-- The sorting center: a parcel being scanned. --}}
                    <circle class="lg-pulse" cx="300" cy="250" r="100" fill="#6d4ad1"/>
                    <circle cx="300" cy="250" r="82" fill="#321d54"/>
                    <circle class="lg-orbit" cx="300" cy="250" r="72" fill="none" stroke="#9a7cf0" stroke-width="3" stroke-dasharray="4 12" stroke-linecap="round"/>
                    <circle cx="300" cy="250" r="58" fill="#6d4ad1"/>
                    <g transform="translate(300 250)">
                        <path d="M-22 -15 L0 -27 L22 -15 L22 11 L0 23 L-22 11 Z" fill="#7b5ce0"/>
                        <path d="M-22 -15 L0 -3 L22 -15 M0 -3 V23" fill="none" stroke="#fff" stroke-width="3.4" stroke-linejoin="round"/>
                        <path d="M-22 -15 L0 -27 L22 -15 L22 11 L0 23 L-22 11 Z" fill="none" stroke="#fff" stroke-width="3.4" stroke-linejoin="round"/>
                        <g clip-path="url(#lgBoxClip)"><rect class="lg-scan" x="-30" y="-2" width="60" height="4" fill="#c9b6ff"/></g>
                    </g>
                    <g transform="translate(300 374)" text-anchor="middle"><rect x="-66" y="-15" width="132" height="30" rx="15" fill="#3a2560" stroke="#6d4ad1" stroke-width="1.5"/><text y="5" class="lg-net-label">Sorting center</text></g>

                    <g class="lg-net-label">
                        @foreach([120, 380] as $y)
                            <g transform="translate(80 {{ $y }})" text-anchor="middle"><rect x="-30" y="-30" width="60" height="60" rx="19" fill="#7b5ce0"/><path d="M-14 -4 h28 l-4 -10 h-20z M-12 -4 v16 h24 v-16 M-4 12 v-8 h8 v8" fill="none" stroke="#fff" stroke-width="2.8" stroke-linejoin="round"/><text y="52">Seller</text></g>
                        @endforeach
                        <g transform="translate(455 100)" text-anchor="middle"><rect x="-30" y="-30" width="60" height="60" rx="19" fill="#5536b5"/><path d="M-14 12 v-14 l14 -10 14 10 v14 z M-5 12 v-9 h10 v9" fill="none" stroke="#fff" stroke-width="2.8" stroke-linejoin="round"/><text y="-42">Branch</text></g>
                        <g transform="translate(455 400)" text-anchor="middle"><rect x="-30" y="-30" width="60" height="60" rx="19" fill="#5536b5"/><circle cx="-10" cy="7" r="6" fill="none" stroke="#fff" stroke-width="2.8"/><circle cx="11" cy="7" r="6" fill="none" stroke="#fff" stroke-width="2.8"/><path d="M-10 7 l7 -13 h9 l5 13 M4 -6 h8" fill="none" stroke="#fff" stroke-width="2.8" stroke-linejoin="round" stroke-linecap="round"/><text y="52">Courier</text></g>
                        @foreach([175, 325] as $y)
                            <g transform="translate(545 {{ $y }})"><rect x="-27" y="-27" width="54" height="54" rx="17" fill="#fff"/><path d="M-12 1 l12 -11 12 11 M-9 -1 v12 h18 v-12 M-3 11 v-7 h6 v7" fill="none" stroke="#5536b5" stroke-width="2.8" stroke-linejoin="round"/><text x="36" y="5">Buyer</text></g>
                        @endforeach
                    </g>

                    <g class="lg-moving">
                        @foreach([[$routeTop, 0, '#ffffff', '#9a7cf0', 175], [$routeBottom, -2.5, '#c9b6ff', '#5536b5', 325]] as [$path, $begin, $fill, $tape, $buyerY])
                            <g opacity=".18"><rect x="-6" y="-6" width="12" height="12" rx="3" fill="#c9b6ff"/><animateMotion dur="5s" begin="{{ $begin - 4.72 }}s" repeatCount="indefinite" rotate="auto" path="{{ $path }}"/></g>
                            <g opacity=".4"><rect x="-8" y="-8" width="16" height="16" rx="4" fill="#c9b6ff"/><animateMotion dur="5s" begin="{{ $begin - 4.86 }}s" repeatCount="indefinite" rotate="auto" path="{{ $path }}"/></g>
                            <g><rect x="-14" y="-14" width="28" height="28" rx="7" fill="#c9b6ff" filter="url(#lgGlow)" opacity=".8"/><rect x="-11" y="-11" width="22" height="22" rx="5" fill="{{ $fill }}"/><path d="M-11 -3 h22 M0 -11 v8" stroke="{{ $tape }}" stroke-width="2.6"/><animateMotion dur="5s" begin="{{ $begin }}s" repeatCount="indefinite" rotate="auto" path="{{ $path }}"/></g>
                            <circle cx="545" cy="{{ $buyerY }}" r="30" fill="none" stroke="#4ade80" stroke-width="4" opacity="0"><animate attributeName="opacity" dur="5s" begin="{{ $begin }}s" repeatCount="indefinite" keyTimes="0;0.9;0.94;1" values="0;0;1;0"/><animate attributeName="r" dur="5s" begin="{{ $begin }}s" repeatCount="indefinite" keyTimes="0;0.9;1" values="28;28;48"/></circle>
                            <text x="575" y="{{ $buyerY - 25 }}" class="lg-plus" opacity="0">+1<animate attributeName="opacity" dur="5s" begin="{{ $begin }}s" repeatCount="indefinite" keyTimes="0;0.9;0.95;1" values="0;0;1;0"/><animate attributeName="y" dur="5s" begin="{{ $begin }}s" repeatCount="indefinite" keyTimes="0;0.9;1" values="{{ $buyerY - 25 }};{{ $buyerY - 25 }};{{ $buyerY - 43 }}"/></text>
                        @endforeach
                    </g>

                    <g class="lg-net-label" text-anchor="middle">
                        <g transform="translate(80 54)"><rect x="-46" y="-16" width="92" height="32" rx="16" fill="#3a2560" stroke="#8a6ae6" stroke-width="1.5"/><text y="5">Picked up</text><path d="M-5 16 l5 7 5 -7z" fill="#8a6ae6"/></g>
                        <g transform="translate(300 122)"><rect x="-38" y="-16" width="76" height="32" rx="16" fill="#c9b6ff"/><text y="5" fill="#2a1846">Sorted</text><path d="M-5 16 l5 7 5 -7z" fill="#c9b6ff"/></g>
                        <g transform="translate(455 482)"><rect x="-48" y="-16" width="96" height="32" rx="16" fill="#fff"/><text y="5" fill="#5536b5">Out today</text></g>
                        <g transform="translate(545 250)"><rect x="-54" y="-16" width="108" height="32" rx="16" fill="#4ade80"/><text y="5" fill="#14532d">Delivered ✓</text></g>
                    </g>
                </svg>
            </div>
        </div>

        <div class="lg-ticker-band" aria-hidden="true">
            <div class="lg-ticker">
                @foreach(array_merge($ticker, $ticker) as $t)
                    <span><span class="lg-dot {{ $t['dot'] }}"></span>{{ $t['text'] }}</span>
                @endforeach
            </div>
        </div>
    </section>

    <main>

        <section class="lg-wrap lg-stats" aria-label="BoomBuy Logistics at a glance">
            @foreach($stats as $s)
                <div class="lg-stat">
                    <span class="lg-stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $s['icon'] }}"/></svg></span>
                    <div><strong>{{ $s['n'] }}</strong><span>{{ $s['label'] }}</span></div>
                </div>
            @endforeach
        </section>

        <section class="lg-wrap lg-section">
            <span class="lg-eyebrow">What you do here</span>
            <h2 class="lg-h2">Three jobs. One screen each.</h2>
            <div class="lg-jobs">
                <article class="lg-job">
                    <div class="lg-job-top"><span class="lg-job-num">01</span><span class="lg-chip">Scan</span></div>
                    <div class="lg-job-demo is-dark" aria-hidden="true"><span class="lg-job-qr"><i class="bi bi-qr-code"></i></span><div><small>Goes to</small><b>Buyer's town</b></div></div>
                    <h3>Receive by scan</h3>
                    <p>Scan the QR on the shipping label as parcels arrive. It opens the parcel and shows where it goes next.</p>
                </article>
                <article class="lg-job">
                    <div class="lg-job-top"><span class="lg-job-num">02</span><span class="lg-chip">Assign</span></div>
                    <div class="lg-job-demo is-lilac" aria-hidden="true"><span class="lg-job-avatar"><i class="bi bi-person-fill"></i></span><div class="lg-grow"><b>Courier</b><small>Covers the buyer's town</small></div><span class="lg-job-btn">Assign</span></div>
                    <h3>Hand off for delivery</h3>
                    <p>Sorted parcels go to a courier who covers that town, or onto the truck to its town branch.</p>
                </article>
                <article class="lg-job">
                    <div class="lg-job-top"><span class="lg-job-num">03</span><span class="lg-chip is-ok">Deliver</span></div>
                    <div class="lg-job-demo is-green" aria-hidden="true"><div class="lg-grow"><small>Delivered · photo taken</small><b>Cash on delivery</b></div><span class="lg-job-btn is-light">Handed in</span></div>
                    <h3>Close the loop</h3>
                    <p>Proof-of-delivery photos, cash on delivery handed in, failed attempts and returns: all in one place.</p>
                </article>
            </div>
        </section>

        <section class="lg-wrap lg-section" id="how">
            <span class="lg-eyebrow">How it works</span>
            <h2 class="lg-h2">How a parcel travels</h2>
            <div class="lg-journey-card">
                <ol class="lg-journey">
                    @foreach($stops as $s)
                        <li>
                            <span class="lg-journey-icon {{ !empty($s['strong']) ? 'is-strong' : '' }}"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $s['icon'] }}"/></svg></span>
                            <strong>{{ $s['title'] }}</strong>
                            <span>{{ $s['note'] }}</span>
                            <em class="lg-status {{ $s['pill'] ?? (!empty($s['strong']) ? 'is-strong' : '') }}">{{ $s['status'] }}</em>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section class="lg-wrap lg-section lg-portal" id="portal">
            <div class="lg-portal-copy">
                <span class="lg-eyebrow">The portal</span>
                <h2 class="lg-h2">See your day at a glance</h2>
                <p>Log in and the dashboard tells you what to do next: parcels to confirm, sort and dispatch, which ones still need a courier, and how many were delivered today.</p>
                <a href="{{ route('logistics.login') }}" class="lg-btn lg-btn-primary lg-btn-lg">Log in to the portal</a>
            </div>

            {{-- A sketch of the dashboard, not real numbers. --}}
            <div class="lg-mock" aria-hidden="true">
                <div class="lg-mock-bar"><span></span><span></span><span></span></div>
                <div class="lg-mock-body">
                    <div class="lg-mock-side"><i class="is-logo"></i><i class="is-active"></i><i></i><i></i><i></i></div>
                    <div class="lg-mock-main">
                        <div class="lg-mock-hero"><small>Today</small><b>Good morning</b></div>
                        <div class="lg-mock-flow">
                            <span><small>Arrived</small><i></i></span>
                            <span class="is-amber"><small>To sort</small><i></i></span>
                            <span class="is-lilac"><small>To assign</small><i></i></span>
                            <span class="is-blue"><small>Out</small><i></i></span>
                            <span class="is-green"><small>Delivered</small><i></i></span>
                        </div>
                        <div class="lg-mock-row">
                            <div class="lg-mock-list"><i class="is-title"></i><i></i><i></i><i></i></div>
                            <div class="lg-mock-col"><div></div><div class="is-dark"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="lg-wrap lg-section" id="who">
            <span class="lg-eyebrow">Who it's for</span>
            <h2 class="lg-h2">Everyone in the chain</h2>
            <div class="lg-who">
                <article class="lg-who-card is-violet">
                    <h3>Sorting center staff</h3>
                    <p>Run the province hub: confirm arrivals, sort, dispatch, open town branches and add their staff.</p>
                    <a href="{{ route('logistics.login') }}">Log in →</a>
                </article>
                <article class="lg-who-card is-lilac">
                    <h3>Branch staff</h3>
                    <p>Confirm parcels that reach your town and send them out for delivery.</p>
                    <a href="{{ route('logistics.login') }}">Log in →</a>
                </article>
                <article class="lg-who-card is-night">
                    <h3>Shopping or selling?</h3>
                    <p>Buyers and sellers use the BoomBuy marketplace. Every order there ends up moving through this network.</p>
                    <a href="{{ route('home') }}">Go to the marketplace →</a>
                </article>
            </div>
        </section>

        <section class="lg-faq-wrap lg-section" id="faq">
            <h2 class="lg-h2 lg-center">Questions</h2>
            <div class="lg-faq">
                <details open>
                    <summary>Who can register a sorting center?</summary>
                    <p>A logistics company that runs the sorting center for a province. BoomBuy reviews every registration before the account is activated.</p>
                </details>
                <details>
                    <summary>Can branch staff use it too?</summary>
                    <p>Yes. A sorting center opens branches for its towns and creates accounts for their staff, who then confirm arrivals and send parcels out for delivery.</p>
                </details>
                <details>
                    <summary>How is cash on delivery handled?</summary>
                    <p>Cash collected at the door is recorded per parcel and handed in to the sorting center, which confirms it in the portal.</p>
                </details>
            </div>
        </section>

        <section class="lg-wrap lg-section">
            <div class="lg-cta">
                <span class="lg-orb lg-orb-c" aria-hidden="true"></span>
                <div class="lg-cta-copy">
                    <h2>Running a sorting center in your province?</h2>
                    <p>Register it. BoomBuy reviews every center before the account is activated.</p>
                </div>
                <div class="lg-cta-actions">
                    <a href="{{ route('logistics.register') }}" class="lg-btn lg-btn-lilac lg-btn-lg">Register a sorting center</a>
                    <a href="{{ route('logistics.login') }}" class="lg-btn lg-btn-outline lg-btn-lg">Log in</a>
                </div>
            </div>
        </section>

    </main>

    <footer class="lg-footer">
        <div class="lg-wrap lg-footer-row">
            <img src="{{ vasset('images/logo-wordmark.svg') }}" alt="BoomBuy" width="97" height="34">
            <span>© {{ now()->year }} BoomBuy Logistics</span>
            <span>Shopping instead? <a href="{{ route('home') }}">Go to the BoomBuy marketplace →</a></span>
        </div>
    </footer>

    @include('partials.pwa-register')

</body>
</html>
