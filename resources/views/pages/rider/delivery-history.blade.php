<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Delivery History — BoomBuy Rider</title>

    @include('partials.pwa-head')

    <link rel="stylesheet" href="{{ asset('css/rider-sidebar.css') }}">
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

            @forelse($history as $order)

                <div class="history-card">

                    <div class="history-top">
                        <div>
                            <div class="history-id">Order #{{ $order['id'] }}</div>
                            <div class="history-date">
                                Delivered {{ \Carbon\Carbon::parse($order['updated_at'])->format('M d, Y • h:i A') }}
                            </div>
                        </div>
                        <span class="history-badge">Delivered</span>
                    </div>

                    <div class="history-info">
                        👤 {{ $order['buyer_name'] }} &nbsp;·&nbsp; 📍 {{ $order['address'] }}
                    </div>

                    <div class="history-items">
                        {{ count($order['items']) }} item(s) · Total ₱{{ number_format((float) ($order['total_amount'] ?? 0), 2) }}
                    </div>

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon">📜</div>
                    <h3>No Completed Deliveries Yet</h3>
                    <p>Your delivery history will appear here once you complete deliveries.</p>
                </div>

            @endforelse

        </div>

    </main>

    @include('partials.pwa-register')

</body>
</html>
