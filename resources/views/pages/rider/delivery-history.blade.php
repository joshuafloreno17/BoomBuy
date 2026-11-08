<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Delivery History — BoomBuy Rider'])

    <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/partials/rider-a.css') }}">
</head>

<body>

    <x-layout.rider-sidebar active="history" :user="$user" />

    @php
        $fromDate = \Illuminate\Support\Carbon::parse($from);
        $toDate = \Illuminate\Support\Carbon::parse($to);
        $rangeLabel = $fromDate->year === $toDate->year
            ? $fromDate->format('M j') . ' – ' . $toDate->format('M j, Y')
            : $fromDate->format('M j, Y') . ' – ' . $toDate->format('M j, Y');

        // Each job gets one filter type: delivered, failed or pickup (R7).
        $typed = $jobs->map(fn ($job) => $job + [
            'type' => $job['kind'] === 'Pickup' ? 'pickup' : (str_starts_with($job['result'], 'Failed') ? 'failed' : 'delivered'),
        ]);
        $counts = $typed->countBy('type');
        $earned = (float) $typed->sum('pay');
        $byDay = $typed->groupBy(fn ($job) => \Illuminate\Support\Carbon::parse($job['at'])->toDateString());
    @endphp

    <main class="main-content">

        <div class="ra">

            <div class="ra-head">
                <h1>Delivery history</h1>
                <p>All of your completed jobs</p>
            </div>

            <details class="ra-range">
                <summary><i class="bi bi-calendar3"></i><span>{{ $rangeLabel }}</span><i class="bi bi-chevron-down"></i></summary>
                <form method="GET" action="{{ route('rider.deliveries.history') }}">
                    <label class="ra-field" for="from">From<input type="date" id="from" name="from" value="{{ $from }}"></label>
                    <label class="ra-field" for="to">To<input type="date" id="to" name="to" value="{{ $to }}"></label>
                    <button type="submit" class="ra-btn">Show these dates</button>
                </form>
            </details>

            <div class="ra-seg is-count" role="group" aria-label="Filter by result">
                <button type="button" class="is-on" aria-pressed="true" data-filter="all"><b>{{ $typed->count() }}</b><span>All</span></button>
                <button type="button" @class(['is-zero' => !($counts['delivered'] ?? 0)]) aria-pressed="false" data-filter="delivered"><b>{{ $counts['delivered'] ?? 0 }}</b><span>Delivered</span></button>
                <button type="button" @class(['is-zero' => !($counts['failed'] ?? 0)]) aria-pressed="false" data-filter="failed"><b>{{ $counts['failed'] ?? 0 }}</b><span>Failed</span></button>
                <button type="button" @class(['is-zero' => !($counts['pickup'] ?? 0)]) aria-pressed="false" data-filter="pickup"><b>{{ $counts['pickup'] ?? 0 }}</b><span>Pickups</span></button>
            </div>

            <div class="ra-card">
                <div class="ra-stat">
                    <span class="ra-stat-icon is-green"><i class="bi bi-graph-up-arrow"></i></span>
                    <div><small>Earned in this range</small><strong class="ra-money">₱{{ number_format($earned, 2) }}</strong></div>
                    <span>from {{ $typed->count() }} job{{ $typed->count() === 1 ? '' : 's' }}</span>
                </div>
            </div>

            @forelse($byDay as $day => $dayJobs)
                @php $d = \Illuminate\Support\Carbon::parse($day); @endphp
                <div class="ra-day" data-day>{{ $d->isToday() ? 'Today' : ($d->isYesterday() ? 'Yesterday' : $d->format('D')) }} · {{ $d->format('M d') }}</div>

                @foreach($dayJobs as $job)
                    @php
                        $order = $jobOrders[$job['order_id']] ?? null;
                        $itemCount = (int) ($jobItemCounts[$job['order_id']] ?? 0);
                        $at = \Illuminate\Support\Carbon::parse($job['at']);
                    @endphp
                    <article class="ra-job" data-job="{{ $job['tracking_number'] }}" data-type="{{ $job['type'] }}">
                        <div class="ra-job-top">
                            <div>
                                <strong>Order #{{ $job['order_id'] }}</strong>
                                <small>{{ $job['tracking_number'] }} · {{ $job['kind'] }} · {{ $at->format('g:i A') }}</small>
                            </div>
                            <x-status-pill :status="$job['type'] === 'pickup' ? 'Picked Up' : ($job['type'] === 'failed' ? 'Delivery Failed' : 'Delivered')" />
                        </div>

                        <div class="ra-job-info">
                            @if($job['type'] === 'pickup')
                                <div><i class="bi bi-box-arrow-in-down"></i>Picked up from the seller for the Sorting Center</div>
                            @else
                                @if($job['type'] === 'failed')
                                    <div><i class="bi bi-exclamation-circle"></i>{{ trim(\Illuminate\Support\Str::after($job['result'], 'Failed:')) ?: 'Delivery attempt failed' }}</div>
                                @endif
                                @if($order)
                                    <div><i class="bi bi-person"></i>{{ $order->shipping_name ?: 'Buyer' }}</div>
                                    <div><i class="bi bi-geo-alt"></i>{{ $order->shipping_address ?: 'No address' }}</div>
                                @endif
                            @endif
                        </div>

                        <div class="ra-job-foot">
                            <span>
                                {{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }}
                                @if($order && $job['type'] !== 'pickup')
                                    · {{ \App\Support\CodPolicy::isCod($order->payment_method ?? '') ? 'COD ₱' . number_format((float) $order->total_amount, 2) : 'Paid online' }}
                                @endif
                            </span>
                            <b @class(['is-zero' => $job['pay'] <= 0])>{{ $job['pay'] > 0 ? '+' : '' }}₱{{ number_format($job['pay'], 2) }}</b>
                        </div>
                    </article>
                @endforeach
            @empty
                <div class="ra-empty">
                    <i class="bi bi-clock-history"></i>
                    <strong>No jobs in these dates</strong>
                    <span>Finished pickups, deliveries and failed attempts show up here.</span>
                </div>
            @endforelse

            @if($typed->isNotEmpty())
                <div class="ra-empty" data-filter-empty hidden>
                    <i class="bi bi-funnel"></i>
                    <strong>Nothing here</strong>
                    <span>No jobs of this kind in these dates.</span>
                </div>
                <p class="ra-end" data-filter-end>That's all for these dates.</p>
            @endif

        </div>

    </main>

    @include('partials.pwa-register')

    <script>
        // Filter the job cards by result; a day with nothing left hides its label.
        (function () {
            var buttons = document.querySelectorAll('[data-filter]');
            var cards = document.querySelectorAll('[data-type]');
            var empty = document.querySelector('[data-filter-empty]');
            var end = document.querySelector('[data-filter-end]');

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var want = button.dataset.filter;
                    var shown = 0;

                    buttons.forEach(function (b) {
                        b.classList.toggle('is-on', b === button);
                        b.setAttribute('aria-pressed', b === button ? 'true' : 'false');
                    });

                    cards.forEach(function (card) {
                        var show = want === 'all' || card.dataset.type === want;
                        card.hidden = !show;
                        if (show) shown++;
                    });

                    document.querySelectorAll('[data-day]').forEach(function (label) {
                        var next = label.nextElementSibling;
                        var any = false;
                        while (next && next.hasAttribute('data-type')) {
                            if (!next.hidden) any = true;
                            next = next.nextElementSibling;
                        }
                        label.hidden = !any;
                    });

                    if (empty) empty.hidden = shown > 0;
                    if (end) end.hidden = shown === 0;
                });
            });
        })();
    </script>

</body>
</html>
