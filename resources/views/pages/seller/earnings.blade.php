<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Earnings — BoomBuy Seller'])

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-vouchers.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-promotions.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="earnings" :user="$user" />

    <main class="main-content">

        <div class="container">

            <x-seller-page-head title="Earnings" subtitle="What each order earns you after your vouchers and BoomBuy's commission, and payouts of your Available balance." />

            @if(session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            <div class="card">
                <div class="form-row">
                    <div class="form-group">
                        <label>Pending</label>
                        <strong class="balance" data-balance="pending">₱{{ number_format($balances['pending'], 2) }}</strong>
                        <small class="promo-note" style="margin:4px 0 0;">Orders not yet completed. Moves to Available when the buyer confirms (or 7 days after delivery).</small>
                    </div>
                    <div class="form-group">
                        <label>Available</label>
                        <strong class="balance" data-balance="available">₱{{ number_format($balances['available'], 2) }}</strong>
                        <small class="promo-note" style="margin:4px 0 0;">Completed orders, less payouts requested or paid.</small>
                    </div>
                    <div class="form-group">
                        <label>Paid out</label>
                        <strong class="balance" data-balance="paid">₱{{ number_format($balances['paid_out'], 2) }}</strong>
                    </div>
                </div>

                <h2 style="margin-top:12px;">Request a Payout</h2>
                <form method="POST" action="{{ route('seller.payouts.store') }}" data-submit-once>
                    @csrf
                    {!! \App\Support\SubmitOnce::field() !!}
                    <div class="form-row">
                        <div class="form-group">
                            <label for="amount">Amount (₱)</label>
                            <input type="number" id="amount" name="amount" step="0.01" min="{{ \App\Support\SellerBalance::PAYOUT_MIN }}" max="{{ $balances['available'] }}" value="{{ old('amount') }}" required>
                            @error('amount')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="method">Send to</label>
                            <select id="method" name="method">
                                <option>GCash</option>
                                <option>Maya</option>
                                <option>Bank transfer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="account_number">Account number</label>
                            <input type="text" id="account_number" name="account_number" maxlength="40" value="{{ old('account_number') }}">
                        </div>
                    </div>
                    <button type="submit" class="save-btn">Request Payout</button>
                </form>

                @foreach($payouts as $payout)
                    <div class="promo-row" data-payout="{{ $payout->id }}">
                        <div class="promo-main">
                            <strong>₱{{ number_format((float) $payout->amount, 2) }}</strong>
                            <span class="status-badge {{ $payout->status === 'Paid' ? 'status-active' : 'status-inactive' }}">{{ $payout->status }}</span>
                            <small>Requested {{ \Illuminate\Support\Carbon::parse($payout->created_at)->format('M d, Y g:i A') }}{{ $payout->method ? ' · ' . $payout->method : '' }}</small>
                        </div>
                        @if($payout->status === 'Pending')
                            <form method="POST" action="{{ route('seller.payouts.cancel', $payout->id) }}">
                                @csrf
                                <button type="submit" class="mini-btn delete">Cancel</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="card">
                <h2>Earnings per Order</h2>

                @forelse($rows as $row)
                    @php $order = $orders->get($row->id); @endphp
                    <div class="promo-row" data-earning-order="{{ $row->id }}">
                        <div class="promo-main">
                            <strong>Order #{{ $row->id }} · {{ \App\Support\OrderStatus::label($order->status ?? '') }}</strong>
                            <small>
                                Gross <b data-gross>₱{{ number_format($row->items, 2) }}</b>
                                @if($row->voucher > 0) · Your voucher <b data-voucher>−₱{{ number_format($row->voucher, 2) }}</b> @endif
                                @if($row->refunds > 0) · Refunded <b>−₱{{ number_format($row->refunds, 2) }}</b> @endif
                                · Commission {{ rtrim(rtrim(number_format($row->rate, 2), '0'), '.') }}% <b data-commission>−₱{{ number_format($row->commission, 2) }}</b>
                                · Net <b data-net>₱{{ number_format($row->earning, 2) }}</b>
                            </small>
                            <small>BoomBuy vouchers and the shipping fee are paid by BoomBuy and the buyer — not taken from you.</small>
                        </div>
                    </div>
                @empty
                    <p class="empty-message">No orders yet.</p>
                @endforelse
            </div>

        </div>

    </main>

</div>

<script>
    // One click asks once.
    document.querySelectorAll('[data-submit-once]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var button = form.querySelector('button[type=submit]');
            setTimeout(function () { button.disabled = true; }, 0);
        });
    });
</script>

    @include('partials.pwa-register')

</body>
</html>
