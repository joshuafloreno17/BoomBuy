<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Seller Payouts — BoomBuy Admin'])
    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/admin-complaints.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/money.css') }}">
</head>
<body>
<div class="layout">
    <x-layout.admin-sidebar active="payouts" />
    <main class="main">
    <div class="container">
        <div class="header">
            <h1>Seller Payouts</h1>
            <p>What BoomBuy owes each seller after its commission. An order counts once its cash is in and the buyer can no longer return it ({{ \App\Support\AutoReceive::RETURN_WINDOW_DAYS }} days after they received it). Send the money, then record it here with the reference.</p>
        </div>

        @if(session('success'))
            <div class="success-box">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="error-box">{{ session('error') }}</div>
        @endif

        <div class="money-tiles">
            <div class="money-tile is-key"><span>Ready to pay out</span><strong>₱{{ number_format($totalAvailable, 2) }}</strong><small>All sellers, after commission</small></div>
            <div class="money-tile"><span>Not yet payable</span><strong>₱{{ number_format($totalOnHold, 2) }}</strong><small>Cash not in yet, or still returnable</small></div>
            <div class="money-tile"><span>Sellers</span><strong>{{ $sellers->count() }}</strong><small>{{ $sellers->filter(fn ($s) => $s->balance['available'] > 0)->count() }} with money to send</small></div>
        </div>

        @forelse($sellers as $seller)
            <article class="money-card">
                <div class="money-head">
                    <div>
                        <h3>{{ $seller->shop }}</h3>
                        <div class="money-meta">{{ $seller->email }}</div>
                    </div>
                    <div style="text-align:right;">
                        <div class="money-amount">₱{{ number_format($seller->balance['available'], 2) }}</div>
                        <span class="money-pill {{ $seller->balance['available'] > 0 ? '' : 'is-wait' }}">{{ $seller->balance['available'] > 0 ? 'Ready to pay' : 'Nothing to pay' }}</span>
                    </div>
                </div>

                <dl class="money-grid">
                    <div><dt>Delivered sales</dt><dd class="v">₱{{ number_format($seller->balance['sales'], 2) }}</dd></div>
                    <div><dt>Commission</dt><dd class="v">₱{{ number_format($seller->balance['commission'], 2) }}</dd></div>
                    <div><dt>Refunded to buyers</dt><dd class="v">₱{{ number_format($seller->balance['refunds'], 2) }}</dd></div>
                    <div><dt>Waiting for cash · on hold</dt><dd class="v">₱{{ number_format($seller->balance['waiting_cash'], 2) }} · ₱{{ number_format($seller->balance['on_hold'], 2) }}</dd></div>
                    <div><dt>Paid so far</dt><dd class="v">₱{{ number_format($seller->balance['paid_out'], 2) }}</dd></div>
                    <div><dt>Pay to</dt><dd class="v">
                        @if($seller->account['method'])
                            {{ $seller->account['method'] }} · {{ $seller->account['name'] }}<br>{{ $seller->account['number'] }}
                        @else
                            Not set yet — the seller adds it under Payouts
                        @endif
                    </dd></div>
                </dl>

                @if($seller->balance['available'] > 0 && $seller->account['method'])
                    <form method="POST" action="{{ route('admin.payouts.store', $seller->id) }}" class="money-form" data-confirm="Record this payout to {{ $seller->shop }}?" data-confirm-ok="Record Payout">
                        @csrf
                        <label for="amount-{{ $seller->id }}">
                            Amount sent (₱)
                            <input type="number" id="amount-{{ $seller->id }}" name="amount" step="0.01" min="0.01" max="{{ $seller->balance['available'] }}" value="{{ number_format($seller->balance['available'], 2, '.', '') }}" required>
                        </label>
                        <label for="reference-{{ $seller->id }}">
                            Reference no.
                            <input type="text" id="reference-{{ $seller->id }}" name="reference" maxlength="100" required placeholder="e.g. GCash ref 1234 567 890">
                        </label>
                        <label for="note-{{ $seller->id }}">
                            Note (optional)
                            <input type="text" id="note-{{ $seller->id }}" name="note" maxlength="200" placeholder="e.g. Oct 1–7 sales">
                        </label>
                        <button type="submit" class="money-btn"><i class="bi bi-send-check"></i> Record Payout</button>
                    </form>
                @endif
            </article>
        @empty
            <div class="money-card money-empty">No approved sellers yet.</div>
        @endforelse

        <section class="money-card">
            <h2>Recent payouts</h2>
            <p class="sub">The last 30 payments recorded.</p>
            @if($history->isEmpty())
                <div class="money-empty">No payouts recorded yet.</div>
            @else
                <div class="money-table-wrap">
                    <table class="money-table">
                        <thead><tr><th>Date</th><th>Seller</th><th>Sent to</th><th>Reference</th><th class="num">Amount</th></tr></thead>
                        <tbody>
                            @foreach($history as $payout)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($payout->paid_at)->format('M j, Y') }}</td>
                                    <td>{{ $payout->seller_name }}</td>
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
