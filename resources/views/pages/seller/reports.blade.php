<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Reports — BoomBuy Seller'])

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-reports.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="reports" :user="$user" />

    <main class="main-content">

        <div class="container">

            <x-seller-page-head title="Reports" subtitle="Your delivered sales, commission and earnings for the dates you pick.">
                <form method="GET" action="{{ route('seller.reports') }}" class="filter-form">

                    <div>
                        <label for="from">From</label>
                        <input type="date" id="from" name="from" value="{{ $from }}">
                    </div>

                    <div>
                        <label for="to">To</label>
                        <input type="date" id="to" name="to" value="{{ $to }}">
                    </div>

                    <button type="submit" class="filter-btn">Apply</button>

                </form>
            </x-seller-page-head>

            <div class="stats">

                <div class="stat-card">
                    <span>Total Sales (Delivered)</span>
                    <strong>₱{{ number_format($totalSales, 2) }}</strong>
                    @if(($voucherDiscounts ?? 0) > 0)
                        <small style="display:block; margin-top:4px; font-size:11px; opacity:.75;">
                            after ₱{{ number_format($voucherDiscounts, 2) }} in voucher discounts
                        </small>
                    @endif
                </div>

                <div class="stat-card">
                    <span>Delivered Orders</span>
                    <strong>{{ number_format($deliveredOrders) }}</strong>
                </div>

                <div class="stat-card commission">
                    <span>Platform Commission ({{ number_format($commissionRate, 1) }}%)</span>
                    <strong>₱{{ number_format($commissionOwed, 2) }}</strong>
                </div>

                <div class="stat-card net">
                    <span>Your Net Earnings</span>
                    <strong>₱{{ number_format($netEarnings, 2) }}</strong>
                </div>

            </div>

            <div class="panel">
                <h2>Sales Trend</h2>
                <div class="chart-box"><canvas id="salesTrendChart"></canvas></div>
            </div>

            <div class="panel">
                <h2>Top Products by Revenue</h2>
                @if($productSales->isEmpty())
                    <p class="chart-empty">No delivered sales in this range yet.</p>
                @else
                    {{-- Horizontal bars: room for product names; height grows with the list. --}}
                    <div class="chart-box" style="height: {{ 60 + min(5, $productSales->count()) * 44 }}px;"><canvas id="topProductsChart"></canvas></div>
                @endif
            </div>

            <div class="panel">
                <h2>Sales by Product</h2>

                <table>
                    <thead>
                        <tr>
                            <th>PRODUCT</th>
                            <th>UNITS SOLD</th>
                            <th>REVENUE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productSales as $row)
                            <tr>
                                <td>{{ $row->product_name }}</td>
                                <td>{{ number_format($row->units_sold) }}</td>
                                <td>₱{{ number_format($row->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="empty-message">No delivered sales in this date range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel">
                <h2>Daily Sales</h2>

                <table>
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>REVENUE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailySales as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->day)->format('M d, Y') }}</td>
                                <td>₱{{ number_format($row->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="empty-message">No delivered sales in this date range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </main>

</div>

    @include('partials.pwa-register')

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    @include('partials.chart-defaults')
    <script>
        var dailyLabels = @json($trendDays->map(fn($r) => \Carbon\Carbon::parse($r->day)->format('M d')));
        var dailyRevenue = @json($trendDays->map(fn($r) => (float) $r->revenue));

        var productLabels = @json($productSales->take(5)->map(fn($r) => $r->product_name));
        var productRevenue = @json($productSales->take(5)->map(fn($r) => (float) $r->revenue));

        bbCharts(function () {
            new Chart(document.getElementById('salesTrendChart'), {
                type: 'line',
                data: {
                    labels: dailyLabels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: dailyRevenue,
                        borderColor: '#e8420f',
                        backgroundColor: 'rgba(232,66,15,0.1)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: dailyLabels.length > 45 ? 0 : 3,
                        pointBackgroundColor: '#e8420f',
                    }]
                },
                options: {
                    plugins: { tooltip: { callbacks: { label: function (c) { return bbPeso(c.parsed.y); } } } },
                    scales: {
                        y: { beginAtZero: true, suggestedMax: 1000, ticks: { callback: bbPesoAxis, maxTicksLimit: 6 } },
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } }
                    }
                }
            });

            if (document.getElementById('topProductsChart')) new Chart(document.getElementById('topProductsChart'), {
                type: 'bar',
                data: {
                    labels: productLabels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: productRevenue,
                        backgroundColor: '#f4a582',
                        hoverBackgroundColor: '#e8420f',
                        borderRadius: 6,
                        maxBarThickness: 28,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    plugins: { tooltip: { callbacks: { label: function (c) { return bbPeso(c.parsed.x); } } } },
                    scales: {
                        x: { beginAtZero: true, ticks: { callback: bbPesoAxis, maxTicksLimit: 5 } },
                        y: { grid: { display: false }, ticks: { callback: function (v) { return bbShortLabel(this.getLabelForValue(v)); } } }
                    }
                }
            });
        });
    </script>

</body>
</html>
