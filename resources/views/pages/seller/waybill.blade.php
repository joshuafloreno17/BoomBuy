<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Waybill — Order #{{ $order['id'] }} — BoomBuy</title>

    @include('partials.design-tokens')

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f3f3f3;
            color: #172033;
        }

        .no-print {
            text-align: center;
            padding: 20px;
        }

        .print-btn {
            border: none;
            background: #e8420f;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .print-btn:hover {
            background: #c43408;
        }

        .waybill {
            width: 4in;
            min-height: 6in;
            margin: 0 auto 30px;
            background: white;
            border: 2px dashed #172033;
            padding: 20px;
        }

        .waybill-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #172033;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .waybill-logo {
            font-family: 'Baloo 2', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #e8420f;
        }

        .waybill-order-id {
            font-size: 13px;
            font-weight: 800;
        }

        .waybill-section {
            margin-bottom: 12px;
        }

        .waybill-label {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #816f6a;
            margin-bottom: 2px;
        }

        .waybill-value {
            font-size: 13px;
            font-weight: 700;
        }

        .waybill-items {
            border-top: 1px dashed #172033;
            border-bottom: 1px dashed #172033;
            padding: 10px 0;
            margin: 12px 0;
        }

        .waybill-item {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .waybill-total {
            display: flex;
            justify-content: space-between;
            font-size: 15px;
            font-weight: 800;
            margin-top: 8px;
        }

        .waybill-footer {
            text-align: center;
            font-size: 9px;
            color: #816f6a;
            margin-top: 15px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                background: white;
            }

            .waybill {
                border: 2px solid #172033;
                margin: 0;
            }
        }
    </style>
</head>

<body>

    <div class="no-print">
        <button class="print-btn" onclick="window.print()"><i class="bi bi-printer-fill"></i> Print Waybill</button>
    </div>

    <div class="waybill">

        <div class="waybill-header">
            <div class="waybill-logo">Boom<span style="color:#172033;">Buy</span></div>
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
