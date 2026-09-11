<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Orders — BoomBuy</title>

<!-- Leaflet -->
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
        background: #fbf6f5;
        color: #1f2937;
    }

    h1,
    h2,
    h3,
    .logo {
        font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
        letter-spacing: -0.01em;
    }

    button,
    .btn {
        border-radius: 12px;
        transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    button:hover,
    .btn:hover {
        transform: translateY(-1px);
    }

    /* =========================
       NAVBAR
    ========================= */

    .navbar {
        background: #ffffff;
        padding: 18px 7%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #ebe6e5;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .logo {
        font-size: 26px;
        font-weight: 800;
        color: #f34f1d;
        text-decoration: none;
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 25px;
    }

    .nav-links a {
        text-decoration: none;
        color: #523d36;
        font-weight: 600;
    }

    .nav-links a:hover {
        color: #f34f1d;
    }

    .user {
        background: #fff3ef;
        color: #f34f1d;
        padding: 9px 15px;
        border-radius: 20px;
        font-weight: 600;
    }

    /* =========================
       SIDEBAR
    ========================= */

    .layout {
        display: flex;
    }

    .sidebar {
        width: 230px;
        background: #ffffff;
        border-right: 1px solid #ffe9e2;
        padding: 25px 18px;
        display: flex;
        flex-direction: column;
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        z-index: 1000;
        overflow-y: auto;
    }

    .sidebar-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #b99c93;
        margin-top: 22px;
        margin-bottom: 4px;
        font-weight: 700;
    }

    .sidebar .menu {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-top: 8px;
    }

    .sidebar .menu a {
        display: block;
        padding: 12px;
        border-radius: 10px;
        color: #8d6c62;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: 0.2s;
    }

    .sidebar .menu a:hover,
    .sidebar .menu a.active {
        background: #fff4f1;
        color: #e8420f;
    }

    .sidebar-footer {
        margin-top: auto;
        padding-top: 16px;
        border-top: 1px solid #ffe9e2;
    }

    .sidebar-footer form {
        margin: 0;
    }

    .sidebar-footer button {
        border: none;
        background: transparent;
        padding: 12px;
        border-radius: 10px;
        color: #8d6c62;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-align: left;
    }

    .sidebar-footer button:hover {
        background: #fff4f1;
        color: #e8420f;
    }

    .main-content {
        margin-left: 230px;
        width: calc(100% - 230px);
        min-width: 0;
    }

    /* =========================
       CONTAINER
    ========================= */

    .container {
        width: 86%;
        max-width: 1200px;
        margin: 40px auto;
    }

    .page-header {
        margin-bottom: 25px;
    }

    .page-header h1 {
        font-size: 32px;
        margin-bottom: 8px;
    }

    .page-header p {
        color: #816f6a;
    }

    /* =========================
       ALERT
    ========================= */

    .alert {
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /* =========================
       ORDERS
    ========================= */

    .orders {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .order-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #ebe6e5;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        padding-bottom: 18px;
        border-bottom: 1px solid #ebe6e5;
    }

    .order-id {
        font-size: 18px;
        font-weight: 700;
    }

    .date {
        color: #816f6a;
        font-size: 14px;
        margin-top: 5px;
    }

    .status {
        padding: 8px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        background: #fffaed;
        color: #c2910c;
    }

    /* =========================
       ITEMS
    ========================= */

    .items {
        margin-top: 20px;
    }

    .item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        padding: 14px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .item:last-child {
        border-bottom: none;
    }

    .item-name {
        font-weight: 700;
    }

    .item-info {
        color: #816f6a;
        font-size: 14px;
        margin-top: 5px;
    }

    .item-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .item-price {
        font-weight: 700;
        white-space: nowrap;
    }

    /* =========================
       RATING
    ========================= */

    .rate-btn {
        border: none;
        cursor: pointer;
        padding: 9px 13px;
        border-radius: 10px;
        background: #f5b70b;
        color: #ffffff;
        font-family: inherit;
        font-size: 11px;
        font-weight: 800;
    }

    .rate-btn:hover {
        background: #d99f00;
    }

    .rated-badge {
        display: inline-flex;
        align-items: center;
        padding: 9px 13px;
        border-radius: 10px;
        background: #fff8df;
        color: #9a7200;
        border: 1px solid #f4dfa0;
        font-size: 11px;
        font-weight: 800;
    }

    .rating-panel {
        display: none;
        margin-top: 20px;
        padding: 22px;
        background: #fffdf7;
        border: 1px solid #f3e6b7;
        border-radius: 14px;
    }

    .rating-panel.active {
        display: block;
    }

    .rating-title {
        font-family: 'Baloo 2', sans-serif;
        font-size: 21px;
        font-weight: 800;
        color: #523d36;
        margin-bottom: 5px;
    }

    .rating-subtitle {
        color: #816f6a;
        font-size: 13px;
        margin-bottom: 18px;
    }

    .rating-product {
        background: #ffffff;
        border: 1px solid #eee4d4;
        border-radius: 12px;
        padding: 16px;
    }

    .rating-product-name {
        font-weight: 800;
        margin-bottom: 12px;
    }

    .stars {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 4px;
        margin-bottom: 12px;
    }

    .stars input {
        display: none;
    }

    .stars label {
        font-size: 30px;
        color: #ddd;
        cursor: pointer;
        transition: 0.15s ease;
    }

    .stars label:hover,
    .stars label:hover ~ label,
    .stars input:checked ~ label {
        color: #f5b70b;
        transform: scale(1.05);
    }

    .review-input {
        width: 100%;
        min-height: 80px;
        resize: vertical;
        border: 1px solid #eadfd8;
        border-radius: 10px;
        padding: 11px 13px;
        font-family: inherit;
        font-size: 13px;
        outline: none;
    }

    .review-input:focus {
        border-color: #f5b70b;
        box-shadow: 0 0 0 3px rgba(245, 183, 11, 0.12);
    }

    .submit-rating {
        margin-top: 12px;
        border: none;
        cursor: pointer;
        padding: 10px 15px;
        border-radius: 10px;
        background: #f34f1d;
        color: #ffffff;
        font-family: inherit;
        font-size: 12px;
        font-weight: 800;
    }

    .submit-rating:hover {
        background: #df4516;
    }

    .review-note {
        margin-top: 10px;
        color: #816f6a;
        font-size: 11px;
    }

    /* =========================
       ORDER INFO
    ========================= */

    .order-info {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-top: 20px;
    }

    .info-box {
        background: #fbf9f9;
        padding: 15px;
        border-radius: 10px;
    }

    .info-box span {
        display: block;
        color: #816f6a;
        font-size: 13px;
        margin-bottom: 5px;
    }

    .info-box strong {
        font-size: 14px;
    }

    /* =========================
       ORDER FOOTER
    ========================= */

    .order-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        padding-top: 18px;
        border-top: 1px solid #ebe6e5;
        gap: 15px;
    }

    .total {
        font-size: 21px;
        font-weight: 800;
        color: #f34f1d;
    }

    .footer-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .footer-actions form {
        margin: 0;
    }

    /* =========================
       TRACK BUTTON
    ========================= */

    .track-btn {
        border: none;
        background: #f34f1d;
        color: white;
        padding: 11px 18px;
        border-radius: 10px;
        font-weight: 700;
        cursor: pointer;
        font-size: 14px;
    }

    .track-btn:hover {
        background: #df4516;
    }

    /* =========================
       RECEIVED BUTTON
    ========================= */

    .received-btn {
        border: none;
        cursor: pointer;
        padding: 11px 16px;
        border-radius: 12px;
        background: #24965a;
        color: #ffffff;
        font-family: inherit;
        font-size: 12px;
        font-weight: 800;
    }

    .received-btn:hover {
        background: #1d7f4c;
    }

    .received-badge {
        display: inline-flex;
        align-items: center;
        padding: 10px 14px;
        border-radius: 12px;
        background: #eafaf0;
        color: #24733e;
        border: 1px solid #ccefd9;
        font-size: 12px;
        font-weight: 800;
    }

    /* =========================
       TRACKING PANEL
    ========================= */

    .tracking-panel {
        display: none;
        margin-top: 25px;
        border-top: 1px solid #ebe6e5;
        padding-top: 25px;
    }

    .tracking-panel.active {
        display: block;
    }

    .tracking-title {
        font-size: 22px;
        font-weight: 800;
        margin-bottom: 18px;
    }

    /* =========================
       MAP
    ========================= */

    .map-container {
        width: 100%;
        height: 400px;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #f0dfda;
        margin-bottom: 25px;
    }

    .map {
        width: 100%;
        height: 100%;
    }

    /* =========================
       RIDER INFO
    ========================= */

    .tracking-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
        margin-bottom: 25px;
    }

    .tracking-box {
        background: #fcf9f8;
        border: 1px solid #f1e5e1;
        border-radius: 12px;
        padding: 18px;
    }

    .tracking-box-title {
        color: #8d6c62;
        font-size: 13px;
        margin-bottom: 7px;
    }

    .tracking-box-value {
        font-size: 16px;
        font-weight: 700;
    }

    .rider-icon {
        font-size: 25px;
        margin-right: 8px;
    }

    /* =========================
       TIMELINE
    ========================= */

    .timeline {
        background: white;
        border: 1px solid #ebe6e5;
        border-radius: 14px;
        padding: 22px;
    }

    .timeline-title {
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 22px;
    }

    .timeline-item {
        position: relative;
        display: flex;
        gap: 15px;
        padding-bottom: 22px;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-line {
        position: absolute;
        left: 11px;
        top: 25px;
        width: 2px;
        height: calc(100% - 10px);
        background: #ffe3da;
    }

    .timeline-item:last-child .timeline-line {
        display: none;
    }

    .timeline-dot {
        width: 24px;
        height: 24px;
        min-width: 24px;
        border-radius: 50%;
        background: #f34f1d;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        z-index: 2;
    }

    .timeline-dot.gray {
        background: #e2d0ca;
    }

    .timeline-content strong {
        display: block;
        margin-bottom: 4px;
    }

    .timeline-content span {
        color: #8d6c62;
        font-size: 13px;
    }

    /* =========================
       EMPTY
    ========================= */

    .empty {
        background: #ffffff;
        padding: 60px 30px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .empty-icon {
        font-size: 55px;
        margin-bottom: 15px;
    }

    .empty h2 {
        margin-bottom: 8px;
    }

    .empty p {
        color: #816f6a;
        margin-bottom: 20px;
    }

    .btn {
        display: inline-block;
        padding: 11px 18px;
        background: #f34f1d;
        color: white;
        text-decoration: none;
        border-radius: 10px;
        font-weight: 700;
    }

    .btn:hover {
        background: #df4516;
    }

    /* =========================
       MOBILE
    ========================= */

    @media (max-width: 900px) {

        .sidebar {
            width: 72px;
            padding: 20px 8px;
        }

        .sidebar .label-text,
        .sidebar-label {
            display: none;
        }

        .sidebar .menu a {
            text-align: center;
        }

        .sidebar-footer button {
            text-align: center;
        }

        .main-content {
            margin-left: 72px;
            width: calc(100% - 72px);
        }
    }

    @media (max-width: 768px) {

        .navbar {
            padding: 15px 5%;
        }

        .nav-links {
            gap: 12px;
            font-size: 13px;
        }

        .container {
            width: 92%;
        }

        .order-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .order-info {
            grid-template-columns: 1fr;
        }

        .item {
            align-items: flex-start;
            flex-direction: column;
            gap: 10px;
        }

        .item-right {
            width: 100%;
            justify-content: space-between;
        }

        .order-footer {
            flex-direction: column;
            align-items: flex-start;
        }

        .tracking-grid {
            grid-template-columns: 1fr;
        }

        .map-container {
            height: 320px;
        }
    }
</style>


</head>

<body>


<!-- =========================
     NAVBAR
========================= -->

<nav class="navbar">

    <a
        href="{{ route('buyer.dashboard') }}"
        class="logo"
    >
        BoomBuy
    </a>

    <div class="nav-links">

        <a href="{{ route('buyer.dashboard') }}">
            Home
        </a>

        <a href="{{ route('cart') }}">
            🛒 Cart
        </a>

        <a href="{{ route('buyer.orders') }}">
            📦 My Orders
        </a>

        <span class="user">
            {{ $user['name'] ?? 'Buyer' }}
        </span>

    </div>

</nav>

<div class="layout">

    <!-- =========================
         SIDEBAR
    ========================= -->

    <aside class="sidebar">

        <div class="sidebar-label">
            My Account
        </div>

        <nav class="menu">

            <a href="{{ route('buyer.dashboard') }}">
                🏠
                <span class="label-text">Overview</span>
            </a>

            <a
                href="{{ route('buyer.orders') }}"
                class="active"
            >
                📦
                <span class="label-text">My Orders</span>
            </a>

            <a href="{{ route('products') }}">
                🛍️
                <span class="label-text">Shop</span>
            </a>

            <a href="{{ route('cart') }}">
                🛒
                <span class="label-text">Cart</span>
            </a>

        </nav>

        <div class="sidebar-footer">

            <form
                action="{{ route('logout') }}"
                method="POST"
            >
                @csrf

                <button
                    type="submit"
                    style="width:100%;"
                >
                    🚪
                    <span class="label-text">Logout</span>
                </button>

            </form>

        </div>

    </aside>

    <!-- =========================
         MAIN
    ========================= -->

    <main class="main-content">

        <div class="container">

            <div class="page-header">

                <h1>
                    My Orders
                </h1>

                <p>
                    View and track all your BoomBuy orders.
                </p>

            </div>

            <!-- SUCCESS -->

            @if(session('success'))

                <div class="alert alert-success">
                    ✓ {{ session('success') }}
                </div>

            @endif

            <!-- ERROR -->

            @if(session('error'))

                <div class="alert alert-error">
                    ⚠ {{ session('error') }}
                </div>

            @endif

            @if(empty($orders))

                <!-- EMPTY -->

                <div class="empty">

                    <div class="empty-icon">
                        📦
                    </div>

                    <h2>
                        No Orders Yet
                    </h2>

                    <p>
                        You haven't placed any orders yet.
                    </p>

                    <a
                        href="{{ route('buyer.dashboard') }}"
                        class="btn"
                    >
                        Start Shopping
                    </a>

                </div>

            @else

                <div class="orders">

                    @foreach($orders as $order)

                        @php

                            $orderStatus = strtolower(
                                $order['status'] ?? 'pending'
                            );

                            $trackingId = 'tracking-' .
                                preg_replace(
                                    '/[^a-zA-Z0-9]/',
                                    '',
                                    $order['id'] ?? uniqid()
                                );

                            $canReview =
                                ($order['status'] ?? '') === 'Delivered'
                                && !empty($order['buyer_received_at']);

                        @endphp

                        <!-- =========================
                             ORDER CARD
                        ========================= -->

                        <div class="order-card">

                            <!-- HEADER -->

                            <div class="order-header">

                                <div>

                                    <div class="order-id">
                                        Order #{{ $order['id'] ?? 'N/A' }}
                                    </div>

                                    <div class="date">
                                        {{ $order['date'] ?? 'N/A' }}
                                    </div>

                                </div>

                                <div class="status">
                                    {{ $order['status'] ?? 'Pending' }}
                                </div>

                            </div>

                            <!-- ITEMS -->

                            <div class="items">

                                @foreach($order['items'] ?? [] as $item)

                                    @php
                                        $itemReview = $item['review'] ?? null;
                                        $productId = $item['product_id'] ?? null;
                                    @endphp

                                    <div class="item">

                                        <div>

                                            <div class="item-name">
                                                {{ $item['name'] ?? 'Product' }}
                                            </div>

                                            <div class="item-info">

                                                Quantity:
                                                {{ $item['quantity'] ?? 1 }}

                                                @if(!empty($item['seller_name']))

                                                    • Seller:
                                                    {{ $item['seller_name'] }}

                                                @endif

                                            </div>

                                        </div>

                                        <div class="item-right">

                                            <div class="item-price">

                                                ₱{{ number_format(
                                                    (float)($item['subtotal'] ?? 0),
                                                    2
                                                ) }}

                                            </div>

                                            @if($canReview && $productId)

                                                @if(!empty($itemReview))

                                                    <div class="rated-badge">

                                                        ⭐
                                                        {{ $itemReview['rating'] ?? $itemReview->rating ?? 0 }}/5

                                                    </div>

                                                @else

                                                    <button
                                                        type="button"
                                                        class="rate-btn"
                                                        onclick="toggleRating('{{ $order['id'] }}-{{ $productId }}')"
                                                    >
                                                        ⭐ Rate Product
                                                    </button>

                                                @endif

                                            @endif

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                            <!-- =========================
                                 RATING PANEL
                            ========================= -->

                            @if($canReview)

                                @foreach($order['items'] ?? [] as $item)

                                    @php

                                        $productId = $item['product_id'] ?? null;
                                        $itemReview = $item['review'] ?? null;

                                        $ratingPanelId =
                                            'rating-' .
                                            ($order['id'] ?? 0) .
                                            '-' .
                                            ($productId ?? uniqid());

                                    @endphp

                                    @if($productId && empty($itemReview))

                                        <div
                                            id="{{ $ratingPanelId }}"
                                            class="rating-panel"
                                        >

                                            <div class="rating-title">
                                                ⭐ Rate Your Product
                                            </div>

                                            <div class="rating-subtitle">
                                                Share your experience with this product.
                                            </div>

                                            <div class="rating-product">

                                                <div class="rating-product-name">
                                                    {{ $item['name'] ?? 'Product' }}
                                                </div>

                                                <form
                                                    method="POST"
                                                    action="{{ url('/buyer/orders/' . $order['id'] . '/review/' . $productId) }}"
                                                    onsubmit="return validateRating(this)"
                                                >

                                                    @csrf

                                                    <div class="stars">

                                                        <input
                                                            type="radio"
                                                            id="star5-{{ $ratingPanelId }}"
                                                            name="rating"
                                                            value="5"
                                                        >

                                                        <label
                                                            for="star5-{{ $ratingPanelId }}"
                                                            title="5 stars"
                                                        >
                                                            ★
                                                        </label>

                                                        <input
                                                            type="radio"
                                                            id="star4-{{ $ratingPanelId }}"
                                                            name="rating"
                                                            value="4"
                                                        >

                                                        <label
                                                            for="star4-{{ $ratingPanelId }}"
                                                            title="4 stars"
                                                        >
                                                            ★
                                                        </label>

                                                        <input
                                                            type="radio"
                                                            id="star3-{{ $ratingPanelId }}"
                                                            name="rating"
                                                            value="3"
                                                        >

                                                        <label
                                                            for="star3-{{ $ratingPanelId }}"
                                                            title="3 stars"
                                                        >
                                                            ★
                                                        </label>

                                                        <input
                                                            type="radio"
                                                            id="star2-{{ $ratingPanelId }}"
                                                            name="rating"
                                                            value="2"
                                                        >

                                                        <label
                                                            for="star2-{{ $ratingPanelId }}"
                                                            title="2 stars"
                                                        >
                                                            ★
                                                        </label>

                                                        <input
                                                            type="radio"
                                                            id="star1-{{ $ratingPanelId }}"
                                                            name="rating"
                                                            value="1"
                                                        >

                                                        <label
                                                            for="star1-{{ $ratingPanelId }}"
                                                            title="1 star"
                                                        >
                                                            ★
                                                        </label>

                                                    </div>

                                                    <textarea
                                                        name="review"
                                                        class="review-input"
                                                        placeholder="Tell us what you think about this product... (optional)"
                                                    ></textarea>

                                                    <button
                                                        type="submit"
                                                        class="submit-rating"
                                                    >
                                                        Submit Review ⭐
                                                    </button>

                                                    <div class="review-note">
                                                        Your rating will be visible as part of the product's reviews.
                                                    </div>

                                                </form>

                                            </div>

                                        </div>

                                    @endif

                                @endforeach

                            @endif

                            <!-- =========================
                                 ORDER INFO
                            ========================= -->

                            <div class="order-info">

                                <div class="info-box">

                                    <span>
                                        Payment Method
                                    </span>

                                    <strong>
                                        {{ $order['payment'] ?? 'N/A' }}
                                    </strong>

                                </div>

                                <div class="info-box">

                                    <span>
                                        Phone
                                    </span>

                                    <strong>
                                        {{ $order['phone'] ?? 'N/A' }}
                                    </strong>

                                </div>

                                <div class="info-box">

                                    <span>
                                        Delivery Address
                                    </span>

                                    <strong>
                                        {{ $order['address'] ?? 'N/A' }}
                                    </strong>

                                </div>

                            </div>

                            <!-- =========================
                                 ORDER FOOTER
                            ========================= -->

                            <div class="order-footer">

                                <div>

                                    <span>
                                        Order Total:
                                    </span>

                                    <div class="total">

                                        ₱{{ number_format(
                                            (float)($order['total'] ?? 0),
                                            2
                                        ) }}

                                    </div>

                                </div>

                                <div class="footer-actions">

                                    <!-- TRACK -->

                                    <button
                                        type="button"
                                        class="track-btn"
                                        onclick="toggleTracking('{{ $trackingId }}')"
                                    >
                                        📍 Track Order
                                    </button>

                                    <!-- ORDER RECEIVED -->

                                    @if(
                                        ($order['status'] ?? '') === 'Delivered'
                                        && empty($order['buyer_received_at'])
                                    )

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'buyer.order.received',
                                                $order['id']
                                            ) }}"
                                        >

                                            @csrf

                                            <button
                                                type="submit"
                                                class="received-btn"
                                                onclick="return confirm('Confirm that you received this order?')"
                                            >
                                                📦 Order Received
                                            </button>

                                        </form>

                                    @elseif(!empty($order['buyer_received_at']))

                                        <div class="received-badge">
                                            ✓ Order Received
                                        </div>

                                    @endif

                                </div>

                            </div>

                            <!-- =========================
                                 TRACKING PANEL
                            ========================= -->

                            <div
                                id="{{ $trackingId }}"
                                class="tracking-panel"
                            >

                                <div class="tracking-title">
                                    📍 Order Tracking
                                </div>

                                <!-- MAP -->

                                <div class="map-container">

                                    <div
                                        id="map-{{ $trackingId }}"
                                        class="map"
                                    ></div>

                                </div>

                                <!-- RIDER INFO -->

                                <div class="tracking-grid">

                                    <div class="tracking-box">

                                        <div class="tracking-box-title">
                                            🚴 Delivery Rider
                                        </div>

                                        <div class="tracking-box-value">

                                            @if(!empty($order['rider_name']))

                                                <span class="rider-icon">
                                                    🏍️
                                                </span>

                                                {{ $order['rider_name'] }}

                                            @else

                                                <span style="color:#8d6c62;">
                                                    Rider not assigned yet
                                                </span>

                                            @endif

                                        </div>

                                    </div>

                                    <div class="tracking-box">

                                        <div class="tracking-box-title">
                                            📦 Current Status
                                        </div>

                                        <div class="tracking-box-value">
                                            {{ $order['status'] ?? 'Pending' }}
                                        </div>

                                    </div>

                                    <div class="tracking-box">

                                        <div class="tracking-box-title">
                                            🏠 Delivery Address
                                        </div>

                                        <div class="tracking-box-value">
                                            {{ $order['address'] ?? 'No address' }}
                                        </div>

                                    </div>

                                    <div class="tracking-box">

                                        <div class="tracking-box-title">
                                            📞 Contact Number
                                        </div>

                                        <div class="tracking-box-value">
                                            {{ $order['phone'] ?? 'N/A' }}
                                        </div>

                                    </div>

                                </div>

                                <!-- =========================
                                     DELIVERY TIMELINE
                                ========================= -->

                                <div class="timeline">

                                    <div class="timeline-title">
                                        Delivery Progress
                                    </div>

                                    <!-- ORDER PLACED -->

                                    <div class="timeline-item">

                                        <div class="timeline-line"></div>

                                        <div class="timeline-dot">
                                            ✓
                                        </div>

                                        <div class="timeline-content">

                                            <strong>
                                                Order Placed
                                            </strong>

                                            <span>
                                                Your order has been successfully placed.
                                            </span>

                                        </div>

                                    </div>

                                    <!-- PREPARING -->

                                    <div class="timeline-item">

                                        <div class="timeline-line"></div>

                                        <div
                                            class="timeline-dot
                                            {{
                                                in_array(
                                                    $orderStatus,
                                                    [
                                                        'processing',
                                                        'preparing',
                                                        'ready for pickup',
                                                        'picked up',
                                                        'out for delivery',
                                                        'delivered'
                                                    ]
                                                )
                                                ? ''
                                                : 'gray'
                                            }}"
                                        >
                                            ✓
                                        </div>

                                        <div class="timeline-content">

                                            <strong>
                                                Preparing Order
                                            </strong>

                                            <span>
                                                Seller is preparing your order.
                                            </span>

                                        </div>

                                    </div>

                                    <!-- READY -->

                                    <div class="timeline-item">

                                        <div class="timeline-line"></div>

                                        <div
                                            class="timeline-dot
                                            {{
                                                in_array(
                                                    $orderStatus,
                                                    [
                                                        'ready for pickup',
                                                        'picked up',
                                                        'out for delivery',
                                                        'delivered'
                                                    ]
                                                )
                                                ? ''
                                                : 'gray'
                                            }}"
                                        >
                                            ✓
                                        </div>

                                        <div class="timeline-content">

                                            <strong>
                                                Ready for Pickup
                                            </strong>

                                            <span>
                                                Your order is ready for the rider.
                                            </span>

                                        </div>

                                    </div>

                                    <!-- PICKED UP -->

                                    <div class="timeline-item">

                                        <div class="timeline-line"></div>

                                        <div
                                            class="timeline-dot
                                            {{
                                                in_array(
                                                    $orderStatus,
                                                    [
                                                        'picked up',
                                                        'out for delivery',
                                                        'delivered'
                                                    ]
                                                )
                                                ? ''
                                                : 'gray'
                                            }}"
                                        >
                                            🚚
                                        </div>

                                        <div class="timeline-content">

                                            <strong>
                                                Picked Up
                                            </strong>

                                            <span>
                                                Rider has picked up your order.
                                            </span>

                                        </div>

                                    </div>

                                    <!-- OUT FOR DELIVERY -->

                                    <div class="timeline-item">

                                        <div class="timeline-line"></div>

                                        <div
                                            class="timeline-dot
                                            {{
                                                in_array(
                                                    $orderStatus,
                                                    [
                                                        'out for delivery',
                                                        'delivered'
                                                    ]
                                                )
                                                ? ''
                                                : 'gray'
                                            }}"
                                        >
                                            🏍️
                                        </div>

                                        <div class="timeline-content">

                                            <strong>
                                                Out for Delivery
                                            </strong>

                                            <span>
                                                Your order is on the way.
                                            </span>

                                        </div>

                                    </div>

                                    <!-- DELIVERED -->

                                    <div class="timeline-item">

                                        <div
                                            class="timeline-dot
                                            {{
                                                $orderStatus === 'delivered'
                                                ? ''
                                                : 'gray'
                                            }}"
                                        >
                                            ✓
                                        </div>

                                        <div class="timeline-content">

                                            <strong>
                                                Delivered
                                            </strong>

                                            <span>
                                                Your order has been delivered.
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            @endif

        </div>

    </main>

