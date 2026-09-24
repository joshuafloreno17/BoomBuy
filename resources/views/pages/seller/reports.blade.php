<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reports — BoomBuy Seller</title>

    @include('partials.pwa-head')

    <link rel="stylesheet" href="{{ asset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/seller-reports.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="reports" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header">

                <div>
                    <small>Seller Panel</small>
                    <h1>Reports</h1>
                </div>

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

            </div>

            <div class="stats">

                <div class="stat-card">
                    <span>Total Sales (Delivered)</span>
                    <strong>₱{{ number_format($totalSales, 2) }}</strong>
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
                <canvas id="salesTrendChart" height="90"></canvas>
            </div>

            <div class="panel">
                <h2>Top Products by Revenue</h2>
                <canvas id="topProductsChart" height="90"></canvas>
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
    <script>
        var dailyLabels = @json($dailySales->map(fn($r) => \Carbon\Carbon::parse($r->day)->format('M d')));
        var dailyRevenue = @json($dailySales->map(fn($r) => (float) $r->revenue));

        var productLabels = @json($productSales->take(5)->map(fn($r) => $r->product_name));
        var productRevenue = @json($productSales->take(5)->map(fn($r) => (float) $r->revenue));

        if (window.Chart) {
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
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            new Chart(document.getElementById('topProductsChart'), {
                type: 'bar',
                data: {
                    labels: productLabels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: productRevenue,
                        backgroundColor: '#f4a582',
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    </script>

</body>
</html>
