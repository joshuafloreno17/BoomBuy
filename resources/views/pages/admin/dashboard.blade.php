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
