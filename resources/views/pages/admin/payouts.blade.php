<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Payouts & Refunds — BoomBuy Admin'])
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
            <h1>Payouts &amp; Refunds</h1>
            <p>Money BoomBuy still has to send: sellers' payout requests and refunds of paid orders that were cancelled. Send it by GCash, Maya or bank transfer, then enter the reference here so the seller or buyer can check it.</p>
        </div>

        @if(session('success'))
            <div class="success-box">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="error-box">{{ session('error') }}</div>
        @endif

        <h2 class="money-section" id="payouts">Seller payout requests <b>{{ $payouts->count() }}</b></h2>

        @forelse($payouts as $payout)
            @php $shop = $shops->get((int) $payout->seller_id); @endphp
            <article class="money-card" id="payout-{{ $payout->id }}" data-payout-request="{{ $payout->id }}">
                <div class="money-head">
                    <div>
                        <h3>Payout #{{ $payout->id }} · {{ $shop['name'] ?? $payout->seller_name ?? 'Seller' }}</h3>
                        <div class="money-meta">Requested {{ \Carbon\Carbon::parse($payout->created_at)->format('M j, Y g:i A') }}</div>
                    </div>
                    <div style="text-align:right;">
                        <div class="money-amount">₱{{ number_format((float) $payout->amount, 2) }}</div>
                        <span class="money-pill is-wait">Pending</span>
                    </div>
                </div>

                <dl class="money-grid">
                    <div><dt>Send to</dt><dd class="v">
                        @if($payout->method || $payout->account_number)
                            {{ $payout->method ?: 'Account' }} · {{ $payout->account_name }}<br>{{ $payout->account_number }}
                        @else
                            Not given — message the seller
                        @endif
                    </dd></div>
                </dl>

                <form method="POST" action="{{ route('admin.payouts.paid', $payout->id) }}" class="money-form" data-confirm="Mark ₱{{ number_format((float) $payout->amount, 2) }} as sent to the seller?" data-confirm-ok="Payout Sent">
                    @csrf
                    <label for="payout-ref-{{ $payout->id }}">
                        Send ₱{{ number_format((float) $payout->amount, 2) }} to the account above, then enter the reference
                        <input type="text" id="payout-ref-{{ $payout->id }}" name="reference" maxlength="60" required placeholder="e.g. GCash ref 1234 567 890">
                    </label>
                    <button type="submit" class="money-btn"><i class="bi bi-send-check"></i> Payout Sent</button>
                </form>
            </article>
        @empty
            <div class="money-card money-empty">No payout requests waiting.</div>
        @endforelse

        <h2 class="money-section" id="refunds">Refunds of cancelled paid orders <b>{{ $refunds->count() }}</b></h2>

        @forelse($refunds as $order)
            <article class="money-card" id="refund-{{ $order->id }}" data-order-refund="{{ $order->id }}">
                <div class="money-head">
                    <div>
                        <h3>Order <a href="{{ route('admin.order.details', $order->id) }}">#{{ $order->id }}</a> · {{ $order->buyer_name ?? 'Buyer' }}</h3>
                        <div class="money-meta">
                            Paid with {{ $order->payment_method }}{{ $order->payment_reference ? ' (ref ' . $order->payment_reference . ')' : '' }}
                            · Cancelled {{ $order->cancelled_at ? \Carbon\Carbon::parse($order->cancelled_at)->format('M j, Y') : '' }}
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div class="money-amount">₱{{ number_format((float) ($order->refund_amount ?: $order->total_amount), 2) }}</div>
                        <span class="money-pill">Refund Pending</span>
                    </div>
                </div>

                @if($order->cancellation_reason)
                    <div class="money-note">{{ $order->cancellation_reason }}</div>
                @endif

                <form method="POST" action="{{ route('admin.orders.refunded', $order->id) }}" class="money-form" data-confirm="Mark ₱{{ number_format((float) ($order->refund_amount ?: $order->total_amount), 2) }} as sent back to the buyer?" data-confirm-ok="Refund Sent">
                    @csrf
                    <label for="order-ref-{{ $order->id }}">
                        Send the refund back to the buyer's {{ $order->payment_method }}, then enter the reference
                        <input type="text" id="order-ref-{{ $order->id }}" name="reference" maxlength="100" required placeholder="e.g. GCash ref 1234 567 890">
                    </label>
                    <button type="submit" class="money-btn"><i class="bi bi-send-check"></i> Refund Sent</button>
                </form>
            </article>
        @empty
            <div class="money-card money-empty">No refunds waiting.</div>
        @endforelse

        @if($recent->isNotEmpty())
            <h2 class="money-section">Recently paid payouts</h2>
            @foreach($recent as $payout)
                @php $shop = $shops->get((int) $payout->seller_id); @endphp
                <div class="money-card">
                    <div class="money-head">
                        <div>
                            <h3>Payout #{{ $payout->id }} · {{ $shop['name'] ?? $payout->seller_name ?? 'Seller' }}</h3>
                            <div class="money-meta">Paid {{ \Carbon\Carbon::parse($payout->paid_at)->format('M j, Y g:i A') }} · ref {{ $payout->reference }}</div>
                        </div>
                        <div style="text-align:right;">
                            <div class="money-amount">₱{{ number_format((float) $payout->amount, 2) }}</div>
                            <span class="money-pill is-good">Paid</span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
    </main>
</div>

    @include('partials.pwa-register')
</body>
</html>
