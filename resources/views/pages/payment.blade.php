<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Payment — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/payment.css') }}">
</head>
<body>

<main class="pay-page">

    <a href="{{ route('buyer.dashboard') }}" class="pay-logo"><img src="{{ vasset('images/logo-wordmark.svg') }}" alt="BoomBuy" width="131" height="46"></a>

    <section class="pay-card" aria-labelledby="payTitle">

        <span class="pay-sandbox"><i class="bi bi-shield-check"></i> Sandbox — test payment, no real money</span>

        <h1 id="payTitle">{{ $method }}</h1>

        @if(session('success'))
            <div class="pay-note is-ok">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="pay-note is-error" role="alert">{{ session('error') }}</div>
        @endif

        @if($payable->isEmpty())
            <p class="pay-text">
                @if($orders->contains(fn ($o) => $o->payment_status === 'Paid'))
                    This order is already paid.
                @else
                    This order can no longer be paid. It was cancelled because it was not paid within {{ \App\Support\Payments::PAY_WINDOW_MINUTES }} minutes.
                @endif
            </p>
            <a href="{{ route('buyer.orders') }}" class="pay-btn">Go to My Orders</a>
        @else
            @if($failed)
                <p class="pay-status is-failed"><i class="bi bi-x-circle-fill"></i> Payment failed</p>
            @endif

            <dl class="pay-summary">
                @foreach($payable as $order)
                    <div><dt>Order #{{ $order->id }}</dt><dd>₱{{ number_format((float) $order->total_amount, 2) }}</dd></div>
                @endforeach
                <div class="pay-total"><dt>Amount to pay</dt><dd data-amount="{{ number_format($amount, 2, '.', '') }}">₱{{ number_format($amount, 2) }}</dd></div>
            </dl>

            <p class="pay-text">Pay before <strong>{{ \Illuminate\Support\Carbon::parse($payBy)->format('M d, Y g:i A') }}</strong>, or the order is cancelled and your items go back on sale.</p>

            <form method="POST" action="{{ route('payment.pay', $group) }}" data-pay-once>
                @csrf
                <button type="submit" class="pay-btn">{{ $failed ? 'Pay Again' : 'Pay ₱' . number_format($amount, 2) }}</button>
            </form>

            <div class="pay-row">
                <form method="POST" action="{{ route('payment.fail', $group) }}">
                    @csrf
                    <button type="submit" class="pay-link">Simulate a declined payment</button>
                </form>
                <form method="POST" action="{{ route('payment.cancel', $group) }}">
                    @csrf
                    <button type="submit" class="pay-link">Cancel and go back</button>
                </form>
            </div>
        @endif

    </section>

</main>

<script>
    // One click pays once: the button locks after the first press.
    document.querySelectorAll('[data-pay-once]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var button = form.querySelector('button');
            if (button.disabled) return false;
            setTimeout(function () { button.disabled = true; button.textContent = 'Processing…'; }, 0);
        });
    });
</script>

</body>
</html>
