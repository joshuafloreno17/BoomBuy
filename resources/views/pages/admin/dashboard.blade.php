<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Admin Dashboard — BoomBuy'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/portal-dash.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/admin-dashboard.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/views/admin-dashboard-final.css') }}">
</head>

<body>

    <x-layout.admin-sidebar active="dashboard" />

    <main class="main">
        <div class="ad">

            @php
                $peso = fn ($v) => '₱' . number_format((float) $v, (float) $v == floor((float) $v) ? 0 : 2);
                $plural = fn ($n, $one, $many) => $n . ' ' . ((int) $n === 1 ? $one : $many);
                $ordersTodayCount = (int) ($ordersToday->total ?? 0);
                $adminName = \App\Support\SupportAccount::user()?->name ?: 'Admin';
                $hour = (int) now()->format('G');
                $greeting = $hour >= 5 && $hour < 12 ? 'Good morning' : ($hour >= 12 && $hour < 18 ? 'Good afternoon' : 'Good evening');

                $types = [
                    'buyer' => ['label' => 'Buyers', 'one' => 'BUYER', 'tone' => 'is-violet', 'icon' => 'bi-bag'],
                    'seller' => ['label' => 'Sellers', 'one' => 'SELLER', 'tone' => 'is-pink', 'icon' => 'bi-shop'],
                    'logistics' => ['label' => 'Logistics', 'one' => 'LOGISTICS', 'tone' => 'is-blue', 'icon' => 'bi-truck'],
                ];
                $firstType = collect(array_keys($types))->first(fn ($t) => count($inbox[$t]) > 0) ?? 'buyer';
                $categories = \App\Support\Categories::LIST;
                $docLabels = [
                    'seller' => \App\Support\SellerTypes::DOCUMENTS,
                    'buyer' => ['id_photo' => 'Valid ID'],
                    'logistics' => ['sec_certificate' => 'SEC Certificate', 'bir_2303' => 'BIR Form 2303', 'business_permit' => "Mayor's / Business Permit", 'id_photo' => 'Valid ID of Representative'],
                ];

                // Sales + commission line chart (6 months).
                $chartTop = max(1, collect($salesByMonth)->max('sales'));
                $cx = fn ($i) => 30 + $i * (660 / max(1, count($salesByMonth) - 1));
                $cy = fn ($v) => 190 - ($v / $chartTop) * 160;
                $line = fn ($key) => collect($salesByMonth)->values()->map(fn ($m, $i) => round($cx($i), 1) . ',' . round($cy($m[$key]), 1))->implode(' ');
                $lastMonth = collect($salesByMonth)->last();

                // Accounts donut.
                $roles = [
                    ['label' => 'Buyers', 'count' => $buyerCount, 'color' => '#6d4ad1'],
                    ['label' => 'Sellers', 'count' => $sellerCount, 'color' => '#c9b6ff'],
                    ['label' => 'Riders', 'count' => $riderCount, 'color' => '#4ade80'],
                    ['label' => 'Logistics', 'count' => $logisticsCount, 'color' => '#93b4ff'],
                ];
                $donutR = 44;
                $donutC = 2 * M_PI * $donutR;
                $donutOffset = 0;
            @endphp

            <section class="ad-hero">
                <span class="ad-orb" aria-hidden="true"></span>
                <div class="ad-hero-text">
                    <p class="ad-eyebrow"><span class="ad-live" aria-hidden="true"></span>{{ now()->format('l, F j') }} · Platform overview</p>
                    <h1><span data-greet>{{ $greeting }}</span>, {{ $adminName }}</h1>
                    <p>
                        <b>{{ $plural($pendingApplications, 'application', 'applications') }}</b> and
                        <b>{{ $plural($pendingComplaints, 'complaint', 'complaints') }}</b> are waiting for you.
                    </p>
                </div>
                <div class="ad-hero-actions">
                    <form class="ad-search" action="{{ route('admin.accounts') }}" method="GET" role="search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" name="q" placeholder="Search users and shops" aria-label="Search users and shops">
                    </form>
                    <a href="{{ route('admin.settings') }}" class="ad-btn ad-btn-glass"><i class="bi bi-megaphone"></i> Post announcement</a>
                    <a href="{{ route('admin.complaints') }}" class="ad-btn ad-btn-lilac">Open complaints</a>
                </div>
            </section>

            @if(session('success'))
                <div class="pd-alert is-ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="pd-alert is-error"><i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}</div>
            @endif

            <section class="ad-kpis" aria-label="Marketplace at a glance">
                <a href="{{ route('admin.orders') }}" class="ad-kpi is-violet">
                    <span class="ad-kpi-top"><span>Orders today</span><i class="bi bi-cart3"></i></span>
                    <strong>{{ $ordersTodayCount }}</strong>
                    <span class="ad-kpi-note">{{ $peso($ordersToday->value ?? 0) }} in products</span>
                </a>
                <a href="{{ route('admin.reports') }}" class="ad-kpi is-night">
                    <span class="ad-kpi-top"><span>Commission today</span><i class="bi bi-graph-up-arrow"></i></span>
                    <strong>{{ $peso($commissionToday) }}</strong>
                    <span class="ad-kpi-note">{{ rtrim(rtrim(number_format($commissionRate, 2), '0'), '.') }}% of delivered sales</span>
                </a>
                <a href="{{ route('admin.accounts', ['role' => 'seller']) }}" class="ad-kpi">
                    <span class="ad-kpi-top"><span>Active sellers</span><i class="bi bi-shop"></i></span>
                    <strong>{{ $activeSellers }}</strong>
                    <span class="ad-kpi-note">{{ ($pendingByType['seller'] ?? 0) > 0 ? $pendingByType['seller'] . ' waiting for approval' : 'No pending sellers' }}</span>
                </a>
                <a href="{{ route('admin.accounts', ['role' => 'rider']) }}" class="ad-kpi">
                    <span class="ad-kpi-top"><span>Active riders</span><i class="bi bi-bicycle"></i></span>
                    <strong>{{ $activeRiders }}</strong>
                    <span class="ad-kpi-note">{{ $ridersOnRoad }} out on delivery now</span>
                </a>
            </section>

            {{-- APPROVAL INBOX --}}
            <section class="ad-inbox" aria-labelledby="inbox-title" data-inbox>
                <div class="ad-inbox-list">
                    <div class="ad-inbox-head">
                        <div class="ad-inbox-title">
                            <h2 id="inbox-title">Applications</h2>
                            <span class="ad-pill {{ $pendingApplications > 0 ? 'is-green' : '' }}">{{ $pendingApplications }} waiting</span>
                        </div>
                        <div class="ad-tabs" role="tablist" aria-label="Application type">
                            @foreach($types as $type => $t)
                                <button type="button" role="tab" class="ad-tab" data-inbox-tab="{{ $type }}" aria-selected="{{ $type === $firstType ? 'true' : 'false' }}">
                                    {{ $t['label'] }} <span>{{ $pendingByType[$type] ?? 0 }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @foreach($types as $type => $t)
                        <div class="ad-inbox-items" data-inbox-list="{{ $type }}" @if($type !== $firstType) hidden @endif>
                            @forelse($inbox[$type] as $app)
                                @php $appName = $type !== 'buyer' && !empty($app->business_name) ? $app->business_name : $app->full_name; @endphp
                                <button type="button" class="ad-item" data-inbox-item="{{ $type }}-{{ $app->id }}" aria-pressed="{{ $type === $firstType && $loop->first ? 'true' : 'false' }}">
                                    <span class="ad-avatar {{ $t['tone'] }}">{{ mb_strtoupper(mb_substr($appName, 0, 1)) }}</span>
                                    <span class="ad-item-main">
                                        <strong>{{ $appName }}</strong>
                                        <span>{{ $type === 'seller' && $app->business_category ? ($categories[$app->business_category] ?? $app->business_category) . ' · ' : '' }}{{ $type !== 'buyer' ? $app->full_name : \Illuminate\Support\Str::limit($app->address, 34) }}</span>
                                    </span>
                                    <span class="ad-item-ago">{{ \Carbon\Carbon::parse($app->created_at)->diffForHumans(null, true) }}</span>
                                </button>
                            @empty
                                <div class="ad-empty"><i class="bi bi-check2-circle"></i>No {{ strtolower($t['label']) }} waiting. Nice!</div>
                            @endforelse
                            @if(($pendingByType[$type] ?? 0) > count($inbox[$type]))
                                <a href="{{ route('admin.applications', ['type' => $type, 'status' => 'pending']) }}" class="ad-link ad-more">See all {{ $pendingByType[$type] }} →</a>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="ad-inbox-detail">
                    @foreach($types as $type => $t)
                        @foreach($inbox[$type] as $app)
                            @php
                                $appName = $type !== 'buyer' && !empty($app->business_name) ? $app->business_name : $app->full_name;
                                $fields = array_filter([
                                    'Full name' => $app->full_name,
                                    'E-mail' => $app->user_email,
                                    'Contact no.' => $app->phone,
                                    'Sex' => $app->user_sex,
                                    'Birthday · age' => $app->user_birthdate ? \Carbon\Carbon::parse($app->user_birthdate)->format('M j, Y') . ($app->user_age ? ' · ' . $app->user_age : '') : null,
                                    'Address' => $app->address,
                                    'Line of business' => $type === 'seller' && $app->business_category ? ($categories[$app->business_category] ?? $app->business_category) : null,
                                    'Seller type' => $app->seller_type ?? null,
                                    'Position' => $app->position ?? null,
                                    'TIN' => $app->tin ?? null,
                                    'Applied' => \Carbon\Carbon::parse($app->created_at)->format('M j, Y g:i A'),
                                ]);
                            @endphp
                            <article class="ad-app" data-inbox-detail="{{ $type }}-{{ $app->id }}" @unless($type === $firstType && $loop->first) hidden @endunless>
                                <div class="ad-app-head">
                                    <span class="ad-avatar is-lg {{ $t['tone'] }}">{{ mb_strtoupper(mb_substr($appName, 0, 1)) }}</span>
                                    <div class="ad-app-who">
                                        <span class="ad-app-role">{{ $t['one'] }} APPLICATION</span>
                                        <h3>{{ $appName }}</h3>
                                    </div>
                                    <span class="ad-pill is-amber">Pending review</span>
                                </div>

                                <div class="ad-fields">
                                    @foreach($fields as $label => $value)
                                        <div><small>{{ $label }}</small><span>{{ $value }}</span></div>
                                    @endforeach
                                </div>

                                <div>
                                    <div class="ad-subtitle">Uploaded documents</div>
                                    <div class="ad-docs">
                                        @foreach($docLabels[$type] as $field => $label)
                                            @continue($type === 'seller' && empty($app->$field) && !in_array($field, \App\Support\SellerTypes::REQUIRED_DOCUMENTS[$app->seller_type ?? ''] ?? ['national_id', 'business_permit'], true))
                                            @if(!empty($app->$field))
                                                <a class="ad-doc" href="{{ route('admin.applications.document', ['type' => $type, 'id' => $app->id, 'field' => $field]) }}" data-viewer="app-{{ $type }}-{{ $app->id }}" data-viewer-badge="{{ $t['one'] }} application" data-viewer-title="{{ $label }}" data-viewer-meta="{{ $appName }}"><i class="bi bi-file-earmark-image"></i><span>{{ $label }}</span><em>View</em></a>
                                            @else
                                                <span class="ad-doc is-missing"><i class="bi bi-file-earmark-x"></i><span>No {{ $label }}</span></span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>

                                <div class="ad-decide">
                                    <form method="POST" action="{{ route('admin.applications.approve', ['type' => $type, 'id' => $app->id]) }}" class="ad-approve">
                                        @csrf
                                        @if($type === 'seller')
                                            <label class="ad-select">
                                                <small>Line of business</small>
                                                <select name="business_category" {{ empty($app->business_category) ? 'required' : '' }}>
                                                    <option value="">{{ empty($app->business_category) ? 'Choose a category…' : 'Change category…' }}</option>
                                                    @foreach($categories as $value => $label)
                                                        <option value="{{ $value }}" {{ $app->business_category === $value ? 'selected' : '' }}>{{ $label }}{{ $app->business_category === $value ? ' (declared by seller)' : '' }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                        @endif
                                        <button type="submit" class="ad-btn ad-btn-green"><i class="bi bi-check-lg"></i> Approve &amp; email</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.applications.reject', ['type' => $type, 'id' => $app->id]) }}" class="ad-reject"
                                          data-confirm="Reject the application of {{ $app->full_name }}? They will be notified by email." data-confirm-ok="Reject" data-confirm-danger>
                                        @csrf
                                        <label class="ad-select">
                                            <small>Reason (in the email)</small>
                                            <input type="text" name="admin_remarks" placeholder="e.g. The ID photo is blurry.">
                                        </label>
                                        <button type="submit" class="ad-btn ad-btn-outline-red">Reject &amp; email</button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    @endforeach

                    <div class="ad-inbox-none" data-inbox-none @if(count($inbox[$firstType]) > 0) hidden @endif>
                        <i class="bi bi-inbox"></i>
                        <strong>Nothing to review here</strong>
                        <span>New applications show up on the left. Every decision is emailed to the applicant.</span>
                    </div>
                </div>
            </section>

            <div class="ad-row">
                <section class="ad-card ad-grow-2" aria-labelledby="sales-title">
                    <div class="ad-card-head">
                        <div>
                            <h2 id="sales-title">Sales and commission</h2>
                            <p class="ad-muted">Delivered orders · last 6 months</p>
                        </div>
                        <div class="ad-legend"><span><i style="background:#6d4ad1"></i>Sales</span><span><i style="background:#4ade80"></i>Commission</span></div>
                    </div>
                    <svg class="ad-chart" viewBox="0 0 720 230" role="img" aria-label="Sales and commission per month: {{ collect($salesByMonth)->map(fn ($m) => $m['label'] . ' ' . $peso($m['sales']) . ' sales, ' . $peso($m['commission']) . ' commission')->implode('; ') }}">
                        <defs><linearGradient id="adFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#6d4ad1" stop-opacity=".28"/><stop offset="1" stop-color="#6d4ad1" stop-opacity="0"/></linearGradient></defs>
                        @foreach([30, 70, 110, 150, 190] as $gy)
                            <line x1="30" x2="690" y1="{{ $gy }}" y2="{{ $gy }}" stroke="#f1edfb"/>
                        @endforeach
                        <polygon points="30,190 {{ $line('sales') }} 690,190" fill="url(#adFill)"/>
                        <polyline class="ad-line" points="{{ $line('sales') }}" fill="none" stroke="#6d4ad1" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <polyline class="ad-line is-late" points="{{ $line('commission') }}" fill="none" stroke="#4ade80" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                        @foreach($salesByMonth as $i => $m)
                            <circle cx="{{ $cx($i) }}" cy="{{ $cy($m['sales']) }}" r="{{ $loop->last ? 7 : 4 }}" fill="#6d4ad1" stroke="#fff" stroke-width="{{ $loop->last ? 3 : 2 }}"><title>{{ $m['label'] }}: {{ $peso($m['sales']) }} sales, {{ $peso($m['commission']) }} commission</title></circle>
                            <text x="{{ $cx($i) }}" y="216" text-anchor="middle" class="ad-chart-label {{ $loop->last ? 'is-now' : '' }}">{{ $m['label'] }}</text>
                        @endforeach
                    </svg>
                    <div class="ad-chart-foot">
                        <span><small>{{ $lastMonth['label'] }} sales</small><b>{{ $peso($lastMonth['sales']) }}</b></span>
                        <span><small>{{ $lastMonth['label'] }} commission</small><b class="is-green">{{ $peso($lastMonth['commission']) }}</b></span>
                        <span><small>All-time delivered</small><b>{{ $peso($totalSales) }}</b></span>
                        <a href="{{ route('admin.reports') }}" class="ad-link">Reports →</a>
                    </div>
                </section>

                <section class="ad-card ad-grow-1" aria-labelledby="activity-title">
                    <div class="ad-card-head">
                        <h2 id="activity-title">Activity</h2>
                        <span class="ad-live-label"><span class="ad-live" aria-hidden="true"></span>Latest</span>
                    </div>
                    @if(empty($activity))
                        <div class="ad-empty"><i class="bi bi-activity"></i>Nothing yet. New applications, complaints and deliveries show up here.</div>
                    @else
                        <ol class="ad-feed">
                            @foreach($activity as $a)
                                <li class="{{ $a['tone'] }}"><strong>{{ $a['text'] }}</strong><span>{{ $a['detail'] }} · {{ \Carbon\Carbon::parse($a['at'])->diffForHumans() }}</span></li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            </div>

            <div class="ad-row ad-row-stretch">
                <section class="ad-card ad-grow-1" aria-labelledby="trust-title">
                    <h2 id="trust-title">Trust &amp; safety</h2>
                    <a href="{{ route('admin.complaints') }}" class="ad-watch is-red {{ $pendingComplaints > 0 ? 'is-hot' : '' }}"><span class="ad-watch-icon"><i class="bi bi-flag"></i></span><span><strong>Open complaints</strong><small>Disputes waiting for a reply</small></span><b>{{ $pendingComplaints }}</b></a>
                    <a href="{{ route('admin.products') }}" class="ad-watch is-amber {{ $flaggedProducts > 0 ? 'is-hot' : '' }}"><span class="ad-watch-icon"><i class="bi bi-exclamation-triangle"></i></span><span><strong>Flagged products</strong><small>Hidden from the shop</small></span><b>{{ $flaggedProducts }}</b></a>
                    <a href="{{ route('admin.compliance') }}" class="ad-watch is-violet"><span class="ad-watch-icon"><i class="bi bi-shield-exclamation"></i></span><span><strong>Suspended sellers</strong><small>Review in Compliance</small></span><b>{{ $suspendedSellers }}</b></a>
                    <a href="{{ route('admin.orders') }}" class="ad-watch is-grey"><span class="ad-watch-icon"><i class="bi bi-hourglass-split"></i></span><span><strong>Pending orders</strong><small>Not yet accepted by sellers</small></span><b>{{ $pendingCount }}</b></a>
                </section>

                <section class="ad-card ad-grow-1" aria-labelledby="accounts-title">
                    <div class="ad-card-head">
                        <h2 id="accounts-title">Accounts</h2>
                        <a href="{{ route('admin.accounts') }}" class="ad-link">Manage →</a>
                    </div>
                    <div class="ad-donut-wrap">
                        <svg class="ad-donut" viewBox="0 0 120 120" role="img" aria-label="{{ $totalUsers }} accounts: {{ collect($roles)->map(fn ($r) => $r['count'] . ' ' . strtolower($r['label']))->implode(', ') }}">
                            <circle cx="60" cy="60" r="{{ $donutR }}" fill="none" stroke="#f1edfb" stroke-width="18"/>
                            @if($totalUsers > 0)
                                @foreach($roles as $r)
                                    @php $len = $r['count'] / $totalUsers * $donutC; @endphp
                                    @if($len > 0)
                                        <circle cx="60" cy="60" r="{{ $donutR }}" fill="none" stroke="{{ $r['color'] }}" stroke-width="18" stroke-dasharray="{{ round($len, 2) }} {{ round($donutC, 2) }}" stroke-dashoffset="{{ round(-$donutOffset, 2) }}" transform="rotate(-90 60 60)"/>
                                        @php $donutOffset += $len; @endphp
                                    @endif
                                @endforeach
                            @endif
                            <text x="60" y="58" text-anchor="middle" class="ad-donut-num">{{ $totalUsers }}</text>
                            <text x="60" y="76" text-anchor="middle" class="ad-donut-label">accounts</text>
                        </svg>
                        <ul class="ad-roles">
                            @foreach($roles as $r)
                                <li><i style="background: {{ $r['color'] }}"></i>{{ $r['label'] }}<b>{{ $r['count'] }}</b></li>
                            @endforeach
                        </ul>
                    </div>
                    <p class="ad-muted">{{ $totalProducts }} {{ \Illuminate\Support\Str::plural('product', $totalProducts) }} listed by sellers</p>
                </section>

                <section class="ad-site" aria-labelledby="site-title">
                    <span class="ad-orb is-b" aria-hidden="true"></span>
                    <span class="ad-site-eyebrow" id="site-title">On the site now</span>
                    <strong>{{ $plural($liveBanners, 'promo banner', 'promo banners') }}{{ $liveAnnouncement ? ' · 1 announcement' : '' }}</strong>
                    <span>The landing page and buyer home show your banners first, then automatic slides.</span>
                    <a href="{{ route('admin.banners') }}" class="ad-btn ad-btn-white">Manage promo banners</a>
                </section>
            </div>

            <section class="ad-card" aria-labelledby="orders-title">
                <div class="ad-card-head">
                    <h2 id="orders-title">Recent orders</h2>
                    <a href="{{ route('admin.orders') }}" class="ad-link">All orders →</a>
                </div>
                @if(empty($orders))
                    <div class="ad-empty"><i class="bi bi-receipt"></i>No orders yet.</div>
                @else
                    <div class="pd-table-wrap">
                        <table class="pd-table pd-stack">
                            <thead>
                                <tr><th>Order</th><th>Buyer</th><th>Items</th><th>Total</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                    @php
                                        $items = $order['items'] ?? [];
                                        $first = $items[0]['product_name'] ?? 'Order items';
                                        $more = count($items) - 1;
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.order.details', $order['id']) }}" class="pd-strong" style="color:#221a3a;">#{{ $order['id'] }}</a>
                                            <div class="pd-sub" style="font-size:12.5px;">{{ \Carbon\Carbon::parse($order['created_at'])->format('M j, g:i A') }}</div>
                                        </td>
                                        <td class="pd-hide-sm">{{ $order['buyer_name'] }}</td>
                                        <td class="pd-sub">{{ \Illuminate\Support\Str::limit($first, 34) }}{{ $more > 0 ? ' + ' . $more . ' more' : '' }}</td>
                                        <td class="pd-strong">₱{{ number_format($order['total'], 2) }}</td>
                                        <td><x-status-pill :status="$order['status']" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

        </div>
    </main>

    <script>
    (function () {
        // Greeting follows the viewer's own clock.
        var greet = document.querySelector('[data-greet]');
        if (greet) {
            var h = new Date().getHours();
            greet.textContent = h >= 5 && h < 12 ? 'Good morning' : (h >= 12 && h < 18 ? 'Good afternoon' : 'Good evening');
        }

        // Approval inbox: tabs switch the list; an item opens its details on the right.
        var box = document.querySelector('[data-inbox]');
        if (!box) return;

        var tabs = box.querySelectorAll('[data-inbox-tab]');
        var none = box.querySelector('[data-inbox-none]');

        function show(key) {
            box.querySelectorAll('[data-inbox-detail]').forEach(function (d) { d.hidden = d.dataset.inboxDetail !== key; });
            box.querySelectorAll('[data-inbox-item]').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.inboxItem === key ? 'true' : 'false'); });
            none.hidden = !!key;
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { t.setAttribute('aria-selected', t === tab ? 'true' : 'false'); });
                box.querySelectorAll('[data-inbox-list]').forEach(function (l) { l.hidden = l.dataset.inboxList !== tab.dataset.inboxTab; });
                var first = box.querySelector('[data-inbox-list="' + tab.dataset.inboxTab + '"] [data-inbox-item]');
                show(first ? first.dataset.inboxItem : null);
            });
        });

        box.querySelectorAll('[data-inbox-item]').forEach(function (item) {
            item.addEventListener('click', function () {
                show(item.dataset.inboxItem);
                if (window.matchMedia('(max-width: 900px)').matches) {
                    var d = box.querySelector('[data-inbox-detail="' + item.dataset.inboxItem + '"]');
                    if (d) d.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    })();
    </script>

    @include('partials.viewer-modal')

    @include('partials.pwa-register')
</body>
</html>
