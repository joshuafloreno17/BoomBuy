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
    Print this and stick it on the parcel. A rider picks it up and brings it to
    <strong>{{ $origin->name ?? 'your Sorting Center' }}</strong>, where the staff scan the QR code.
</p>

<article class="label" aria-label="Shipping label {{ $waybill }}">

    <header class="label-head">
        <div class="label-brand">
            <img src="{{ vasset('images/icon.svg') }}" alt="" width="26" height="26">
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

    @php
        // "BoomBuy Sorting Center – Cebu" → "Cebu": the short place name is what sorters read.
        $centerPlace = fn ($center) => $center ? trim(preg_replace('/^BoomBuy Sorting Center\s*[–-]\s*/u', '', $center->name)) : '—';
        $toCenter = $destination ?? $origin;
    @endphp

    <section class="label-route">
        <div>
            <span>From sorting center</span>
            <strong>{{ $centerPlace($origin) }}</strong>
        </div>
        <i class="bi bi-arrow-right" aria-hidden="true"></i>
        <div>
            <span>To sorting center</span>
            <strong>{{ $centerPlace($toCenter) }}</strong>
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

    {{-- What's inside, so the rider and the buyer can check the parcel. --}}
    <section class="label-contents">
        <span>Contents</span>
        <ul>
            @foreach($items->take(4) as $item)
                <li>
                    <b>{{ $item->quantity }}×</b>
                    {{ $item->product_name }}@if(!empty($item->variation_label)) <em>({{ $item->variation_label }})</em>@endif
                </li>
            @endforeach
            @if($items->count() > 4)
                <li class="label-more">+ {{ $items->count() - 4 }} more item(s)</li>
            @endif
        </ul>
    </section>

    <section class="label-sign">
        <div>
            <span>Received by (name &amp; signature)</span>
            <i class="label-line"></i>
        </div>
        <div>
            <span>Date</span>
            <i class="label-line"></i>
        </div>
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
