
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — BoomBuy</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            overflow-x: hidden;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: #fff7f4;
            color: #172033;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            width: 245px;
            background: #ffffff;
            border-right: 1px solid #f7e5e0;
            padding: 25px 18px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
            overflow-y: auto;
        }

        .logo {
            padding: 0 12px;
            margin-bottom: 35px;
            font-size: 23px;
            font-weight: 700;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .admin-label {
            padding: 0 12px;
            color: #b99c93;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .menu a {
            display: block;
            padding: 12px;
            border-radius: 9px;
            color: #8d6c62;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
        }

        .menu a:hover {
            background: #fff4f1;
            color: #e8420f;
        }

        .menu a.active {
            background: #ffefea;
            color: #e8420f;
        }

        .logout {
            margin-top: 35px;
        }

        .logout a {
            display: block;
            padding: 12px;
            border-radius: 9px;
            color: #ef4444;
            font-size: 13px;
            font-weight: 600;
        }

        .logout a:hover {
            background: #fff1f2;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 245px;
            width: calc(100% - 245px);
            min-width: 0;
            padding: 35px 5%;
        }

        /* =========================
           TOPBAR
        ========================= */

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .topbar small {
            color: #db5a33;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 10px;
            font-weight: 700;
        }

        .topbar h1 {
            font-size: 32px;
            margin-top: 7px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            border: 1px solid #f7e5e0;
            padding: 9px 13px;
            border-radius: 10px;
            flex-shrink: 0;
        }

        .profile-icon {
            width: 35px;
            height: 35px;
            background: #ffefea;
            color: #e8420f;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-name {
            font-size: 12px;
            font-weight: 700;
        }

        .profile-role {
            color: #b99c93;
            font-size: 10px;
            margin-top: 2px;
        }

        /* =========================
           SUMMARY CARDS
        ========================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 15px;
            padding: 22px;
            transition: 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(39, 84, 150, 0.08);
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-title {
            color: #977970;
            font-size: 12px;
        }

        .stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            background: #fff1ed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .stat-value {
            font-size: 27px;
            font-weight: 700;
        }

        .stat-sub {
            color: #b99c93;
            font-size: 10px;
            margin-top: 7px;
        }

        /* =========================
           PANELS
        ========================= */

        .panel {
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 15px;
            padding: 23px;
            margin-bottom: 20px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .panel-header h2 {
            font-size: 17px;
        }

        .panel-description {
            color: #977970;
            font-size: 11px;
            margin-top: 5px;
        }

        /* =========================
           TABLE
        ========================= */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 13px;
            background: #fffaf8;
            color: #8d6c62;
            font-size: 10px;
            letter-spacing: .5px;
        }

        td {
            padding: 14px 13px;
            border-bottom: 1px solid #f7efed;
            font-size: 12px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .amount {
            font-weight: 700;
            color: #e8420f;
        }

        .status {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 5px;
            font-size: 9px;
            font-weight: 700;
        }

        .completed {
            background: #ecfdf5;
            color: #16a34a;
        }

        .processing {
            background: #fff3ef;
            color: #f34f1d;
        }

        .pending {
            background: #fffaed;
            color: #eaaf0c;
        }

        .cancelled {
            background: #fff1f2;
            color: #dc2626;
        }

        .empty-row {
            text-align: center;
            padding: 35px 15px !important;
            color: #b99c93;
        }

        /* =========================
           REPORT GRID
        ========================= */

        .report-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 20px;
        }

        /* =========================
           SALES OVERVIEW
        ========================= */

        .sales-chart {
            height: 260px;
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            gap: 14px;
            padding: 20px 10px 0;
            border-bottom: 1px solid #f7e5e0;
        }

        .bar-item {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
        }

        .bar {
            width: 45px;
            background: #e8420f;
            border-radius: 7px 7px 0 0;
            min-height: 4px;
            transition: .2s;
        }

        .bar:hover {
            background: #c4360b;
        }

        .bar-label {
            font-size: 10px;
            color: #977970;
        }

        .bar-value {
            font-size: 9px;
            color: #8d6c62;
        }

        .chart-empty {
            height: 100%;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #b99c93;
            font-size: 12px;
        }

        /* =========================
           CATEGORY REPORT
        ========================= */

        .category {
            padding: 14px 0;
            border-bottom: 1px solid #f7efed;
        }

        .category:last-child {
            border-bottom: none;
        }

        .category-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 7px;
        }

        .category-name {
            font-size: 12px;
            font-weight: 700;
        }

        .category-value {
            color: #e8420f;
            font-size: 11px;
            font-weight: 700;
        }

        .progress {
            width: 100%;
            height: 7px;
            background: #f9f0ed;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: #e8420f;
            border-radius: 20px;
        }

        .empty-message {
            color: #b99c93;
            font-size: 12px;
            padding: 20px 0;
            text-align: center;
        }

        /* =========================
           PRODUCT PERFORMANCE
        ========================= */

        .product-name {
            font-weight: 700;
        }

        .seller-label {
            color: #977970;
            font-size: 10px;
            margin-top: 3px;
        }

        .performance-stars {
            letter-spacing: 1px;
        }

        /* =========================
           DESIGN OVERRIDES
        ========================= */

        h1,
        h2,
        h3,
        .logo,
        .stat-title,
        .stat-value {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
        }

        ::selection {
            background: #ffd7c2;
            color: #7c1a00;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .report-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 750px) {
            .sidebar {
                width: 70px;
                padding: 20px 10px;
            }

            .logo {
                font-size: 0;
                text-align: center;
                padding: 0;
            }

            .logo::before {
                content: "B";
                font-size: 23px;
                color: #e8420f;
            }

            .admin-label,
            .menu a span,
            .logout a span {
                display: none;
            }

            .menu a,
            .logout a {
                text-align: center;
                font-size: 18px;
            }

            .main {
                margin-left: 70px;
                width: calc(100% - 70px);
                padding: 25px 4%;
            }

            .profile-name,
            .profile-role {
                display: none;
            }
        }

        @media (max-width: 550px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .panel {
                padding: 18px;
            }

            .bar {
                width: 30px;
            }
        }
    </style>
