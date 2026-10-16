<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Admin Dashboard — BoomBuy'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/portal-dash.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/admin-dashboard.css') }}">
</head>

<body>

    <x-layout.admin-sidebar active="dashboard" />

    <main class="main">
        <div class="pd">

            @php
                $peso = fn ($v) => '₱' . number_format((float) $v, (float) $v == floor((float) $v) ? 0 : 2);
                $typeLabels = ['seller' => 'Seller', 'buyer' => 'Buyer', 'logistics' => 'Logistics'];
                $typePlurals = ['seller' => 'Sellers', 'buyer' => 'Buyers', 'logistics' => 'Logistics'];
                $plural = fn ($n, $one, $many) => $n . ' ' . ((int) $n === 1 ? $one : $many);
                $ordersTodayCount = (int) ($ordersToday->total ?? 0);
            @endphp

            <header class="pd-head">
                <div class="pd-head-text">
                    <p class="pd-eyebrow">Marketplace overview · {{ now()->format('l, F j') }}</p>
                    <h1 class="pd-title">Dashboard</h1>
                </div>
                <form class="pd-search" action="{{ route('admin.accounts') }}" method="GET" role="search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" name="q" placeholder="Search users and shops" aria-label="Search users and shops">
                </form>
                <a href="{{ route('admin.settings') }}" class="pd-btn pd-btn-ghost"><i class="bi bi-megaphone"></i> Post announcement</a>
            </header>

            @if(session('success'))
                <div class="pd-alert is-ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif

            <section class="pd-kpis" aria-label="Marketplace at a glance">
                <a href="{{ route('admin.orders') }}" class="pd-kpi is-accent">
                    <span class="pd-kpi-label">Orders today</span>
                    <span class="pd-kpi-value">{{ $ordersTodayCount }}</span>
                    <span class="pd-kpi-note">{{ $peso($ordersToday->value ?? 0) }} in products</span>
                </a>
                <a href="{{ route('admin.reports') }}" class="pd-kpi">
                    <span class="pd-kpi-label">Commission today</span>
                    <span class="pd-kpi-value">{{ $peso($commissionToday) }}</span>
                    <span class="pd-kpi-note">{{ rtrim(rtrim(number_format($commissionRate, 2), '0'), '.') }}% of delivered sales</span>
                </a>
                <a href="{{ route('admin.accounts', ['role' => 'seller']) }}" class="pd-kpi">
                    <span class="pd-kpi-label">Active sellers</span>
                    <span class="pd-kpi-value">{{ $activeSellers }}</span>
                    <span class="pd-kpi-note">{{ ($pendingByType['seller'] ?? 0) > 0 ? $pendingByType['seller'] . ' waiting for approval' : 'No pending sellers' }}</span>
                </a>
                <a href="{{ route('admin.accounts', ['role' => 'rider']) }}" class="pd-kpi">
                    <span class="pd-kpi-label">Active riders</span>
                    <span class="pd-kpi-value">{{ $activeRiders }}</span>
                    <span class="pd-kpi-note">{{ $ridersOnRoad }} out on delivery now</span>
                </a>
            </section>

            <div class="pd-row">
                <section class="pd-card pd-grow-2" aria-labelledby="queue-title">
                    <div class="pd-card-head">
                        <h2 class="pd-card-title" id="queue-title">Applications to review</h2>
                        <div class="pd-chips" aria-label="Pending by type">
                            <a href="{{ route('admin.applications', ['status' => 'pending']) }}" class="pd-chip is-on">All · {{ $pendingApplications }}</a>
                            @foreach($typeLabels as $type => $label)
                                <a href="{{ route('admin.applications', ['type' => $type, 'status' => 'pending']) }}" class="pd-chip">{{ $typePlurals[$type] }} ·{{ $pendingByType[$type] ?? 0 }}</a>
                            @endforeach
                        </div>
                    </div>

                    @if($reviewQueue->isEmpty())
                        <div class="pd-empty"><i class="bi bi-check2-circle"></i>Nothing to review. New applications show up here.</div>
                    @else
                        <div class="pd-list">
                            @foreach($reviewQueue as $application)
                                <div class="pd-list-row">
                                    <span class="pd-avatar">{{ mb_strtoupper(mb_substr($application['name'] ?? '?', 0, 2)) }}</span>
                                    <span class="pd-list-main">
                                        <strong>{{ $application['name'] }}</strong>
                                        <span>{{ $application['detail'] ? $application['detail'] . ' · ' : '' }}submitted {{ \Carbon\Carbon::parse($application['created_at'])->diffForHumans() }}</span>
                                    </span>
                                    <span class="pd-tag">{{ $typeLabels[$application['type']] ?? ucfirst($application['type']) }}</span>
                                    <a href="{{ route('admin.applications', ['type' => $application['type'], 'status' => 'pending']) }}" class="pd-btn pd-btn-primary pd-btn-sm">Review</a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="pd-card pd-grow-1" aria-labelledby="compliance-title">
                    <div class="pd-card-head">
                        <h2 class="pd-card-title" id="compliance-title">Keep an eye on</h2>
                    </div>
                    <div class="pd-todo">
                        <a href="{{ route('admin.complaints') }}" class="pd-todo-item {{ $pendingComplaints > 0 ? 'is-hot' : 'is-done' }}">
                            <span class="pd-todo-icon"><i class="bi bi-exclamation-triangle"></i></span>
                            <span class="pd-todo-text"><strong>Open complaints</strong><span>Waiting for a reply</span></span>
                            <span class="pd-todo-count">{{ $pendingComplaints }}</span>
                        </a>
                        <a href="{{ route('admin.products') }}" class="pd-todo-item {{ $flaggedProducts > 0 ? 'is-hot' : 'is-done' }}">
                            <span class="pd-todo-icon"><i class="bi bi-flag"></i></span>
                            <span class="pd-todo-text"><strong>Flagged products</strong><span>Hidden from the shop</span></span>
                            <span class="pd-todo-count">{{ $flaggedProducts }}</span>
                        </a>
                        <a href="{{ route('admin.compliance') }}" class="pd-todo-item {{ $suspendedSellers > 0 ? '' : 'is-done' }}">
                            <span class="pd-todo-icon"><i class="bi bi-shield-exclamation"></i></span>
                            <span class="pd-todo-text"><strong>Suspended sellers</strong><span>Review in Compliance</span></span>
                            <span class="pd-todo-count">{{ $suspendedSellers }}</span>
                        </a>
                        <a href="{{ route('admin.orders') }}" class="pd-todo-item {{ $pendingCount > 0 ? '' : 'is-done' }}">
                            <span class="pd-todo-icon"><i class="bi bi-hourglass-split"></i></span>
                            <span class="pd-todo-text"><strong>Pending orders</strong><span>Not yet accepted by sellers</span></span>
                            <span class="pd-todo-count">{{ $pendingCount }}</span>
                        </a>
                    </div>
                </section>
            </div>

            @php
                // New accounts per month: grouped bars on one scale, rounded up to a tidy top.
                $roleColors = ['buyer' => '#e8420f', 'seller' => '#f59e0b', 'logistics' => '#1b1a1f', 'rider' => '#a99da3'];
                $roleNames = ['buyer' => 'Buyers', 'seller' => 'Sellers', 'logistics' => 'Logistics staff', 'rider' => 'Riders'];
                $peak = max(1, collect($registrations)->flatMap(fn ($m) => $m['counts'])->max());
                $step = max(1, (int) ceil($peak / 3 / (10 ** max(0, strlen((string) (int) ceil($peak / 3)) - 1))) * (10 ** max(0, strlen((string) (int) ceil($peak / 3)) - 1)));
                $top = $step * 3;
                $chartH = 180;
                $slot = 600 / max(1, count($registrations));
                $barW = min(16, ($slot - 24) / 4);
                $y = fn ($v) => 20 + $chartH - ($v / $top) * $chartH;

                $donutParts = [
                    'Approved' => ['Approved', '#15803d'],
                    'Pending Verification' => ['Pending', '#d97706'],
                    'Rejected' => ['Rejected', '#dc2626'],
                ];
                $donutLinks = [
                    'buyer' => [route('admin.buyers', ['tab' => 'applications']), 'Buyer applications'],
                    'seller' => [route('admin.sellers', ['tab' => 'applications']), 'Seller applications'],
                    'logistics' => [route('admin.logistics', ['tab' => 'staff', 'view' => 'applications']), 'Logistics applications'],
                ];
            @endphp

            <div class="pd-row">
                <section class="pd-card pd-grow-2" aria-labelledby="joined-title">
                    <div class="pd-card-head">
                        <h2 class="pd-card-title" id="joined-title">New accounts per month</h2>
                        <span class="pd-sub" style="font-size:12.5px;">By role, last 6 months</span>
                    </div>
                    <svg viewBox="0 0 660 230" role="img" style="width:100%; height:auto; display:block;"
                         aria-label="New accounts per month: {{ collect($registrations)->map(fn ($m) => $m['label'] . ' ' . collect($m['counts'])->map(fn ($c, $r) => $c . ' ' . strtolower($roleNames[$r]))->implode(', '))->implode('; ') }}">
                        @foreach ([0, $step, $step * 2, $top] as $tick)
                            <line x1="44" x2="650" y1="{{ $y($tick) }}" y2="{{ $y($tick) }}" stroke="#f0e2da" @if($tick > 0) stroke-dasharray="3 4" @endif />
                            <text x="36" y="{{ $y($tick) + 4 }}" text-anchor="end" font-size="11" fill="#8a7f86">{{ $tick }}</text>
                        @endforeach
                        @foreach ($registrations as $i => $month)
                            @php $x0 = 50 + $i * $slot + ($slot - $barW * 4 - 6) / 2; @endphp
                            @foreach (array_keys($roleColors) as $r => $role)
                                @php $v = $month['counts'][$role]; @endphp
                                @if ($v > 0)
                                    <rect x="{{ $x0 + $r * ($barW + 2) }}" y="{{ $y($v) }}" width="{{ $barW }}" height="{{ max(2, 20 + $chartH - $y($v)) }}" rx="3" fill="{{ $roleColors[$role] }}">
                                        <title>{{ $month['label'] }}: {{ $v }} {{ strtolower($roleNames[$role]) }}</title>
                                    </rect>
                                    @if ($v === max($month['counts']) && $v > 0)
                                        <text x="{{ $x0 + $r * ($barW + 2) + $barW / 2 }}" y="{{ $y($v) - 5 }}" text-anchor="middle" font-size="11" font-weight="700" fill="#1b1a1f">{{ $v }}</text>
                                    @endif
                                @endif
                            @endforeach
                            <text x="{{ 50 + $i * $slot + $slot / 2 }}" y="220" text-anchor="middle" font-size="12" fill="{{ $loop->last ? '#1b1a1f' : '#8a7f86' }}" font-weight="{{ $loop->last ? 700 : 400 }}">{{ $month['label'] }}</text>
                        @endforeach
                    </svg>
                    <div style="display:flex; flex-wrap:wrap; gap:6px 16px; margin-top:8px; font-size:12.5px; color:#6b6570;">
                        @foreach ($roleColors as $role => $color)
                            <span><i style="display:inline-block; width:10px; height:10px; border-radius:3px; background:{{ $color }}; margin-right:6px; vertical-align:-1px;"></i>{{ $roleNames[$role] }}</span>
                        @endforeach
                    </div>
                </section>

                <section class="pd-card pd-grow-1" aria-labelledby="apps-title">
                    <div class="pd-card-head">
                        <h2 class="pd-card-title" id="apps-title">Applications</h2>
                        <span class="pd-sub" style="font-size:12.5px;">Status of every application</span>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:14px;">
                        @foreach ($donutLinks as $type => [$url, $title])
                            @php
                                $counts = $applicationStatus[$type];
                                $total = max(0, (int) $counts->sum());
                                $offset = 0;
                            @endphp
                            <a href="{{ $url }}" style="display:flex; align-items:center; gap:14px; color:inherit; text-decoration:none;">
                                <svg viewBox="0 0 42 42" width="64" height="64" style="flex:none;" aria-label="{{ $title }}: {{ $total }}">
                                    <circle cx="21" cy="21" r="15.9" fill="none" stroke="#f0e2da" stroke-width="6"/>
                                    @if ($total > 0)
                                        @foreach ($donutParts as $status => [$label, $color])
                                            @php $share = ($counts[$status] ?? 0) / $total * 100; @endphp
                                            @if ($share > 0)
                                                <circle cx="21" cy="21" r="15.9" fill="none" stroke="{{ $color }}" stroke-width="6"
                                                        stroke-dasharray="{{ $share }} {{ 100 - $share }}" stroke-dashoffset="{{ -$offset }}" transform="rotate(-90 21 21)"/>
                                                @php $offset += $share; @endphp
                                            @endif
                                        @endforeach
                                    @endif
                                    <text x="21" y="24.5" text-anchor="middle" font-size="10" font-weight="800" fill="#1b1a1f">{{ $total }}</text>
                                </svg>
                                <span style="min-width:0;">
                                    <strong style="display:block; font-size:13.5px;">{{ $title }}</strong>
                                    <span style="font-size:12.5px; color:#6b6570;">
                                        @foreach ($donutParts as $status => [$label, $color])
                                            <span style="white-space:nowrap;"><i style="display:inline-block; width:8px; height:8px; border-radius:50%; background:{{ $color }}; margin-right:4px;"></i>{{ $label }} {{ $counts[$status] ?? 0 }}</span>@if (!$loop->last) · @endif
                                        @endforeach
                                    </span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>

            <section class="pd-kpis" aria-label="All time">
                <div class="pd-kpi">
                    <span class="pd-kpi-label">Delivered sales, all time</span>
                    <span class="pd-kpi-value">{{ $peso($totalSales) }}</span>
                    <span class="pd-kpi-note">{{ $deliveredCount }} delivered of {{ $totalOrders }} orders</span>
                </div>
                <a href="{{ route('admin.accounts') }}" class="pd-kpi">
                    <span class="pd-kpi-label">Accounts</span>
                    <span class="pd-kpi-value">{{ $totalUsers }}</span>
                    <span class="pd-kpi-note">{{ $plural($buyerCount, 'buyer', 'buyers') }} · {{ $plural($sellerCount, 'seller', 'sellers') }} · {{ $plural($riderCount, 'rider', 'riders') }}</span>
                </a>
                <a href="{{ route('admin.products') }}" class="pd-kpi">
                    <span class="pd-kpi-label">Products</span>
                    <span class="pd-kpi-value">{{ $totalProducts }}</span>
                    <span class="pd-kpi-note">Listed by sellers</span>
                </a>
            </section>

            <section class="pd-card" aria-labelledby="orders-title">
                <div class="pd-card-head">
                    <h2 class="pd-card-title" id="orders-title">Recent orders</h2>
                    <a href="{{ route('admin.orders') }}" class="pd-link">All orders →</a>
                </div>
                @if(empty($orders))
                    <div class="pd-empty"><i class="bi bi-receipt"></i>No orders yet.</div>
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
                                            <a href="{{ route('admin.order.details', $order['id']) }}" class="pd-strong" style="color:#1b1a1f;">#{{ $order['id'] }}</a>
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

    @include('partials.pwa-register')
</body>
</html>
