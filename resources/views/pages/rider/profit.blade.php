<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Profit — BoomBuy Rider</title>

    @include('partials.pwa-head')

    <link rel="stylesheet" href="{{ asset('css/rider-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/rider-profit.css') }}">
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

</body>
</html>
