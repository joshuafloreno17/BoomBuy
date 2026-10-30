<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Profit — BoomBuy Rider'])

    <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/rider-profit.css') }}">
</head>

<body>

    <x-layout.rider-sidebar active="profit" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header">

                <div>
                    <h1>Profit</h1>
                    <p>Your delivery earnings for the selected date range.</p>
                </div>

                <form method="GET" action="{{ route('rider.profit') }}" class="filter-form">

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

            {{-- PAY PER JOB (A33) AND COD TO REMIT (A24) --}}
            <section class="rider-pay" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin:18px 0;">
                <div class="summary-card"><span>Today</span><strong data-pay-today>₱{{ number_format($payToday['total'], 2) }}</strong><small>{{ $payToday['pickups'] }} pickup(s) × ₱{{ number_format(\App\Support\RiderJobs::PICKUP_PAY, 2) }} · {{ $payToday['deliveries'] }} delivery(ies)</small></div>
                <div class="summary-card"><span>This week</span><strong data-pay-week>₱{{ number_format($payWeek['total'], 2) }}</strong><small>Failed attempts pay ₱0.00</small></div>
                <div class="summary-card"><span>COD to remit</span><strong data-cod-balance>₱{{ number_format($cod['balance'], 2) }}</strong>
                    <small>{{ $cod['orders']->count() }} order(s){{ $cod['shortfall'] > 0 ? ' · ₱' . number_format($cod['shortfall'], 2) . ' outstanding' : '' }}{{ $cod['pending'] ? ' · ₱' . number_format($cod['pending']->amount, 2) . ' pending confirmation' : '' }}</small>
                </div>
            </section>
            @if($cod['held'] > 0 && !$cod['pending'])
                <form method="POST" action="{{ route('rider.cod.remit') }}" class="filter-form" style="margin-bottom:18px;">
                    @csrf
                    <div><label for="remitAmount">Remit COD (₱)</label><input type="number" id="remitAmount" name="amount" step="0.01" min="0.01" value="{{ number_format($cod['held'], 2, '.', '') }}"></div>
                    <button type="submit" class="filter-btn">Send remittance</button>
                </form>
            @endif


            <div class="stats">

                <div class="stat-card">
                    <span>Completed Deliveries</span>
                    <strong>{{ number_format($totalDeliveries) }}</strong>
                </div>

                <div class="stat-card">
                    <span>Fee per Delivery</span>
                    <strong>₱{{ number_format($deliveryFee, 2) }}</strong>
                </div>

                <div class="stat-card">
                    <span>Total Profit</span>
                    <strong>₱{{ number_format($totalProfit, 2) }}</strong>
                </div>

            </div>

            <div class="panel">
                <h2>Earnings per Day</h2>
                @if($totalDeliveries === 0)
                    <p class="chart-empty">No completed deliveries in this date range yet.</p>
                @else
                    <div class="chart-box"><canvas id="profitChart"></canvas></div>
                @endif
            </div>

            <div class="panel">
                <h2>Daily Breakdown</h2>

                <table>
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>DELIVERIES</th>
                            <th>PROFIT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyProfit as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row['day'])->format('M d, Y') }}</td>
                                <td>{{ $row['deliveries'] }}</td>
                                <td>₱{{ number_format($row['profit'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="empty-message">No completed deliveries in this date range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </main>

    @include('partials.pwa-register')

    @if($totalDeliveries > 0)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
        @include('partials.chart-defaults')
        <script>
            var profitLabels = @json($chartDays->map(fn($r) => \Carbon\Carbon::parse($r['day'])->format('M d')));
            var profitValues = @json($chartDays->map(fn($r) => (float) $r['profit']));
            var deliveryCounts = @json($chartDays->map(fn($r) => (int) $r['deliveries']));

            // Bars = ₱ earned that day; line = how many deliveries.
            bbCharts(function () {
                new Chart(document.getElementById('profitChart'), {
                    data: {
                        labels: profitLabels,
                        datasets: [{
                            type: 'bar',
                            label: 'Profit',
                            data: profitValues,
                            backgroundColor: '#b5a4e2',
                            hoverBackgroundColor: '#6d4ad1',
                            borderRadius: 5,
                            maxBarThickness: 26,
                            yAxisID: 'y',
                        }, {
                            type: 'line',
                            label: 'Deliveries',
                            data: deliveryCounts,
                            borderColor: '#1f9d6b',
                            backgroundColor: '#1f9d6b',
                            tension: 0,
                            pointRadius: profitLabels.length > 45 ? 0 : 3,
                            yAxisID: 'y1',
                        }]
                    },
                    options: {
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: true, position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } },
                            tooltip: { callbacks: { label: function (c) {
                                return c.dataset.yAxisID === 'y' ? 'Profit: ' + bbPeso(c.parsed.y) : 'Deliveries: ' + c.parsed.y;
                            } } }
                        },
                        scales: {
                            y: { beginAtZero: true, suggestedMax: 200, ticks: { callback: bbPesoAxis, maxTicksLimit: 6 } },
                            y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { precision: 0, maxTicksLimit: 6 }, suggestedMax: 4 },
                            x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } }
                        }
                    }
                });
            });
        </script>
    @endif

</body>
</html>
