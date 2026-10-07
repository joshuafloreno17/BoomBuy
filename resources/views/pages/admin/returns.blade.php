<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Returns & Refunds — BoomBuy Admin'])
    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/admin-complaints.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/money.css') }}">
</head>
<body>
<div class="layout">
    <x-layout.admin-sidebar active="returns" />
    <main class="main">
    <div class="container">
        <div class="header">
            <h1>Returns &amp; Refunds</h1>
            <p>Decide the requests sellers rejected or never answered, and send buyers their refunds. BoomBuy holds the buyer's money until the return window closes, so refunds come from BoomBuy — not from the seller's payout.</p>
        </div>

        @if(session('success'))
            <div class="success-box">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="error-box">{{ session('error') }}</div>
        @endif

        <nav class="money-tabs" aria-label="Return requests">
            @foreach($tabs as $key => $definition)
                <a href="{{ route('admin.returns', ['tab' => $key]) }}" @class(['active' => $tab === $key])>
                    {{ $definition['label'] }} <b>{{ $tabCounts[$key] }}</b>
                </a>
            @endforeach
        </nav>

        @forelse($requests as $request)
            @php
                $shop = $shops->get((int) $request->seller_id);
                $label = \App\Services\ReturnRefundService::LABELS[$request->status] ?? ucfirst($request->status);
                $tone = match (true) {
                    $request->status === 'completed' => 'is-good',
                    in_array($request->status, ['rejected', 'cancelled'], true) => 'is-bad',
                    in_array($request->status, ['disputed', 'refund_pending', 'refund_processing'], true) => '',
                    default => 'is-wait',
                };
            @endphp

            <article class="money-card" id="return-{{ $request->id }}">
                <div class="money-head">
                    <div>
                        <h3>{{ $request->request_type }} #{{ $request->id }} · {{ $request->product_name ?? 'Item' }}{{ $request->quantity ? ' × ' . $request->quantity : '' }}</h3>
                        <div class="money-meta">
                            Order <a href="{{ route('admin.order.details', $request->order_id) }}">#{{ $request->order_id }}</a>
                            · Buyer {{ $request->buyer_name ?? 'Unknown' }}
                            · Shop {{ $shop['name'] ?? 'Unknown' }}
                            · Requested {{ \Carbon\Carbon::parse($request->created_at)->format('M j, Y') }}
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div class="money-amount">₱{{ number_format((float) $request->refund_amount, 2) }}</div>
                        <span class="money-pill {{ $tone }}">{{ $label }}</span>
                    </div>
                </div>

                <dl class="money-grid">
                    <div><dt>Reason</dt><dd class="v">{{ $request->reason }}</dd></div>
                    <div><dt>Refund to</dt><dd class="v">
                        @if($request->refund_method)
                            {{ $request->refund_method }} · {{ $request->refund_account_name }}<br>{{ $request->refund_account_number }}
                        @else
                            Not given (older request) — message the buyer
                        @endif
                    </dd></div>
                    <div><dt>Paid with</dt><dd class="v">{{ $request->payment_method }}</dd></div>
                    @if($request->refund_reference)
                        <div><dt>Refund reference</dt><dd class="v">{{ $request->refund_reference }}</dd></div>
                    @endif
                </dl>

                @if($request->message)
                    <div class="money-note">Buyer: {{ $request->message }}</div>
                @endif
                @if($request->seller_note)
                    <div class="money-note">Seller: {{ $request->seller_note }}</div>
                @endif
                @if($request->admin_note)
                    <div class="money-note">{{ $request->admin_note }}</div>
                @endif
                @if($request->evidence)
                    <a href="{{ route('return-refund.evidence', $request->id) }}" target="_blank" class="evidence-link"><i class="bi bi-paperclip"></i> View the buyer's photo</a>
                @endif

                @if($request->status === 'disputed')
                    <form method="POST" action="{{ route('admin.returns.decide', $request->id) }}" class="money-form">
                        @csrf
                        <label for="note-{{ $request->id }}">
                            Your decision and reason (the buyer and the seller see it)
                            <textarea id="note-{{ $request->id }}" name="note" maxlength="500" required placeholder="e.g. The photo shows the item arrived broken."></textarea>
                        </label>
                        <button type="submit" name="decision" value="approve" class="money-btn"><i class="bi bi-check-lg"></i> Approve for the buyer</button>
                        <button type="submit" name="decision" value="reject" class="money-btn is-quiet"><i class="bi bi-x-lg"></i> Close it (seller was right)</button>
                    </form>
                @elseif(in_array($request->status, ['refund_pending', 'refund_processing'], true))
                    <form method="POST" action="{{ route('admin.returns.refunded', $request->id) }}" class="money-form" data-confirm="Mark ₱{{ number_format((float) $request->refund_amount, 2) }} as sent to the buyer?" data-confirm-ok="Refund Sent">
                        @csrf
                        <label for="ref-{{ $request->id }}">
                            Send ₱{{ number_format((float) $request->refund_amount, 2) }} to the account above, then enter the reference
                            <input type="text" id="ref-{{ $request->id }}" name="reference" maxlength="100" required placeholder="e.g. GCash ref 1234 567 890">
                        </label>
                        <button type="submit" class="money-btn"><i class="bi bi-send-check"></i> Refund Sent</button>
                    </form>
                @endif
            </article>
        @empty
            <div class="money-card money-empty">Nothing here right now.</div>
        @endforelse

        @include('partials.simple-pager', ['paginator' => $requests])
    </div>
    </main>
</div>
    @include('partials.pwa-register')
</body>
</html>
