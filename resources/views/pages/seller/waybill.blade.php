<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Waybill — Order #{{ $order['id'] }} — BoomBuy</title>

    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ vasset('css/views/seller-waybill.css') }}">
</head>

<body>

    <div class="no-print">
        <button class="print-btn" onclick="window.print()"><i class="bi bi-printer-fill"></i> Print Waybill</button>
    </div>

    <div class="waybill">

        <div class="waybill-header">
            <div class="waybill-logo">Boom<span style="color:#1b1a1f;">Buy</span></div>
            <div class="waybill-order-id">Order #{{ $order['id'] }}</div>
        </div>

        <div class="waybill-section">
            <div class="waybill-label">Ship To</div>
            <div class="waybill-value">{{ $order['buyer_name'] }}</div>
            <div class="waybill-value">{{ $order['phone'] }}</div>
            <div class="waybill-value">{{ $order['address'] }}</div>
        </div>

        <div class="waybill-section">
            <div class="waybill-label">Payment Method</div>
            <div class="waybill-value">{{ $order['payment_method'] ?? 'N/A' }}</div>
        </div>

        <div class="waybill-items">

            @foreach($order['items'] as $item)

                <div class="waybill-item">
                    <span>{{ $item['name'] }} x{{ $item['quantity'] }}</span>
                    <span>₱{{ number_format($item['subtotal'], 2) }}</span>
                </div>

            @endforeach

        </div>

        <div class="waybill-total">
            <span>Total</span>
            <span>₱{{ number_format($order['seller_total'], 2) }}</span>
        </div>

        <div class="waybill-footer">
            Handle with care · Thank you for shopping with BoomBuy!
        </div>

    </div>

</body>
</html>