</head>

<body>

@php
    /*
    |--------------------------------------------------------------------------
    | REAL DATABASE REPORT DATA
    |--------------------------------------------------------------------------
    | Seller products come from the actual products table.
    | Orders and order items come from the actual database.
    */

    $sellerProducts = \App\Models\Product::query()
        ->whereNotNull('seller_id')
        ->orderByDesc('created_at')
        ->get();

    $allOrders = \Illuminate\Support\Facades\DB::table('orders')
        ->orderByDesc('created_at')
        ->get();

    $totalOrders = $allOrders->count();

    $completedOrders = $allOrders
        ->where('status', 'Delivered')
        ->count();

    $totalRevenue = $allOrders
        ->where('status', 'Delivered')
        ->sum(function ($order) {
            return (float) ($order->total_amount ?? 0);
        });

    $averageOrder = $completedOrders > 0
        ? $totalRevenue / $completedOrders
        : 0;

    /*
    |--------------------------------------------------------------------------
    | SELLER PRODUCT PERFORMANCE
    |--------------------------------------------------------------------------
    */

    $productPerformance = $sellerProducts->map(function ($product) {

        $unitsSold = \Illuminate\Support\Facades\DB::table('order_items')
            ->where('product_id', $product->id)
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'Delivered')
            ->sum('order_items.quantity');

        return [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category,
            'price' => (float) $product->price,
            'units_sold' => (int) $unitsSold,
            'seller_id' => $product->seller_id,
        ];
    });

    /*
    |--------------------------------------------------------------------------
    | CATEGORY PERFORMANCE
    |--------------------------------------------------------------------------
    */

    $categorySales = [];

    foreach ($productPerformance as $product) {
        $category = $product['category'] ?: 'Other';

        if (!isset($categorySales[$category])) {
            $categorySales[$category] = 0;
        }

        $categorySales[$category] += $product['units_sold'];
    }

    arsort($categorySales);

    $totalCategoryUnits = array_sum($categorySales);

    /*
    |--------------------------------------------------------------------------
    | MONTHLY SALES
    |--------------------------------------------------------------------------
    */

    $monthlySales = [];

    for ($month = 1; $month <= 7; $month++) {
        $monthlySales[$month] = 0;
    }

    foreach ($allOrders as $order) {
        if (($order->status ?? '') !== 'Delivered') {
            continue;
        }

        if (empty($order->created_at)) {
            continue;
        }

        $month = (int) date('n', strtotime($order->created_at));

        if ($month >= 1 && $month <= 7) {
            $monthlySales[$month] += (float) ($order->total_amount ?? 0);
        }
    }

    $maxMonthlySales = max($monthlySales ?: [0]);

    /*
    |--------------------------------------------------------------------------
    | RECENT ORDERS
    |--------------------------------------------------------------------------
    */

    $recentOrders = $allOrders->take(5);

    /*
    |--------------------------------------------------------------------------
    | HELPER FOR ORDER ITEM NAMES
    |--------------------------------------------------------------------------
    */

    $getOrderProductNames = function ($orderId) {
    return \Illuminate\Support\Facades\DB::table('order_items')
        ->join('products', 'products.id', '=', 'order_items.product_id')
        ->where('order_items.order_id', $orderId)
        ->pluck('products.name')
        ->toArray();
};
    /*
    |--------------------------------------------------------------------------
    | CATEGORY ICONS
    |--------------------------------------------------------------------------
    */

    $categoryIcons = [
        'Smartphone' => '📱',
        'Smartphones' => '📱',
        'Laptop' => '💻',
        'Laptops' => '💻',
        'Audio' => '🎧',
        'Wearable' => '⌚',
        'Wearables' => '⌚',
        'Accessories' => '🎮',
        'Gaming' => '🎮',
        'Women’s' => '👗',
        "Women's" => '👗',
        'Men’s' => '👕',
        "Men's" => '👕',
        'Kids & Baby' => '🧸',
        'Home' => '🏠',
        'Sports' => '⚽',
        'Beauty' => '💄',
        'Food' => '🍔',
        'Automotive' => '🚗',
        'Office & School' => '📚',
    ];
