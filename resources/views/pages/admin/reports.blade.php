<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'Reports — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/admin-reports.css') }}">
</head>

<body>


<div class="layout">

    <!-- SIDEBAR -->

    <x-layout.admin-sidebar active="reports" />


    <!-- MAIN -->

    <main class="main">

        <!-- TOPBAR -->

        <div class="topbar">

            <div>

                <small>
                    BoomBuy Administration
                </small>

                <h1>
                    Reports
                </h1>

            </div>

        </div>


        <!-- SUMMARY -->

        <section class="stats">

            <!-- TOTAL REVENUE -->

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-title">
                        Total Revenue
                    </div>

                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
                    </div>

                </div>

                <div class="stat-value">
                    ₱{{ number_format($totalRevenue, 2) }}
                </div>

                <div class="stat-sub">
                    Revenue from successful orders
                </div>

            </div>


            <!-- TOTAL ORDERS -->

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-title">
                        Total Orders
                    </div>

                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    </div>

                </div>

                <div class="stat-value">
                    {{ number_format($totalOrders) }}
                </div>

                <div class="stat-sub">
                    Orders recorded in the system
                </div>

            </div>


            <!-- COMPLETED -->

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-title">
                        Completed Orders
                    </div>

                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="16 9 11 14 8 11"/></svg>
                    </div>

                </div>

                <div class="stat-value">
                    {{ number_format($completedOrders) }}
                </div>

                <div class="stat-sub">
                    Successfully delivered
                </div>

            </div>


            <!-- AVERAGE -->

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-title">
                        Average Order
                    </div>

                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                    </div>

                </div>

                <div class="stat-value">
                    ₱{{ number_format($averageOrder, 2) }}
                </div>

                <div class="stat-sub">
                    Average successful order value
                </div>

            </div>

        </section>


        <!-- COMMISSION REPORT -->

        <section class="panel" style="margin-bottom: 25px;">

            <div class="panel-header">

                <div>

                    <h2>
                        Commission Report
                    </h2>

                    <p class="panel-description">
                        BoomBuy's platform commission ({{ number_format($commissionRate, 1) }}%) on delivered orders, after each seller's own vouchers — same figures sellers see on their Reports page
                    </p>

                </div>

            </div>

            <div class="stats" style="margin-bottom: 20px;">

                <div class="stat-card">
                    <div class="stat-title">Total Seller Sales</div>
                    <div class="stat-value">₱{{ number_format($totalSellerSales, 2) }}</div>
                </div>

                <div class="stat-card">
                    <div class="stat-title">Platform Commission</div>
                    <div class="stat-value">₱{{ number_format($totalCommission, 2) }}</div>
                </div>

                <div class="stat-card">
                    <div class="stat-title">Seller Earnings</div>
                    <div class="stat-value">₱{{ number_format($totalPayouts, 2) }}</div>
                </div>

            </div>

            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>SELLER</th>
                            <th>SALES</th>
                            <th>COMMISSION ({{ number_format($commissionRate, 1) }}%)</th>
                            <th>SELLER EARNS</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($sellerSales as $row)

                            <tr>
                                <td>{{ $row['seller_name'] }}</td>
                                <td>₱{{ number_format($row['sales'], 2) }}</td>
                                <td>₱{{ number_format($row['commission'], 2) }}</td>
                                <td>₱{{ number_format($row['payout'], 2) }}</td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="4" class="empty-message">No seller sales recorded yet.</td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>


        <!-- SALES + CATEGORY -->

        <div class="report-grid">

            <!-- SALES OVERVIEW -->

            <section class="panel">

                <div class="panel-header">

                    <div>

                        <h2>
                            Sales Overview
                        </h2>

                        <p class="panel-description">
                            Monthly sales for {{ $year }} (excluding cancelled and returned orders, without delivery fees)
                        </p>

                    </div>

                </div>


                @if($maxMonthlySales > 0)

                    <div class="sales-chart">

                        @php

                            $months = [

                                1 => 'Jan',
                                2 => 'Feb',
                                3 => 'Mar',
                                4 => 'Apr',
                                5 => 'May',
                                6 => 'Jun',
                                7 => 'Jul',
                                8 => 'Aug',
                                9 => 'Sep',
                                10 => 'Oct',
                                11 => 'Nov',
                                12 => 'Dec',

                            ];

                        @endphp


                        @foreach($months as $monthNumber => $monthName)

                            @php

                                $value = $monthlySales[$monthNumber] ?? 0;

                                $height = $maxMonthlySales > 0
                                    ? ($value / $maxMonthlySales) * 100
                                    : 0;

                            @endphp


                            <div class="bar-item">

                                <div class="bar-value">
                                    ₱{{ number_format($value, 0) }}
                                </div>

                                <div
                                    class="bar"
                                    style="height: {{ max($height, $value > 0 ? 4 : 1) }}%;"
                                ></div>

                                <div class="bar-label">
                                    {{ $monthName }}
                                </div>

                            </div>

                        @endforeach

                    </div>

                @else

                    <div class="sales-chart">

                        <div class="chart-empty">
                            No sales data available yet.
                        </div>

                    </div>

                @endif

            </section>


            <!-- CATEGORY SALES -->

            <section class="panel">

                <div class="panel-header">

                    <div>

                        <h2>
                            Sales by Category
                        </h2>

                        <p class="panel-description">
                            Based on actual seller product sales
                        </p>

                    </div>

                </div>


                @if(count($categorySales) > 0)

                    @foreach($categorySales as $category => $units)

                        @php

                            $percentage = $totalCategoryUnits > 0
                                ? round(
                                    ($units / $totalCategoryUnits) * 100
                                )
                                : 0;

                            $icon = \App\Support\Categories::icon($category);

                        @endphp


                        <div class="category">

                            <div class="category-top">

                                <span class="category-name">
                                    <i class="bi {{ $icon }}"></i> {{ $category }}
                                </span>

                                <span class="category-value">
                                    {{ $percentage }}%
                                </span>

                            </div>

                            <div class="progress">

                                <div
                                    class="progress-bar"
                                    style="width: {{ $percentage }}%;"
                                ></div>

                            </div>

                        </div>

                    @endforeach

                @else

                    <div class="empty-message">
                        No seller product sales available yet.
                    </div>

                @endif

            </section>

        </div>


        <!-- ORDER REPORT -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Order Report
                    </h2>

                    <p class="panel-description">
                        Recent order and sales activity from the database
                    </p>

                </div>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                ORDER ID
                            </th>

                            <th>
                                CUSTOMER
                            </th>

                            <th>
                                PRODUCT
                            </th>

                            <th>
                                AMOUNT
                            </th>

                            <th>
                                STATUS
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($recentOrders as $order)

                            @php

                                $productNames = $order->product_names;

                                $status =
                                    $order->status ?? 'Pending';

                                $statusClass = match ($status) {

                                    'Delivered', 'Completed' =>
                                        'completed',

                                    'Confirmed',
                                    'Preparing',
                                    'Ready for Pickup',
                                    'Pickup Assigned',
                                    'Picked Up',
                                    'Sorted',
                                    'Dropped Off',
                                    'At Sorting Center',
                                    'In Transit',
                                    'Assigned for Delivery',
                                    'Out for Delivery',
                                    'Ready to Collect' =>
                                        'processing',

                                    'Cancelled',
                                    'Delivery Failed',
                                    'Returning',
                                    'Return Ready',
                                    'Returned to Seller' =>
                                        'cancelled',

                                    default =>
                                        'pending',

                                };

                            @endphp


                            <tr>

                                <td>
                                    #BB-{{ $order->id }}
                                </td>

                                <td>
                                    {{ $order->shipping_name ?? 'Unknown Buyer' }}
                                </td>

                                <td>

                                    @if(count($productNames) > 0)

                                        {{ implode(', ', $productNames) }}

                                    @else

                                        No product information

                                    @endif

                                </td>

                                <td class="amount">

                                    ₱{{ number_format(
                                        (float) ($order->total_amount ?? 0),
                                        2
                                    ) }}

                                </td>

                                <td>

                                    <span class="status {{ $statusClass }}">
                                        {{ $status }}
                                    </span>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="empty-row"
                                >
                                    No orders have been recorded yet.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>


        <!-- PRODUCT PERFORMANCE -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Product Performance
                    </h2>

                    <p class="panel-description">
                        Actual products added by sellers
                    </p>

                </div>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                PRODUCT
                            </th>

                            <th>
                                CATEGORY
                            </th>

                            <th>
                                UNITS SOLD
                            </th>

                            <th>
                                PRICE
                            </th>

                            <th>
                                PERFORMANCE
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($productPerformance as $product)

                            @php

                                $icon = \App\Support\Categories::icon($product['category']);

                                $filledStar = '<i class="bi bi-star-fill"></i>';

                                if ($product['units_sold'] >= 20) {

                                    $stars = str_repeat($filledStar, 5);

                                } elseif ($product['units_sold'] >= 10) {

                                    $stars = str_repeat($filledStar, 4);

                                } elseif ($product['units_sold'] >= 5) {

                                    $stars = str_repeat($filledStar, 3);

                                } elseif ($product['units_sold'] > 0) {

                                    $stars = str_repeat($filledStar, 2);

                                } else {

                                    $stars = '—';

                                }


                                $sellerName = $product['seller_name'];

                            @endphp


                            <tr>

                                <td>

                                    <div class="product-name">
                                        <i class="bi {{ $icon }}"></i>
                                        {{ $product['name'] }}
                                    </div>

                                    @if($sellerName)

                                        <div class="seller-label">
                                            Seller:
                                            {{ $sellerName }}
                                        </div>

                                    @endif

                                </td>

                                <td>
                                    {{ $product['category'] ?: 'Other' }}
                                </td>

                                <td>
                                    {{ number_format(
                                        $product['units_sold']
                                    ) }}
                                </td>

                                <td class="amount">
                                    ₱{{ number_format(
                                        $product['price'],
                                        2
                                    ) }}
                                </td>

                                <td class="performance-stars">
                                    {!! $stars !!}
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="empty-row"
                                >
                                    No seller products have been added yet.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>