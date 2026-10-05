<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'Shipping Label ' . $waybill . ' — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/shipping-label.css') }}">
</head>

<body>

<div class="label-toolbar">
    <a href="{{ route('seller.order.details', $order->id) }}" class="label-back"><i class="bi bi-arrow-left"></i> Back to order</a>
    <button type="button" class="label-print" onclick="window.print()"><i class="bi bi-printer-fill"></i> Print label</button>
</div>

<p class="label-help">
    Print this, stick it on the parcel, then bring it to
    <strong>{{ $origin->name ?? 'your Sorting Center' }}</strong>. The staff scan the QR code to receive it.
</p>

<article class="label" aria-label="Shipping label {{ $waybill }}">

    <header class="label-head">
        <div class="label-brand">
            <img src="{{ asset('images/icon.svg') }}" alt="" width="26" height="26">
            BoomBuy
        </div>
        <div class="label-service">{{ $isCod ? 'COD' : 'PAID' }}</div>
    </header>

    <section class="label-code">
        <div id="labelQr" class="label-qr" data-url="{{ $scanUrl }}" aria-label="QR code for {{ $waybill }}"></div>
        <div class="label-code-text">
            <span>Waybill No.</span>
            <strong>{{ $waybill }}</strong>
            <span>Order #{{ $order->id }} · {{ \Illuminate\Support\Carbon::parse($order->created_at)->format('M j, Y') }}</span>
        </div>
    </section>

    <section class="label-route">
        <div>
            <span>From center</span>
            <strong>{{ $origin->name ?? '—' }}</strong>
        </div>
        <i class="bi bi-arrow-right" aria-hidden="true"></i>
        <div>
            <span>To center</span>
            <strong>{{ $destination->name ?? ($origin->name ?? '—') }}</strong>
        </div>
    </section>

    <section class="label-party is-to">
        <span>Deliver to</span>
        <strong>{{ $order->shipping_name }}</strong>
        <p>{{ $order->shipping_address }}</p>
        <p>{{ $order->shipping_phone }}</p>
    </section>

    <section class="label-party">
        <span>From</span>
        <strong>{{ $shopName }}</strong>
        <p>{{ $seller->address }}</p>
        <p>{{ $seller->phone }}</p>
    </section>

    <footer class="label-foot">
        <div>
            <span>Items</span>
            <strong>{{ $items->sum('quantity') }}</strong>
        </div>
        <div>
            <span>{{ $isCod ? 'Collect from buyer' : 'Amount to collect' }}</span>
            <strong>{{ $isCod ? '₱' . number_format((float) $order->total_amount, 2) : '₱0.00' }}</strong>
        </div>
    </footer>

</article>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    (function () {
        var box = document.getElementById('labelQr');
        if (!box) return;

        if (window.QRCode) {
            new QRCode(box, { text: box.dataset.url, width: 132, height: 132, correctLevel: QRCode.CorrectLevel.M });
        } else {
            // No QR library (offline): the waybill no. can still be typed in.
            box.textContent = 'Scan unavailable — type the waybill no.';
            box.classList.add('is-fallback');
        }
    })();
</script>

</body>

</html>