@endphp

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo">
            Boom<span>Buy</span>
        </div>

        <div class="admin-label">
            Administration
        </div>

        <nav class="menu">

            <a href="{{ route('admin.dashboard') }}">
                📊 <span>Dashboard</span>
            </a>

            <a href="{{ route('admin.accounts') }}">
                👥 <span>Accounts</span>
            </a>

            <a href="{{ route('admin.reports') }}" class="active">
                📈 <span>Reports</span>
            </a>

            <a href="{{ route('admin.settings') }}">
                ⚙️ <span>Settings</span>
            </a>

        </nav>

        <div class="logout">
            <a href="{{ route('admin.login') }}">
                🚪 <span>Logout</span>
            </a>
        </div>

    </aside>


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

            <div class="admin-profile">

                <div class="profile-icon">
                    👤
                </div>

                <div>
                    <div class="profile-name">
                        Administrator
                    </div>

                    <div class="profile-role">
                        Store Manager
                    </div>
                </div>

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
                        💰
                    </div>

                </div>

                <div class="stat-value">
                    ₱{{ number_format($totalRevenue, 2) }}
                </div>

                <div class="stat-sub">
                    Revenue from delivered orders
                </div>

            </div>


            <!-- TOTAL ORDERS -->
            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-title">
                        Total Orders
                    </div>

                    <div class="stat-icon">
                        🛒
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
                        ✅
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
                        📊
                    </div>

                </div>

                <div class="stat-value">
                    ₱{{ number_format($averageOrder, 2) }}
                </div>

                <div class="stat-sub">
                    Average delivered order value
                </div>

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
                            Actual monthly sales performance
                        </p>

                    </div>

                </div>


                @if($totalOrders > 0)

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
                                ? round(($units / $totalCategoryUnits) * 100)
                                : 0;

                            $icon = $categoryIcons[$category] ?? '📦';
                        @endphp

                        <div class="category">

                            <div class="category-top">

                                <span class="category-name">
                                    {{ $icon }} {{ $category }}
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
                                $productNames = $getOrderProductNames($order->id);

                                $status = $order->status ?? 'Pending';

                                $statusClass = match ($status) {
                                    'Delivered' => 'completed',
                                    'Processing',
                                    'Ready for Pickup',
                                    'Picked Up',
                                    'Out for Delivery',
                                    'On the Way' => 'processing',
                                    'Cancelled' => 'cancelled',
                                    default => 'pending',
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

                                    ₱{{ number_format((float) ($order->total_amount ?? 0), 2) }}

                                </td>

                                <td>

                                    <span class="status {{ $statusClass }}">
                                        {{ $status }}
                                    </span>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5" class="empty-row">
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
                                $icon = $categoryIcons[$product['category']] ?? '📦';

                                if ($product['units_sold'] >= 20) {
                                    $stars = '⭐⭐⭐⭐⭐';
                                } elseif ($product['units_sold'] >= 10) {
                                    $stars = '⭐⭐⭐⭐';
                                } elseif ($product['units_sold'] >= 5) {
                                    $stars = '⭐⭐⭐';
                                } elseif ($product['units_sold'] > 0) {
                                    $stars = '⭐⭐';
                                } else {
                                    $stars = '—';
                                }

                                $sellerName = null;

                                if (!empty($product['seller_id'])) {
                                    $seller = \Illuminate\Support\Facades\DB::table('users')
                                        ->where('id', $product['seller_id'])
                                        ->first();

                                    if ($seller) {
                                        $sellerName = $seller->name;
                                    }
                                }
                            @endphp

                            <tr>

                                <td>

                                    <div class="product-name">
                                        {{ $icon }} {{ $product['name'] }}
                                    </div>

                                    @if($sellerName)
                                        <div class="seller-label">
                                            Seller: {{ $sellerName }}
                                        </div>
                                    @endif

                                </td>

                                <td>
                                    {{ $product['category'] ?: 'Other' }}
                                </td>

                                <td>
                                    {{ number_format($product['units_sold']) }}
                                </td>

                                <td class="amount">
                                    ₱{{ number_format($product['price'], 2) }}
                                </td>

                                <td class="performance-stars">
                                    {{ $stars }}
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5" class="empty-row">
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

</body>
</html>
