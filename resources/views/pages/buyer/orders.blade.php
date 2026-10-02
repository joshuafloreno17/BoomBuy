<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Orders — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

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
       MAIN CONTENT (unified navbar, no sidebar)
    ========================= */

    .main-content {
        width: 100%;
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


    /* =========================
       ORDER FILTERS
    ========================= */

    .order-filters {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 22px;
    }

    .order-search-input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #f0ddd5;
        border-radius: 10px;
        font-family: inherit;
        font-size: 13px;
        outline: none;
    }

    .order-search-input:focus {
        border-color: #e8420f;
    }

    /* Chips like the Shop and Notifications pages; one scrolling row on phones. */
    .order-tabs {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 4px;
        scrollbar-width: thin;
    }

    .order-tab {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 40px;
        padding: 0 15px;
        border: 1px solid #f0d9d1;
        background: #ffffff;
        color: #172033;
        border-radius: 999px;
        font-family: inherit;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease;
    }

    .order-tab small {
        font-size: 11.5px;
        font-weight: 700;
        opacity: 0.65;
    }

    .order-tab:hover {
        border-color: #e8b5a4;
        background: #fffaf8;
        transform: none;
    }

    .order-tab.active {
        background: #172033;
        border-color: #172033;
        color: #ffffff;
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

        /* Keeps the card clear of the sticky navbar when scrolled to. */
        scroll-margin-top: 96px;
        transition: box-shadow 0.4s ease, border-color 0.4s ease;
    }

    /* The order a notification pointed to. */
    .order-card.is-highlighted {
        border-color: #f3a58c;
        box-shadow: 0 0 0 4px rgba(232, 66, 15, 0.18), 0 5px 20px rgba(0, 0, 0, 0.05);
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
   RIDER PROFILE
========================= */

.rider-profile {
    background: linear-gradient(135deg, #fff7f3, #ffffff);
    border: 1px solid #f1dfd8;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 18px;
}

.rider-profile-photo {
    width: 72px;
    height: 72px;
    min-width: 72px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #ffffff;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.10);
}

.rider-profile-placeholder {
    width: 72px;
    height: 72px;
    min-width: 72px;
    border-radius: 50%;
    background: #fff0eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
    border: 3px solid #ffffff;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
}

.rider-profile-info {
    min-width: 0;
}

.rider-profile-label {
    font-size: 11px;
    color: #a17f74;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 3px;
}

.rider-profile-name {
    font-family: 'Baloo 2', sans-serif;
    font-size: 22px;
    font-weight: 800;
    color: #523d36;
    line-height: 1.1;
}

.rider-profile-email {
    color: #816f6a;
    font-size: 12px;
    margin-top: 4px;
    word-break: break-word;
}

.rider-profile-status {
    margin-left: auto;
    background: #eafaf0;
    color: #24733e;
    border: 1px solid #ccefd9;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
}

@media (max-width: 600px) {
    .rider-profile {
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .rider-profile-status {
        margin-left: 0;
        width: 100%;
        text-align: center;
    }
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

    /* Secondary action: one filled button per order is enough. */
    .track-btn.is-ghost {
        background: #ffffff;
        color: #c43408;
        border: 1px solid #f0cfc4;
    }

    .track-btn.is-ghost:hover {
        background: #fff4f0;
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

    .cancel-order-btn {
        border: 1px solid #f4c7c3;
        cursor: pointer;
        padding: 10px 15px;
        border-radius: 12px;
        background: #fff1f0;
        color: #c0362c;
        font-family: inherit;
        font-size: 12px;
        font-weight: 800;
    }

    .cancel-order-btn:hover {
        background: #ffe1de;
    }

    /* A status, not a button. */
    .received-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0 4px;
        color: #24733e;
        font-size: 12.5px;
        font-weight: 700;
    }

    .return-btn{border:1px solid #f3c6ba;background:#fff5f1;color:#d9471c;padding:11px 16px;border-radius:12px;font-family:inherit;font-size:12px;font-weight:800;cursor:pointer}
    .return-btn:hover{background:#ffe9e1}
    .return-modal{display:none;position:fixed;inset:0;z-index:9999;background:rgba(31,41,55,.55);padding:20px;align-items:center;justify-content:center}
    .return-modal.active{display:flex}
    .return-modal-card{width:100%;max-width:560px;max-height:90vh;overflow-y:auto;background:#fff;border-radius:18px;padding:24px;box-shadow:0 20px 60px rgba(0,0,0,.18)}
    .return-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;margin-bottom:20px}
    .return-modal-title{font-family:'Baloo 2',sans-serif;font-size:25px;font-weight:800;color:#523d36}
    .return-modal-subtitle{color:#816f6a;font-size:12px;margin-top:3px}
    .return-close{border:none;background:#f7f2f0;color:#6f5a52;width:34px;height:34px;border-radius:50%;cursor:pointer;font-size:18px;font-weight:800}
    .return-form-group{margin-bottom:16px}
    .return-form-group label{display:block;margin-bottom:7px;color:#523d36;font-size:12px;font-weight:800}
    .return-form-group select,.return-form-group textarea{width:100%;border:1px solid #eadfd8;border-radius:10px;padding:11px 13px;font-family:inherit;font-size:13px;outline:none;background:#fff}
    .return-form-group select:focus,.return-form-group textarea:focus{border-color:#f34f1d;box-shadow:0 0 0 3px rgba(243,79,29,.10)}
    .return-form-group textarea{min-height:90px;resize:vertical}
    .return-note{background:#fff8f4;border:1px solid #f3dfd7;color:#7d6259;padding:12px 14px;border-radius:10px;font-size:11px;line-height:1.5;margin-bottom:18px}
    .return-actions{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap}
    .return-cancel,.return-submit{border:none;padding:11px 16px;border-radius:10px;font-family:inherit;font-size:12px;font-weight:800;cursor:pointer}
    .return-cancel{background:#f3efed;color:#67544d}
    .return-submit{background:#f34f1d;color:#fff}
    .return-submit:hover{background:#df4516}
    @media(max-width:600px){.return-modal{padding:12px}.return-modal-card{padding:18px}}

    .cancel-reasons{display:grid;gap:8px}
    .cancel-reason{display:flex!important;align-items:center;gap:10px;margin:0!important;padding:11px 13px;border:1px solid #eadfd8;border-radius:10px;font-size:13px!important;font-weight:600!important;color:#523d36;cursor:pointer;transition:.15s}
    .cancel-reason:hover{border-color:#f3c6ba;background:#fffaf8}
    .cancel-reason input{accent-color:#c62828;margin:0}
    .cancel-reason:has(input:checked){border-color:#c62828;background:#fff1f0}
    .return-note.is-warning{background:#fff4e5;border-color:#f5d9a8;color:#8a5a00}
    .cancel-submit{background:#c62828}
    .cancel-submit:hover{background:#a51f1f}
    .cancel-note{display:flex;gap:8px;align-items:flex-start;margin-top:14px;padding:11px 13px;border-radius:10px;background:#f7f4f2;color:#6b5048;font-size:12px;line-height:1.5}
    .cancel-note i{margin-top:2px}

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

    .timeline-dot.danger {
        background: #c62828;
    }

    .timeline-content .timeline-reason {
        display: block;
        margin-top: 6px;
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

    @media (max-width: 768px) {

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
    }

    /* Tracker bar on in-progress orders */
    .order-track {
        margin: 4px 0 16px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .order-track-steps {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 5px;
    }

    .order-track-steps span {
        height: 6px;
        border-radius: 999px;
        background: #f3e6e1;
    }

    .order-track-steps span.is-on {
        background: #e8420f;
    }

    .order-track-labels {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 5px;
        font-size: 11px;
        font-weight: 600;
        color: #8d7c77;
    }

    .order-track-note {
        font-size: 12.5px;
        font-weight: 600;
        color: #5b4a44;
    }

    .order-track-note i {
        color: #c43408;
    }

    /* Photo + name on each item */
    .item-main {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .item-shop {
        color: #c43408;
        font-weight: 700;
        text-decoration: none;
    }

    .item-shop:hover {
        text-decoration: underline;
    }

    /* Delivery & payment details, folded */
    .order-more {
        margin-top: 4px;
    }

    .order-more summary {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 36px;
        font-size: 12.5px;
        font-weight: 700;
        color: #6f5a53;
        cursor: pointer;
        list-style: none;
    }

    .order-more summary::-webkit-details-marker {
        display: none;
    }

    .order-more summary i {
        transition: transform 0.2s ease;
    }

    .order-more[open] summary i {
        transform: rotate(180deg);
    }

    .order-more .order-info {
        margin-top: 8px;
    }

    @media (max-width: 640px) {
        .order-track-labels {
            display: none;
        }
    }
</style>


</head>

<body>


@include('partials.buyer-navbar', ['activeNav' => 'orders'])

<main class="main-content">

        <div class="container">

            @include('partials.page-head', [
                'title' => 'My Orders',
                'note' => count($orders ?? []) . ' ' . \Illuminate\Support\Str::plural('order', count($orders ?? [])),
            ])

            <!-- SUCCESS -->

            @if(session('success'))

                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                </div>

            @endif

            <!-- ERROR -->

            @if(session('error'))

                <div class="alert alert-error">
                    <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
                </div>

            @endif

            @if(empty($orders))

                <!-- EMPTY -->

                <div class="empty">

                    <div class="empty-icon">
                        <i class="bi bi-box-seam-fill"></i>
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

                <div class="order-filters">

                    <input
                        type="text"
                        id="orderSearchInput"
                        class="order-search-input"
                        placeholder="Search by order # or product name..."
                    >

                    @php
                        // How many orders each tab holds (same grouping as the cards below).
                        $tabGroups = [
                            'to-ship' => ['pending', 'processing', 'ready for pickup'],
                            'to-receive' => ['assigned', 'picked up', 'at sorting center', 'assigned for delivery', 'out for delivery'],
                            'delivered' => ['delivered'],
                            'cancelled' => ['cancelled', 'delivery failed', 'returned to seller'],
                        ];
                        $tabCounts = ['all' => count($orders), 'to-ship' => 0, 'to-receive' => 0, 'delivered' => 0, 'cancelled' => 0];
                        foreach ($orders as $o) {
                            $s = strtolower($o['status'] ?? 'pending');
                            $g = collect($tabGroups)->search(fn ($list) => in_array($s, $list)) ?: 'to-ship';
                            $tabCounts[$g]++;
                        }
                        $tabLabels = ['all' => 'All', 'to-ship' => 'To Ship', 'to-receive' => 'To Receive', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled / Returned'];
                    @endphp

                    <div class="order-tabs" id="orderTabs">
                        @foreach($tabLabels as $key => $label)
                            <button type="button" class="order-tab {{ $key === 'all' ? 'active' : '' }}" data-group="{{ $key }}">
                                {{ $label }} <small>{{ $tabCounts[$key] }}</small>
                            </button>
                        @endforeach
                    </div>

                </div>

                <div class="orders" id="ordersList">

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

                            $statusGroups = [
                                'to-ship' => ['pending', 'processing', 'ready for pickup'],
                                'to-receive' => ['assigned', 'picked up', 'at sorting center', 'assigned for delivery', 'out for delivery'],
                                'delivered' => ['delivered'],
                                'cancelled' => ['cancelled', 'delivery failed', 'returned to seller'],
                            ];

                            $orderGroup = 'to-ship';

                            foreach ($statusGroups as $groupKey => $groupStatuses) {
                                if (in_array($orderStatus, $groupStatuses)) {
                                    $orderGroup = $groupKey;
                                    break;
                                }
                            }

                            // Rider + map only mean something once a rider
                            // actually has the parcel; a closed order (cancelled,
                            // failed, returned) gets its own short history instead
                            // of an unfinished delivery timeline.
                            $showRider = $orderGroup === 'to-receive';
                            $isClosed = $orderGroup === 'cancelled';

                            $closedLabels = [
                                'cancelled' => 'Order Cancelled',
                                'delivery failed' => 'Delivery Failed',
                                'returned to seller' => 'Returned to Seller',
                            ];

                            $closedLabel = $closedLabels[$orderStatus] ?? 'Order Closed';

                            $closedReason = $orderStatus === 'cancelled'
                                ? ($order['cancellation_reason'] ?? null)
                                : ($order['failure_reason'] ?? $order['cancellation_reason'] ?? null);

                            $closedAt = $orderStatus === 'delivery failed' && !empty($order['delivery_failed_at'])
                                ? $order['delivery_failed_at']
                                : ($order['updated_at'] ?? null);

                            // COD: cancellable while Pending/Processing.
                            // Prepaid: never by the buyer once checked out.
                            $isCod = \App\Support\CodPolicy::isCod($order['payment_method'] ?? null);
                            $canCancel = \App\Support\CodPolicy::buyerCanCancel($order);

                            $cancelNote = null;

                            if (!$canCancel && in_array($orderGroup, ['to-ship', 'to-receive'])) {
                                $cancelNote = $isCod
                                    ? 'This order can no longer be cancelled because it is already ready for pickup or on its way. You can refuse the parcel when it arrives, or request a return after receiving it.'
                                    : 'Paid orders (GCash, Maya or card) can\'t be cancelled after checkout. You can request a return once you receive it.';
                            }

                            $orderSearchText = strtolower(
                                'order #' . ($order['id'] ?? '') . ' ' .
                                collect($order['items'] ?? [])->pluck('name')->implode(' ')
                            );

                        @endphp

                        <!-- =========================
                             ORDER CARD
                        ========================= -->

                        <div
                            class="order-card"
                            id="order-{{ $order['id'] }}"
                            data-order-group="{{ $orderGroup }}"
                            data-order-search="{{ $orderSearchText }}"
                        >

                            <!-- HEADER -->

                            <div class="order-header">

                                <div>

                                    <div class="order-id">
                                        Order #{{ $order['id'] ?? 'N/A' }}
                                    </div>

                                    <div class="date">
                                        {{ $order['date_label'] ?? 'N/A' }}
                                    </div>

                                </div>

                                <x-status-pill :status="$order['status'] ?? 'Pending'" />

                            </div>

                            @if(($order['step'] ?? 0) > 0 && ($order['step'] ?? 0) < 5)
                                <div class="order-track">
                                    <div class="order-track-steps" role="img" aria-label="Step {{ $order['step'] }} of 5: {{ $order['step_note'] }}">
                                        @for($trackStep = 1; $trackStep <= 5; $trackStep++)
                                            <span class="{{ $trackStep <= $order['step'] ? 'is-on' : '' }}"></span>
                                        @endfor
                                    </div>
                                    <div class="order-track-labels" aria-hidden="true">
                                        <span>Placed</span><span>Packed</span><span>Picked up</span><span>On the way</span><span>Delivered</span>
                                    </div>
                                    <div class="order-track-note"><i class="bi bi-info-circle"></i> {{ $order['step_note'] }}</div>
                                </div>
                            @endif

                            <!-- ITEMS -->

                            <div class="items">

                                @foreach($order['items'] ?? [] as $item)

                                    @php
                                        $itemReview = $item['review'] ?? null;
                                        $productId = $item['product_id'] ?? null;
                                    @endphp

                                    <div class="item">

                                        <div class="item-main">

                                        @if(!empty($item['slug']))
                                            <a href="{{ route('product.details', $item['slug']) }}" tabindex="-1" aria-hidden="true">
                                                <x-product-thumb :image="$item['image'] ?? null" :category="$item['category'] ?? null" size="60" />
                                            </a>
                                        @else
                                            <x-product-thumb :image="null" :category="$item['category'] ?? null" size="60" />
                                        @endif

                                        <div>

                                            <div class="item-name">
                                                {{ $item['name'] ?? 'Product' }}
                                                @if(!empty($item['variation_label']))
                                                    <span style="color:#977970; font-weight:400;">({{ $item['variation_label'] }})</span>
                                                @endif
                                            </div>

                                            <div class="item-info">

                                                Quantity:
                                                {{ $item['quantity'] ?? 1 }}

                                                @if(!empty($item['seller_name']))

                                                    •
                                                    @if(!empty($item['shop_url']))
                                                        <a href="{{ $item['shop_url'] }}" class="item-shop"><i class="bi bi-shop"></i> {{ $item['seller_name'] }}</a>
                                                    @else
                                                        {{ $item['seller_name'] }}
                                                    @endif

                                                @endif

                                            </div>

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

                                                        <i class="bi bi-star-fill"></i>
                                                        {{ $itemReview['rating'] ?? $itemReview->rating ?? 0 }}/5

                                                    </div>

                                                @else

                                                    <button
                                                        type="button"
                                                        class="rate-btn"
                                                        onclick="toggleRating('{{ $order['id'] }}-{{ $productId }}')"
                                                    >
                                                        <i class="bi bi-star-fill"></i> Rate Product
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
                                                <i class="bi bi-star-fill"></i> Rate Your Product
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
                                                            <i class="bi bi-star-fill"></i>
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
                                                            <i class="bi bi-star-fill"></i>
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
                                                            <i class="bi bi-star-fill"></i>
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
                                                            <i class="bi bi-star-fill"></i>
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
                                                            <i class="bi bi-star-fill"></i>
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
                                                        Submit Review <i class="bi bi-star-fill"></i>
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

                            <details class="order-more">

                            <summary><i class="bi bi-chevron-down"></i> Delivery &amp; payment details</summary>

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

                            </details>

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

                                    @if(empty($order['buyer_received_at']))

                                        <button
                                            type="button"
                                            class="track-btn is-ghost"
                                            onclick="toggleTracking('{{ $trackingId }}')"
                                        >
                                            @if($isClosed)
                                                <i class="bi bi-receipt"></i> View Details
                                            @else
                                                <i class="bi bi-geo-alt-fill"></i> Track Order
                                            @endif
                                        </button>

                                        @if($canCancel)
                                            <button
                                                type="button"
                                                class="cancel-order-btn"
                                                onclick="openCancelOrder('{{ route('buyer.order.cancel', $order['id']) }}', '{{ $order['id'] }}', '{{ $order['status'] }}')"
                                            >
                                                <i class="bi bi-x-lg"></i> Cancel Order
                                            </button>
                                        @endif

                                        @if(($order['status'] ?? '') === 'Delivered')
                                            <form
                                                method="POST"
                                                action="{{ route('buyer.order.received', $order['id']) }}"
                                            >
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="received-btn"
                                                    data-confirm="Confirm that you received this order?" data-confirm-ok="Yes, Received"
                                                >
                                                    <i class="bi bi-box-seam-fill"></i> Order Received
                                                </button>
                                            </form>
                                        @endif

                                    @else

                                        <div class="received-badge">
                                            <i class="bi bi-check-circle-fill"></i> Order Received
                                        </div>

                                        <button
                                            type="button"
                                            class="return-btn"
                                            onclick="openReturnRefund('{{ $order['id'] }}')"
                                        >
                                            <i class="bi bi-arrow-return-left"></i> Return / Refund
                                        </button>

                                    @endif

                                    <form method="POST" action="{{ route('buyer.order.reorder', $order['id']) }}">
                                        @csrf
                                        <button type="submit" class="track-btn {{ ($order['status'] ?? '') === 'Delivered' && empty($order['buyer_received_at']) ? 'is-ghost' : '' }}">
                                            <i class="bi bi-arrow-repeat"></i> Buy Again
                                        </button>
                                    </form>

                                </div>

                            </div>

                            @if($cancelNote)
                                <div class="cancel-note">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>{{ $cancelNote }}</span>
                                </div>
                            @endif

                            <!-- =========================
                                 TRACKING PANEL
                            ========================= -->

                            @if(!empty($order['buyer_received_at']))

                            <div
                                id="return-modal-{{ $order['id'] }}"
                                class="return-modal"
                                onclick="closeReturnRefundOutside(event, '{{ $order['id'] }}')"
                            >
                                <div class="return-modal-card" onclick="event.stopPropagation()">

                                    <div class="return-modal-head">
                                        <div>
                                            <div class="return-modal-title"><i class="bi bi-arrow-return-left"></i> Return / Refund</div>
                                            <div class="return-modal-subtitle">
                                                Order #{{ $order['id'] }}
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            class="return-close"
                                            onclick="closeReturnRefund('{{ $order['id'] }}')"
                                        >
                                            ×
                                        </button>
                                    </div>

                                    <div class="return-note">
                                        Your order has already been marked as received.
                                        Choose the item and tell us why you want a return or refund.
                                    </div>

                                    <form
                                        method="POST"
                                        action="{{ route('buyer.return-refund.store', ['orderId' => $order['id']]) }}"
                                        enctype="multipart/form-data"
                                    >
                                        @csrf

                                        <div class="return-form-group">
                                            <label for="return-item-{{ $order['id'] }}">Product</label>
                                            <select id="return-item-{{ $order['id'] }}" name="order_item_id" required>
                                                <option value="">Select product</option>
                                                @foreach($order['items'] ?? [] as $returnItem)
                                                    @if(!empty($returnItem['id']))
                                                        <option value="{{ $returnItem['id'] }}">
                                                            {{ $returnItem['name'] ?? 'Product' }}
                                                            — Qty {{ $returnItem['quantity'] ?? 1 }}
                                                            — ₱{{ number_format((float)($returnItem['subtotal'] ?? 0), 2) }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="return-form-group">
                                            <label for="request-type-{{ $order['id'] }}">Request Type</label>
                                            <select id="request-type-{{ $order['id'] }}" name="request_type" required>
                                                <option value="">Select request type</option>
                                                <option value="Return">Return the item</option>
                                                <option value="Refund">Refund only</option>
                                            </select>
                                        </div>

                                        <div class="return-form-group">
                                            <label for="return-reason-{{ $order['id'] }}">Reason</label>
                                            <select id="return-reason-{{ $order['id'] }}" name="reason" required>
                                                <option value="">Select reason</option>
                                                <option value="Wrong item received">Wrong item received</option>
                                                <option value="Damaged item">Damaged item</option>
                                                <option value="Defective product">Defective product</option>
                                                <option value="Missing item">Missing item</option>
                                                <option value="Item doesn't match description">Item doesn't match description</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>

                                        <div class="return-form-group">
                                            <label for="return-message-{{ $order['id'] }}">Additional Details</label>
                                            <textarea
                                                id="return-message-{{ $order['id'] }}"
                                                name="message"
                                                placeholder="Explain what happened (optional)"
                                            ></textarea>
                                        </div>

                                        <div class="return-form-group">
                                            <label for="return-evidence-{{ $order['id'] }}">Photo Evidence (optional)</label>
                                            @include('partials.file-picker', [
                                                'id' => 'return-evidence-' . $order['id'],
                                                'name' => 'evidence',
                                                'accept' => 'image/*',
                                                'label' => 'Attach a photo',
                                                'hint' => 'JPG or PNG · up to 4 MB',
                                            ])
                                        </div>

                                        <div class="return-actions">
                                            <button
                                                type="button"
                                                class="return-cancel"
                                                onclick="closeReturnRefund('{{ $order['id'] }}')"
                                            >
                                                Cancel
                                            </button>

                                            <button type="submit" class="return-submit">
                                                Submit Request
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        @endif

                        <div
                                id="{{ $trackingId }}"
                                class="tracking-panel"
                            >

                            <div class="tracking-title">
    <i class="bi bi-geo-alt-fill"></i> Order Tracking
</div>

@if($showRider || $orderGroup === 'delivered')

{{-- RIDER PROFILE --}}
@if(!empty($order['rider_name']))

    <div class="rider-profile">

        @if(!empty($order['rider_profile_photo']))

            <img
                src="{{ asset('storage/profile-photos/' . $order['rider_profile_photo']) }}"
                alt="{{ $order['rider_name'] }}"
                class="rider-profile-photo"
            >

        @else

            <div class="rider-profile-placeholder">
                <i class="bi bi-bicycle"></i>
            </div>

        @endif

        <div class="rider-profile-info">

            <div class="rider-profile-label">
                Delivery Rider
            </div>

            <div class="rider-profile-name">
                {{ $order['rider_name'] }}
            </div>

            @if(!empty($order['rider_email']))
                <div class="rider-profile-email">
                    {{ $order['rider_email'] }}
                </div>
            @endif

        </div>

        <div class="rider-profile-status">
            <i class="bi bi-bicycle"></i> Assigned Rider
        </div>

    </div>

@else

    <div class="rider-profile">

        <div class="rider-profile-placeholder">
            <i class="bi bi-bicycle"></i>
        </div>

        <div class="rider-profile-info">

            <div class="rider-profile-label">
                Delivery Rider
            </div>

            <div class="rider-profile-name">
                Rider not assigned yet
            </div>

            <div class="rider-profile-email">
                Your rider information will appear here once assigned.
            </div>

        </div>

    </div>

@endif

@elseif($orderGroup === 'to-ship')

    <div class="rider-profile">

        <div class="rider-profile-placeholder">
            <i class="bi bi-shop"></i>
        </div>

        <div class="rider-profile-info">

            <div class="rider-profile-label">
                Still with the seller
            </div>

            <div class="rider-profile-name">
                Not yet handed to a rider
            </div>

            <div class="rider-profile-email">
                Rider details will appear here once a rider picks up your order.
            </div>

        </div>

    </div>

@endif

                                <!-- RIDER INFO -->

                                <div class="tracking-grid">

                                    @unless($isClosed)
                                    <div class="tracking-box">

                                        <div class="tracking-box-title">
                                            <i class="bi bi-bicycle"></i> Delivery Rider
                                        </div>

                                        <div class="tracking-box-value">

                                            @if(!empty($order['rider_name']))

                                                <span class="rider-icon">
                                                    <i class="bi bi-bicycle"></i>
                                                </span>

                                                {{ $order['rider_name'] }}

                                            @else

                                                <span style="color:#8d6c62;">
                                                    Rider not assigned yet
                                                </span>

                                            @endif

                                        </div>

                                    </div>
                                    @endunless

                                    <div class="tracking-box">

                                        <div class="tracking-box-title">
                                            <i class="bi bi-box-seam-fill"></i> Current Status
                                        </div>

                                        <div class="tracking-box-value">
                                            <x-status-pill :status="$order['status'] ?? 'Pending'" />
                                        </div>

                                    </div>

                                    <div class="tracking-box">

                                        <div class="tracking-box-title">
                                            <i class="bi bi-house-door-fill"></i> Delivery Address
                                        </div>

                                        <div class="tracking-box-value">
                                            {{ $order['address'] ?? 'No address' }}
                                        </div>

                                    </div>

                                    <div class="tracking-box">

                                        <div class="tracking-box-title">
                                            <i class="bi bi-telephone-fill"></i> Contact Number
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
                                        {{ $isClosed ? 'Order History' : 'Delivery Progress' }}
                                    </div>

                                    @if($isClosed)

                                    <div class="timeline-item">

                                        <div class="timeline-line"></div>

                                        <div class="timeline-dot">
                                            <i class="bi bi-check-lg"></i>
                                        </div>

                                        <div class="timeline-content">

                                            <strong>
                                                Order Placed
                                            </strong>

                                            <span>
                                                {{ !empty($order['created_at']) ? \Carbon\Carbon::parse($order['created_at'])->format('M d, Y · h:i A') : 'Your order was placed.' }}
                                            </span>

                                        </div>

                                    </div>

                                    <div class="timeline-item">

                                        <div class="timeline-dot danger">
                                            <i class="bi bi-x-lg"></i>
                                        </div>

                                        <div class="timeline-content">

                                            <strong>
                                                {{ $closedLabel }}
                                            </strong>

                                            @if(!empty($closedAt))
                                                <span>
                                                    {{ \Carbon\Carbon::parse($closedAt)->format('M d, Y · h:i A') }}
                                                </span>
                                            @endif

                                            @if(!empty($closedReason))
                                                <span class="timeline-reason">
                                                    Reason: {{ $closedReason }}
                                                </span>
                                            @endif

                                            @if($orderStatus === 'cancelled')
                                                <span class="timeline-reason">
                                                    This order will not be shipped.
                                                </span>
                                            @endif

                                        </div>

                                    </div>

                                    @else

                                    <!-- ORDER PLACED -->

                                    <div class="timeline-item">

                                        <div class="timeline-line"></div>

                                        <div class="timeline-dot">
                                            <i class="bi bi-check-lg"></i>
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
                                                        'at sorting center',
                                                        'assigned for delivery',
                                                        'out for delivery',
                                                        'delivered'
                                                    ]
                                                )
                                                ? ''
                                                : 'gray'
                                            }}"
                                        >
                                            <i class="bi bi-check-lg"></i>
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
                                                        'assigned',
                                                        'picked up',
                                                        'at sorting center',
                                                        'assigned for delivery',
                                                        'out for delivery',
                                                        'delivered'
                                                    ]
                                                )
                                                ? ''
                                                : 'gray'
                                            }}"
                                        >
                                            <i class="bi bi-check-lg"></i>
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
                                                        'at sorting center',
                                                        'assigned for delivery',
                                                        'out for delivery',
                                                        'delivered'
                                                    ]
                                                )
                                                ? ''
                                                : 'gray'
                                            }}"
                                        >
                                            <i class="bi bi-truck"></i>
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

                                    <!-- AT SORTING CENTER -->

                                    <div class="timeline-item">

                                        <div class="timeline-line"></div>

                                        <div
                                            class="timeline-dot
                                            {{
                                                in_array(
                                                    $orderStatus,
                                                    [
                                                        'at sorting center',
                                                        'assigned for delivery',
                                                        'out for delivery',
                                                        'delivered'
                                                    ]
                                                )
                                                ? ''
                                                : 'gray'
                                            }}"
                                        >
                                            <i class="bi bi-box-seam-fill"></i>
                                        </div>

                                        <div class="timeline-content">

                                            <strong>
                                                At Sorting Center
                                            </strong>

                                            <span>
                                                Your parcel has arrived at the sorting facility and is being prepared for delivery.
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
                                            <i class="bi bi-bicycle"></i>
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
                                            <i class="bi bi-check-lg"></i>
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

                                    @endif

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

                <div class="empty" id="ordersNoMatch" style="display:none;">
                    <div class="empty-icon"><i class="bi bi-search"></i></div>
                    <h2>No Matching Orders</h2>
                    <p>Try a different search term or filter.</p>
                </div>

            @endif

        </div>

    </main>

<script>

    /* =========================
       TOGGLE TRACKING
    ========================= */

    function toggleTracking(id) {

        const panel = document.getElementById(id);

        if (panel) {
            panel.classList.toggle('active');
        }
    }

    /* =========================
       RETURN / REFUND MODAL
    ========================= */

    function openReturnRefund(orderId) {
        const modal = document.getElementById('return-modal-' + orderId);
        if (!modal) return;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeReturnRefund(orderId) {
        const modal = document.getElementById('return-modal-' + orderId);
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    function closeReturnRefundOutside(event, orderId) {
        if (event.target.classList.contains('return-modal')) {
            closeReturnRefund(orderId);
        }
    }

    document.addEventListener('keydown', function(event) {
        if (event.key !== 'Escape') return;

        document.querySelectorAll('.return-modal.active').forEach(function(modal) {
            modal.classList.remove('active');
        });

        document.body.style.overflow = '';
    });

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

            bbAlert('Please select a star rating first.');

            return false;
        }

        return true;
    }

    /* =========================
       ORDER SEARCH / TAB FILTER
    ========================= */

    (function () {

        var searchInput = document.getElementById('orderSearchInput');
        var tabs = document.querySelectorAll('.order-tab');
        var noMatch = document.getElementById('ordersNoMatch');
        var ordersList = document.getElementById('ordersList');

        if (!searchInput || !ordersList) {
            return;
        }

        var activeGroup = 'all';

        function applyFilters() {

            var query = searchInput.value.trim().toLowerCase();
            var cards = ordersList.querySelectorAll('.order-card');
            var visibleCount = 0;

            cards.forEach(function (card) {

                var matchesGroup = activeGroup === 'all' || card.dataset.orderGroup === activeGroup;
                var matchesSearch = query === '' || (card.dataset.orderSearch || '').indexOf(query) !== -1;
                var visible = matchesGroup && matchesSearch;

                card.style.display = visible ? '' : 'none';

                if (visible) visibleCount++;
            });

            if (noMatch) {
                noMatch.style.display = visibleCount === 0 ? '' : 'none';
            }
        }

        searchInput.addEventListener('input', applyFilters);

        tabs.forEach(function (tab) {

            tab.addEventListener('click', function () {

                tabs.forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');

                activeGroup = tab.dataset.group;

                applyFilters();
            });
        });

        // Opened from a dashboard shortcut (?tab=to-ship etc.): start on that tab.
        var initialTab = new URLSearchParams(window.location.search).get('tab');
        var initialButton = initialTab
            ? document.querySelector('.order-tab[data-group="' + CSS.escape(initialTab) + '"]')
            : null;

        if (initialButton) {
            initialButton.click();
        }

    })();

    /* =========================
       OPENED FROM A NOTIFICATION (#order-123)
       Scroll to that order, open its tracking and flash it.
    ========================= */

    (function () {

        var match = window.location.hash.match(/^#order-(\d+)$/);

        if (!match) {
            return;
        }

        var card = document.getElementById('order-' + match[1]);

        if (!card) {
            return;
        }

        card.style.display = '';

        var panel = document.getElementById('tracking-' + match[1]);

        if (panel) {
            panel.classList.add('active');
        }

        card.classList.add('is-highlighted');

        setTimeout(function () {
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 150);

        setTimeout(function () {
            card.classList.remove('is-highlighted');
        }, 3200);

    })();

</script>


    <div
        id="cancel-order-modal"
        class="return-modal"
        onclick="if (event.target === this) closeCancelOrder()"
    >
        <div class="return-modal-card">

            <div class="return-modal-head">
                <div>
                    <div class="return-modal-title"><i class="bi bi-x-circle"></i> Cancel Order</div>
                    <div class="return-modal-subtitle" id="cancel-order-subtitle"></div>
                </div>

                <button type="button" class="return-close" onclick="closeCancelOrder()">×</button>
            </div>

            <div class="return-note" id="cancel-order-processing-note" style="display:none;">
                The seller has already started preparing this order. Cancelling now still returns the items to their stock, but please only cancel if you really need to.
            </div>

            <form method="POST" id="cancel-order-form" action="">
                @csrf

                <div class="return-form-group">
                    <label>Why are you cancelling?</label>

                    <div class="cancel-reasons">
                        @foreach($cancelReasons ?? \App\Support\CodPolicy::CANCEL_REASONS as $reasonOption)
                            <label class="cancel-reason">
                                <input type="radio" name="cancel_reason" value="{{ $reasonOption }}" required>
                                <span>{{ $reasonOption }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="return-form-group">
                    <label for="cancel-details" id="cancel-details-label">Anything else? (optional)</label>
                    <textarea id="cancel-details" name="cancel_details" maxlength="250" placeholder="Tell the seller a bit more…"></textarea>
                </div>

                <div class="return-note {{ ($codStatus['strikes'] ?? 0) >= ($codStatus['limit'] ?? 3) - 1 ? 'is-warning' : '' }}">
                    <i class="bi bi-shield-exclamation"></i>
                    You have <strong>{{ $codStatus['strikes'] ?? 0 }} of {{ $codStatus['limit'] ?? 3 }}</strong>
                    cancellations or refused parcels in the last 30 days.
                    Reaching {{ $codStatus['limit'] ?? 3 }} pauses Cash on Delivery on your account for a while.
                </div>

                <div class="return-actions">
                    <button type="button" class="return-cancel" onclick="closeCancelOrder()">Keep Order</button>
                    <button type="submit" class="return-submit cancel-submit">Cancel Order</button>
                </div>
            </form>

        </div>
    </div>

    <script>
        function openCancelOrder(action, orderId, status) {
            var modal = document.getElementById('cancel-order-modal');
            var form = document.getElementById('cancel-order-form');

            form.reset();
            form.action = action;
            syncCancelDetails();

            document.getElementById('cancel-order-subtitle').textContent = 'Order #' + orderId;
            document.getElementById('cancel-order-processing-note').style.display =
                status === 'Processing' ? 'block' : 'none';

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCancelOrder() {
            document.getElementById('cancel-order-modal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // "Other" needs an explanation; for the rest it's optional.
        function syncCancelDetails() {
            var picked = document.querySelector('#cancel-order-form input[name="cancel_reason"]:checked');
            var isOther = picked && picked.value === 'Other';
            var details = document.getElementById('cancel-details');

            details.required = !!isOther;
            document.getElementById('cancel-details-label').textContent =
                isOther ? 'Please tell us why' : 'Anything else? (optional)';
        }

        document.querySelectorAll('#cancel-order-form input[name="cancel_reason"]').forEach(function (radio) {
            radio.addEventListener('change', syncCancelDetails);
        });
    </script>

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>

</html>
