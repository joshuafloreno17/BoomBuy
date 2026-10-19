<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Delivery History — BoomBuy Rider'])

    <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/rider-delivery-history.css') }}">
</head>

<body>

    <x-layout.rider-sidebar active="history" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header">

                <div>
                    <h1>Delivery History</h1>
                    <p>All of your completed deliveries.</p>
                </div>

                <form method="GET" action="{{ route('rider.deliveries.history') }}" class="filter-form">

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

            {{-- EVERY JOB ONCE: pickups, deliveries and failed attempts (R7) --}}
            <section class="all-jobs" style="margin:18px 0;">
                <h2>All jobs</h2>
                @if(($jobs ?? collect())->isEmpty())
                    <p>No jobs in this date range.</p>
                @else
                    <table class="jobs-table" style="width:100%;border-collapse:collapse;">
                        <thead><tr><th align="left">Date</th><th align="left">Tracking number</th><th align="left">Job</th><th align="left">Result</th><th align="right">Pay</th></tr></thead>
                        <tbody>
                            @foreach($jobs as $job)
                                <tr data-job="{{ $job['tracking_number'] }}">
                                    <td>{{ \Illuminate\Support\Carbon::parse($job['at'])->format('M d, Y g:i A') }}</td>
                                    <td>{{ $job['tracking_number'] }}</td>
                                    <td>{{ $job['kind'] }}</td>
                                    <td>{{ $job['result'] }}</td>
                                    <td align="right">₱{{ number_format($job['pay'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>


            @forelse($history as $order)

                <div class="history-card">

                    <div class="history-top">
                        <div>
                            <div class="history-id">Order #{{ $order['id'] }}</div>
                            <div class="history-date">
                                Delivered {{ \Carbon\Carbon::parse($order['delivered_at'] ?? $order['updated_at'])->format('M d, Y • h:i A') }}
                            </div>
                        </div>
                        <span class="history-badge">Delivered</span>
                    </div>

                    <div class="history-info">
                        <i class="bi bi-person-fill"></i> {{ $order['buyer_name'] }} &nbsp;·&nbsp; <i class="bi bi-geo-alt-fill"></i> {{ $order['address'] }}
                    </div>

                    <div class="history-items">
                        {{ count($order['items']) }} item(s) · Total ₱{{ number_format((float) ($order['total_amount'] ?? 0), 2) }}
                    </div>

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-clock-history"></i></div>
                    <h3>No Completed Deliveries Yet</h3>
                    <p>Your delivery history will appear here once you complete deliveries.</p>
                </div>

            @endforelse

        </div>

    </main>

    @include('partials.pwa-register')

</body>
</html>
