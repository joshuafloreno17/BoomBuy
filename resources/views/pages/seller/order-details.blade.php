<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seller Order Details — BoomBuy</title>

    @include('partials.pwa-head')

    <link rel="stylesheet" href="{{ asset('css/pages/seller-order-details.css') }}">

</head>

<body>

<div class="layout">

    <!-- =========================================
         SIDEBAR
    ========================================== -->

    <aside class="sidebar">

        <a href="/" class="logo">
            Boom<span>Buy</span>
        </a>

        <div class="sidebar-label">
            Seller Panel
        </div>

        <nav class="menu">

            <a href="{{ route('seller.dashboard') }}">
                📊
                <span>Dashboard</span>
            </a>

            <a href="{{ route('seller.products.create') }}">
                ➕
                <span>Add Product</span>
            </a>

            <a href="{{ route('seller.orders') }}" class="active">
                🛒
                <span>Orders</span>
            </a>

        </nav>

        <div class="sidebar-footer">

            <div class="sidebar-user">
                Seller: {{ $user['name'] ?? 'Seller' }}
            </div>

        </div>

    </aside>


    <!-- =========================================
         MAIN CONTENT
    ========================================== -->

    <main class="main-content">

        <div class="container">

            <!-- BACK -->

            <a href="{{ route('seller.orders') }}" class="back">
                ← Back to Seller Orders
            </a>

            <a
                href="{{ route('seller.order.waybill', $order['id']) }}"
                target="_blank"
                class="back"
                style="float:right;"
            >
                🖨 Print Waybill
            </a>


            <!-- ALERTS -->

            @if(session('success'))

                <div class="success">
                    {{ session('success') }}
                </div>

            @endif


            @if(session('error'))

                <div class="error">
                    {{ session('error') }}
                </div>

            @endif


            <!-- =========================================
                 ORDER INFORMATION
            ========================================== -->

            <div class="card">

                <h1>
                    Seller Order Details
                </h1>

                <div class="order-id">
                    Order #{{ $order['id'] ?? 'N/A' }}
                </div>

                <div class="status">
                    {{ $order['status'] ?? 'Pending' }}
                </div>


                <div class="info-grid">

                    <!-- BUYER -->

                    <div class="info-box">

                        <div class="label">
                            Buyer
                        </div>

                        <div class="value">
                            {{ $order['buyer_name'] ?? 'Buyer' }}
                        </div>

                    </div>


                    <!-- ORDER DATE -->

                    <div class="info-box">

                        <div class="label">
                            Order Date
                        </div>

                        <div class="value">
                            {{ $order['date'] ?? 'N/A' }}
                        </div>

                    </div>


                    <!-- PHONE -->

                    <div class="info-box">

                        <div class="label">
                            Phone
                        </div>

                        <div class="value">
                            {{ $order['phone'] ?? 'N/A' }}
                        </div>

                    </div>


                    <!-- PAYMENT -->

                    <div class="info-box">

                        <div class="label">
                            Payment Method
                        </div>

                        <div class="value">
                            {{ $order['payment'] ?? 'N/A' }}
                        </div>

                    </div>

                </div>

            </div>


            <!-- =========================================
                 PRODUCTS
            ========================================== -->

            <div class="card">

                <h2>
                    Your Products in This Order
                </h2>

                @forelse(($order['items'] ?? []) as $item)

                    <div class="item">

                        <div>

                            <div class="item-name">
                                {{ $item['name'] ?? 'Product' }}
                                @if(!empty($item['variation_label']))
                                    <span style="color:var(--muted);">({{ $item['variation_label'] }})</span>
                                @endif
                            </div>

                            <div class="item-info">

                                Quantity:
                                {{ $item['quantity'] ?? 0 }}

                                • ₱{{ number_format((float)($item['price'] ?? 0), 2) }}

                                each

                            </div>

                        </div>

                        <div class="price">

                            ₱{{ number_format((float)($item['subtotal'] ?? 0), 2) }}

                        </div>

                    </div>

                @empty

                    <div class="info-box">
                        <div class="value">
                            No products found in this order.
                        </div>
                    </div>

                @endforelse


                <!-- SELLER TOTAL -->

                <div class="summary total">

                    <span>
                        Your Sales
                    </span>

                    <span>
                        ₱{{ number_format((float)($order['seller_total'] ?? 0), 2) }}
                    </span>

                </div>

            </div>


            <!-- =========================================
                 DELIVERY INFORMATION
            ========================================== -->

            <div class="card">

                <h2>
                    Delivery Information
                </h2>

                <div class="info-box delivery-box">

                    <div class="label">
                        Delivery Address
                    </div>

                    <div class="value">
                        {{ $order['address'] ?? 'N/A' }}
                    </div>

                </div>

            </div>


            <!-- =========================================
                 UPDATE STATUS
            ========================================== -->

            <div class="card">

                <h2>
                    Update Order Status
                </h2>

                <form
                    method="POST"
                    action="{{ route('seller.order.status', $order['id']) }}"
                >

                    @csrf

                    <select name="status" required>

                        <option value="">
                            Select Status
                        </option>

                        <option value="Processing">
                            Processing
                        </option>

                        <option value="Ready for Pickup">
                            Ready for Pickup
                        </option>

                        <option value="Cancelled">
                            Cancelled
                        </option>

                    </select>

                    <button type="submit">
                        Update Order Status
                    </button>

                </form>

            </div>

        </div>


        <!-- FOOTER -->

        <footer>

            © 2026 <strong>BoomBuy</strong>
            <br>
            Seller Order Management

        </footer>

    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>