</div>

<!-- Leaflet JS -->

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>

<script>

    /* =========================
       MAP STORAGE
    ========================= */

    const maps = {};

    /* =========================
       TOGGLE TRACKING
    ========================= */

    function toggleTracking(id) {

        const panel = document.getElementById(id);

        if (!panel) {
            return;
        }

        const isOpen = panel.classList.contains('active');

        if (isOpen) {

            panel.classList.remove('active');

            return;
        }

        panel.classList.add('active');

        const mapElement = panel.querySelector('.map');

        if (!mapElement) {
            return;
        }

        if (maps[id]) {

            setTimeout(function () {

                maps[id].invalidateSize();

            }, 100);

            return;
        }

        /* =========================
           TEMPORARY DEMO LOCATION
        ========================= */

        const buyerLocation = [
            14.5995,
            120.9842
        ];

        const riderLocation = [
            14.6095,
            120.9942
        ];

        /* =========================
           CREATE MAP
        ========================= */

        const map = L.map(mapElement).setView(
            buyerLocation,
            13
        );

        maps[id] = map;

        /* =========================
           MAP TILES
        ========================= */

        L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }
        ).addTo(map);

        /* =========================
           BUYER MARKER
        ========================= */

        const buyerIcon = L.divIcon({

            className: '',

            html:
                '<div style="' +
                'font-size:32px;' +
                'text-align:center;' +
                '">🏠</div>',

            iconSize: [
                35,
                35
            ],

            iconAnchor: [
                17,
                30
            ]

        });

        L.marker(
            buyerLocation,
            {
                icon: buyerIcon
            }
        )
        .addTo(map)
        .bindPopup(
            '<strong>🏠 Delivery Address</strong><br>' +
            'Your delivery location'
        );

        /* =========================
           RIDER MARKER
        ========================= */

        const riderIcon = L.divIcon({

            className: '',

            html:
                '<div style="' +
                'font-size:32px;' +
                'text-align:center;' +
                '">🏍️</div>',

            iconSize: [
                35,
                35
            ],

            iconAnchor: [
                17,
                30
            ]

        });

        L.marker(
            riderLocation,
            {
                icon: riderIcon
            }
        )
        .addTo(map)
        .bindPopup(
            '<strong>🏍️ Rider</strong><br>' +
            'Delivery rider location'
        );

        /* =========================
           DELIVERY ROUTE
        ========================= */

        L.polyline(
            [
                riderLocation,
                buyerLocation
            ],
            {
                weight: 5,
                opacity: 0.7
            }
        ).addTo(map);

        /* =========================
           FIT MAP
        ========================= */

        const bounds = L.latLngBounds([
            riderLocation,
            buyerLocation
        ]);

        map.fitBounds(
            bounds,
            {
                padding: [
                    40,
                    40
                ]
            }
        );

        /* =========================
           FIX MAP SIZE
        ========================= */

        setTimeout(function () {

            map.invalidateSize();

        }, 300);

    }

    /* =========================
       RATING PANEL
    ========================= */

    function toggleRating(key) {

    const target = document.getElementById('rating-' + key);

    if (!target) {
        console.log('Rating panel not found:', 'rating-' + key);
        return;
    }

    document.querySelectorAll('.rating-panel').forEach(function(panel) {
        if (panel !== target) {
            panel.classList.remove('active');
        }
    });

    target.classList.toggle('active');

    if (target.classList.contains('active')) {
        setTimeout(function() {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }, 100);
    }
}
    /* =========================
       VALIDATE RATING
    ========================= */

    function validateRating(form) {

        const selected = form.querySelector(
            'input[name="rating"]:checked'
        );

        if (!selected) {

            alert('Please select a star rating first.');

            return false;
        }

        return true;
    }

</script>


</body>

</html>
