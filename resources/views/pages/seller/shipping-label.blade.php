<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'Shipping Label ' . $label['waybill'] . ' — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/shipping-label.css') }}">
</head>

@php
    $place = [\App\Support\ShippingLabel::class, 'place'];
    $printedAt = $label['printed_at'] ? \Illuminate\Support\Carbon::parse($label['printed_at']) : null;
    $items = collect($label['items']);
@endphp

<body @class(["is-embed" => request()->boolean("embed")])>

@unless(request()->boolean("embed"))
<div class="label-toolbar">
    <a href="{{ route('seller.order.details', $order->id) }}" class="label-back"><i class="bi bi-arrow-left"></i> Back to order</a>
    <button type="button" class="label-print" onclick="window.print()"><i class="bi bi-printer-fill"></i> {{ $printedAt ? 'Reprint label' : 'Print label' }}</button>
</div>

<p class="label-help">
    @if($printedAt)
        First printed {{ $printedAt->format('M j, Y · g:i A') }}. This is the same label — reprints are marked <strong>REPRINT</strong>.
    @else
        Print this and stick it on the parcel. A rider picks it up and brings it to
        <strong>{{ $label['origin'] ?? 'your Sorting Center' }}</strong>, where the staff scan the QR code.
    @endif
</p>
@endunless

<article class="label" aria-label="Shipping label {{ $label['waybill'] }}">

    <header class="label-head">
        <div class="label-brand">
            <img src="{{ vasset('images/icon.svg') }}" alt="" width="26" height="26">
            BoomBuy
        </div>
        <div class="label-service">{{ $label['is_cod'] ? 'COD' : 'PAID' }}</div>
    </header>

    {{-- Every print after the first says so, with the first print's date. --}}
    @if($printedAt)
        <div class="label-reprint" data-label-reprint>
            REPRINT · first printed {{ $printedAt->format('M j, Y g:i A') }}
        </div>
    @endif

    <section class="label-code">
        <div id="labelQr" class="label-qr" data-url="{{ $label['scan_url'] }}" aria-label="QR code for {{ $label['waybill'] }}"></div>
        <div class="label-code-text">
            <span>Waybill No.</span>
            <strong>{{ \App\Support\Waybill::pretty($label['waybill']) }}</strong>
            <span>Order #{{ $label['order_id'] }} · {{ \Illuminate\Support\Carbon::parse($label['order_date'])->format('M j, Y') }}</span>
        </div>
    </section>

    <section class="label-route">
        <div>
            <span>From sorting center</span>
            <strong>{{ $place($label['origin']) }}</strong>
        </div>
        <i class="bi bi-arrow-right" aria-hidden="true"></i>
        <div>
            <span>To sorting center</span>
            <strong>{{ $place($label['destination']) }}</strong>
        </div>
    </section>

    @if (!empty($label['hubs']))
        <p class="label-hubs" style="margin:4px 0 8px;font-size:12px;"><i class="bi bi-signpost-split" aria-hidden="true"></i> Via hubs: {{ implode(' → ', array_map($place, $label['hubs'])) }}</p>
    @endif

    <section class="label-party is-to">
        <span>Deliver to</span>
        <strong>{{ $label['to_name'] }}</strong>
        <p>{{ $label['to_address'] }}</p>
        <p>{{ $label['to_phone'] }}</p>
    </section>

    <section class="label-party">
        <span>From</span>
        <strong>{{ $label['shop_name'] }}</strong>
        <p>{{ $label['from_address'] }}</p>
        <p>{{ $label['from_phone'] }}</p>
    </section>

    {{-- What's inside, so the rider and the buyer can check the parcel. --}}
    <section class="label-contents">
        <span>Contents</span>
        <ul>
            @foreach($items->take(4) as $item)
                <li>
                    <b>{{ $item['quantity'] }}×</b>
                    {{ $item['name'] }}@if(!empty($item['variation'])) <em>({{ $item['variation'] }})</em>@endif
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
            <strong>{{ $label['item_count'] }}</strong>
        </div>
        <div data-label-weight>
            <span>Weight</span>
            <strong>{{ $label['weight_kg'] > 0 ? rtrim(rtrim(number_format($label['weight_kg'], 2), '0'), '.') . ' kg' : '—' }}</strong>
        </div>
        <div>
            <span>{{ $label['is_cod'] ? 'Collect from buyer' : 'Amount to collect' }}</span>
            <strong>₱{{ number_format((float) $label['collect'], 2) }}</strong>
        </div>
    </footer>

</article>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    (function () {
        var box = document.getElementById('labelQr');
        if (box) {
            if (window.QRCode) {
                new QRCode(box, { text: box.dataset.url, width: 132, height: 132, correctLevel: QRCode.CorrectLevel.M });
            } else {
                // No QR library (offline): the waybill no. can still be typed in.
                box.textContent = 'Scan unavailable — type the waybill no.';
                box.classList.add('is-fallback');
            }
        }

        // Tell the server when this label is printed (also from the side panel,
        // which prints this page in its iframe): the first print saves the label.
        // Opened with ?print=1 the server already recorded it.
        var printedUrl = @json(route('seller.order.label.printed', $order->id));
        var token = @json(csrf_token());
        var reported = @json(request()->boolean('print'));

        window.addEventListener('beforeprint', function () {
            if (reported) return;
            reported = true;
            try {
                fetch(printedUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    keepalive: true
                });
            } catch (e) { /* the print itself still goes ahead */ }
        });

        // Opened from the app with ?print=1: hand it to the phone's print screen
        // after the page has loaded (the app hooks window.print on page load).
        @if(request()->boolean('print'))
            window.addEventListener('load', function () {
                setTimeout(function () { window.print(); }, 700);
            });
        @endif
    })();
</script>

</body>

</html>
