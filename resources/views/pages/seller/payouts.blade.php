<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Payouts — BoomBuy Seller'])

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-vouchers.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/money.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="payouts" :user="$user" />

    <main class="main-content">

        <div class="container">

            <x-seller-page-head title="Payouts" subtitle="Your earnings after BoomBuy's commission, and the payments BoomBuy has sent you." />

            @if(session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            <div class="money-tiles">
                <div class="money-tile is-key">
                    <span>Ready for payout</span>
                    <strong>₱{{ number_format($balance['available'], 2) }}</strong>
                    <small>BoomBuy sends this to your account below.</small>
                </div>
                <div class="money-tile">
                    <span>On hold</span>
                    <strong>₱{{ number_format($balance['on_hold'], 2) }}</strong>
                    <small>Buyers can still return these ({{ \App\Support\AutoReceive::RETURN_WINDOW_DAYS }} days after receiving).</small>
                </div>
                <div class="money-tile">
                    <span>Waiting for cash</span>
                    <strong>₱{{ number_format($balance['waiting_cash'], 2) }}</strong>
                    <small>COD not handed in at the Sorting Center yet.</small>
                </div>
                <div class="money-tile">
                    <span>Paid to you</span>
                    <strong>₱{{ number_format($balance['paid_out'], 2) }}</strong>
                    <small>{{ $payouts->count() }} {{ \Illuminate\Support\Str::plural('payment', $payouts->count()) }}</small>
                </div>
            </div>

            {{-- WHERE TO BE PAID --}}
            <section class="money-card" id="payout-account">
                <h2>Where to send your payouts</h2>
                <p class="sub">BoomBuy sends your money here. Double-check the number — payments can't be pulled back.</p>

                <form method="POST" action="{{ route('seller.payouts.account') }}" class="money-form">
                    @csrf
                    <label for="payout_method">
                        Method
                        <select id="payout_method" name="payout_method" required>
                            <option value="">Choose…</option>
                            @foreach($methods as $method)
                                <option value="{{ $method }}" @selected(old('payout_method', $account['method']) === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label for="payout_account_name">
                        Account name
                        <input type="text" id="payout_account_name" name="payout_account_name" maxlength="120" required value="{{ old('payout_account_name', $account['name']) }}" placeholder="As registered">
                    </label>
                    <label for="payout_account_number">
                        Mobile or account number
                        <input type="text" id="payout_account_number" name="payout_account_number" maxlength="60" required value="{{ old('payout_account_number', $account['number']) }}" placeholder="09XXXXXXXXX">
                    </label>
                    <button type="submit" class="money-btn">Save</button>
                </form>
            </section>

            {{-- HOW IT ADDS UP --}}
            <section class="money-card">
                <h2>How your balance adds up</h2>
                <p class="sub">Delivered orders only. Commission is the rate when each order was placed.</p>
                <dl class="money-grid">
                    <div><dt>Delivered sales</dt><dd class="v">₱{{ number_format($balance['sales'], 2) }}</dd></div>
                    <div><dt>Refunded to buyers</dt><dd class="v">− ₱{{ number_format($balance['refunds'], 2) }}</dd></div>
                    <div><dt>BoomBuy commission</dt><dd class="v">− ₱{{ number_format($balance['commission'], 2) }}</dd></div>
                    <div><dt>Already paid to you</dt><dd class="v">− ₱{{ number_format($balance['paid_out'], 2) }}</dd></div>
                </dl>
                <p class="sub" style="margin:0;">Your own vouchers are also taken off the order they were used on.</p>
            </section>

            {{-- ORDERS --}}
            <section class="money-card">
                <h2>Orders</h2>
                <p class="sub">Your latest delivered orders and when each one can be paid out.</p>

                @if($orders->isEmpty())
                    <div class="money-empty">No delivered orders yet.</div>
                @else
                    <div class="money-table-wrap">
                        <table class="money-table">
                            <thead>
                                <tr><th>Order</th><th>Status</th><th class="num">Items</th><th class="num">Voucher · refunds</th><th class="num">Commission</th><th class="num">You get</th></tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                    @php
                                        [$label, $tone] = match ($order->state) {
                                            'available' => ['Ready for payout', 'is-good'],
                                            'waiting_cash' => ['Waiting for cash', 'is-wait'],
                                            default => [$order->open_request ? 'Return/refund open' : 'On hold' . ($order->releases_on ? ' until ' . $order->releases_on->format('M j') : ''), ''],
                                        };
                                    @endphp
                                    <tr>
                                        <td><a href="{{ route('seller.order.details', $order->id) }}">#{{ $order->id }}</a><br><small style="color:#8a7f86;">{{ $order->delivered_at ? \Carbon\Carbon::parse($order->delivered_at)->format('M j, Y') : '' }}</small></td>
                                        <td><span class="money-pill {{ $tone }}">{{ $label }}</span></td>
                                        <td class="num">₱{{ number_format($order->items, 2) }}</td>
                                        <td class="num">− ₱{{ number_format($order->voucher + $order->refunds, 2) }}</td>
                                        <td class="num">− ₱{{ number_format($order->commission, 2) }}<br><small style="color:#8a7f86;">{{ rtrim(rtrim(number_format($order->rate, 2), '0'), '.') }}%</small></td>
                                        <td class="num"><strong>₱{{ number_format($order->earning, 2) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- PAYMENTS RECEIVED --}}
            <section class="money-card">
                <h2>Payments from BoomBuy</h2>
                @if($payouts->isEmpty())
                    <div class="money-empty">No payouts yet.</div>
                @else
                    <div class="money-table-wrap">
                        <table class="money-table">
                            <thead><tr><th>Date</th><th>Sent to</th><th>Reference</th><th class="num">Amount</th></tr></thead>
                            <tbody>
                                @foreach($payouts as $payout)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($payout->paid_at)->format('M j, Y') }}</td>
                                        <td>{{ $payout->method }} · {{ \App\Services\ReturnRefundService::masked($payout->account_number) }}</td>
                                        <td>{{ $payout->reference }}@if($payout->note)<br><small style="color:#8a7f86;">{{ $payout->note }}</small>@endif</td>
                                        <td class="num">₱{{ number_format((float) $payout->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

        </div>

    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>
