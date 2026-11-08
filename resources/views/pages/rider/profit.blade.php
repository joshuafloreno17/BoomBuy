<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Cash & Earnings — BoomBuy Rider'])

    <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/partials/rider-a.css') }}">
</head>

<body>

    <x-layout.rider-sidebar active="profit" :user="$user" />

    @php
        $todayStr = today()->toDateString();
        $presets = [
            'today' => ['label' => 'Today', 'from' => $todayStr, 'to' => $todayStr],
            'week' => ['label' => 'Week', 'from' => today()->startOfWeek()->toDateString(), 'to' => $todayStr],
            '30' => ['label' => '30 days', 'from' => today()->subDays(30)->toDateString(), 'to' => $todayStr],
        ];
        $activePreset = collect($presets)->search(fn ($p) => $p['from'] === $from && $p['to'] === $to);

        $fromDate = \Illuminate\Support\Carbon::parse($from);
        $toDate = \Illuminate\Support\Carbon::parse($to);
        $rangeLabel = $fromDate->isSameDay($toDate)
            ? $fromDate->format('M j, Y')
            : ($fromDate->year === $toDate->year
                ? $fromDate->format('M j') . ' – ' . ($fromDate->month === $toDate->month ? $toDate->format('j, Y') : $toDate->format('M j, Y'))
                : $fromDate->format('M j, Y') . ' – ' . $toDate->format('M j, Y'));
        $earnedTitle = match ($activePreset) {
            'today' => 'Earned today',
            'week' => 'Earned this week · ' . $rangeLabel,
            default => 'Earned · ' . $rangeLabel,
        };
    @endphp

    <main class="main-content">

        <div class="ra">

            <div class="ra-head">
                <span class="ra-kicker">RIDER CENTER</span>
                <h1>Cash &amp; earnings</h1>
            </div>

            @if(session('success'))
                <div class="ra-note is-success" role="status"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
            @endif
            @if(session('error'))
                <div class="ra-note is-error" role="alert"><i class="bi bi-x-circle-fill"></i><span>{{ session('error') }}</span></div>
            @endif

            <div class="ra-seg" role="group" aria-label="Date range">
                @foreach($presets as $key => $preset)
                    <a href="{{ route('rider.profit', ['from' => $preset['from'], 'to' => $preset['to']]) }}" @class(['is-on' => $activePreset === $key]) @if($activePreset === $key) aria-current="true" @endif>{{ $preset['label'] }}</a>
                @endforeach
                <button type="button" @class(['is-on' => $activePreset === false]) data-open-range><i class="bi bi-calendar3"></i> Custom</button>
            </div>

            <details class="ra-range" id="customRange" @if($activePreset === false) open @endif>
                <summary><i class="bi bi-calendar3"></i><span>{{ $rangeLabel }}</span><i class="bi bi-chevron-down"></i></summary>
                <form method="GET" action="{{ route('rider.profit') }}">
                    <label class="ra-field" for="from">From<input type="date" id="from" name="from" value="{{ $from }}"></label>
                    <label class="ra-field" for="to">To<input type="date" id="to" name="to" value="{{ $to }}"></label>
                    <button type="submit" class="ra-btn">Show these dates</button>
                </form>
            </details>

            {{-- PAY PER JOB (A33) for the chosen dates --}}
            <section class="ra-card" aria-label="Earnings">
                <div class="ra-stat">
                    <div>
                        <small>{{ $earnedTitle }}</small>
                        <strong style="font-size:34px;line-height:1.15">₱{{ number_format($payRange['total'], 2) }}</strong>
                    </div>
                </div>
                <div class="ra-lines">
                    <div class="ra-line"><span>Deliveries · {{ $payRange['deliveries'] }}</span><span>₱{{ number_format($payRange['delivery_pay'], 2) }}</span></div>
                    <div class="ra-line"><span>Pickups · {{ $payRange['pickups'] }} × ₱{{ number_format(\App\Support\RiderJobs::PICKUP_PAY, 2) }}</span><span>₱{{ number_format($payRange['pickup_pay'], 2) }}</span></div>
                    <div class="ra-line"><span>Failed attempts</span><span>₱0.00</span></div>
                </div>
            </section>

            {{-- COD TO REMIT (A24) --}}
            <section class="ra-card" aria-label="Cash on Delivery to remit" style="background:#fff7ec;border-color:#f6ddb8">
                <div class="ra-stat">
                    <span class="ra-stat-icon is-amber"><i class="bi bi-cash-stack"></i></span>
                    <div><small style="color:#7a4a12">COD to remit</small><strong data-cod-balance>₱{{ number_format($cod['balance'], 2) }}</strong></div>
                </div>
                <span style="font-size:12.5px;color:#7a4a12">
                    {{ $cod['orders']->count() }} order{{ $cod['orders']->count() === 1 ? '' : 's' }}{{ $cod['shortfall'] > 0 ? ' · ₱' . number_format($cod['shortfall'], 2) . ' outstanding' : '' }}{{ $cod['pending'] ? ' · ₱' . number_format($cod['pending']->amount, 2) . ' pending confirmation' : '' }}
                </span>
                @if($cod['balance'] > 0 && !$cod['pending'])
                    <form method="POST" action="{{ route('rider.cod.remit') }}" style="display:flex;flex-direction:column;gap:10px">
                        @csrf
                        <label class="ra-field" for="remitAmount" style="color:#7a4a12">Amount to remit (₱)<input type="number" id="remitAmount" name="amount" step="0.01" min="0.01" value="{{ number_format($cod['balance'], 2, '.', '') }}"></label>
                        <button type="submit" class="ra-btn is-dark">Remit ₱{{ number_format($cod['balance'], 2) }}</button>
                    </form>
                @elseif($cod['pending'])
                    <div class="ra-note" style="background:#fff;border-color:#f6ddb8"><i class="bi bi-hourglass-split"></i><span>Your Sorting Center still has to confirm the ₱{{ number_format($cod['pending']->amount, 2) }} you sent.</span></div>
                @else
                    <span style="font-size:13px;font-weight:700;color:#0f7a53"><i class="bi bi-check-circle-fill"></i> Nothing to remit</span>
                @endif
            </section>

            <div class="ra-tiles">
                <div class="ra-tile"><span>Today</span><strong data-pay-today>₱{{ number_format($payToday['total'], 2) }}</strong></div>
                <div class="ra-tile"><span>This week</span><strong data-pay-week>₱{{ number_format($payWeek['total'], 2) }}</strong></div>
            </div>

            <section class="ra-card" aria-label="Delivery earnings per day">
                <h2><i class="bi bi-bar-chart-fill"></i> Delivery earnings per day</h2>
                @if($totalDeliveries === 0)
                    <div class="ra-empty" style="border:0;padding:12px 0">
                        <i class="bi bi-bar-chart"></i>
                        <strong>No completed deliveries in this date range yet.</strong>
                        <span>Completed deliveries show up here by day.</span>
                    </div>
                @else
                    <div class="ra-chart"><canvas id="profitChart"></canvas></div>
                    <div class="ra-days">
                        @foreach($dailyProfit as $row)
                            <div>
                                <span>{{ \Carbon\Carbon::parse($row['day'])->format('D, M d') }} <small>· {{ $row['deliveries'] }} deliver{{ $row['deliveries'] === 1 ? 'y' : 'ies' }}</small></span>
                                <b class="ra-money">₱{{ number_format($row['profit'], 2) }}</b>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

        </div>

    </main>

    @include('partials.pwa-register')

    <script>
        // "Custom" opens the From / To picker.
        (function () {
            var open = document.querySelector('[data-open-range]');
            var range = document.getElementById('customRange');
            if (!open || !range) return;
            open.addEventListener('click', function () {
                range.open = true;
                range.querySelector('input').focus();
            });
        })();
    </script>

    @if($totalDeliveries > 0)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
        @include('partials.chart-defaults')
        <script>
            var profitLabels = @json($chartDays->map(fn($r) => \Carbon\Carbon::parse($r['day'])->format('M d')));
            var profitValues = @json($chartDays->map(fn($r) => (float) $r['profit']));

            // One bar per day: ₱ earned from deliveries.
            bbCharts(function () {
                new Chart(document.getElementById('profitChart'), {
                    type: 'bar',
                    data: {
                        labels: profitLabels,
                        datasets: [{
                            label: 'Earned',
                            data: profitValues,
                            backgroundColor: '#c9b9f2',
                            hoverBackgroundColor: '#6d4ad1',
                            borderRadius: 6,
                            maxBarThickness: 24,
                        }]
                    },
                    options: {
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: function (c) { return 'Earned: ' + bbPeso(c.parsed.y); } } }
                        },
                        scales: {
                            y: { beginAtZero: true, suggestedMax: 200, ticks: { callback: bbPesoAxis, maxTicksLimit: 5 } },
                            x: { grid: { display: false }, ticks: { maxTicksLimit: 7 } }
                        }
                    }
                });
            });
        </script>
    @endif

</body>
</html>
