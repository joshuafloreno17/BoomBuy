<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Deliveries — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/rider-sidebar.css') }}">

    <style>

        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #fff7f4;
            color: #172033;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button,
        input,
        select {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* =========================================================
           RIDER SIDEBAR
        ========================================================= */

        

        /* =========================================================
           BRAND
        ========================================================= */

        

        

        

        /* =========================================================
           SIDEBAR NAVIGATION
        ========================================================= */

        

        

        

        

        

        

        /* =========================================================
           SIDEBAR BOTTOM
        ========================================================= */

        

        

        

        

        /* =========================================================
           MAIN
        ========================================================= */

        .main {

            min-height: 100vh;

            padding: 38px 40px 70px;
        }

        .content-wrapper {

            width: 100%;

            max-width: 1200px;

            margin: 0 auto;
        }

        /* =========================================================
           TOPBAR
        ========================================================= */

        .topbar {

            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 20px;

            margin-bottom: 28px;
        }

        .topbar h1 {

            font-family: 'Baloo 2', sans-serif;

            color: #172033;

            font-size: 32px;
            font-weight: 800;

            line-height: 1.15;
        }

        .subtitle {

            margin-top: 5px;

            color: #977970;

            font-size: 13px;

            line-height: 1.5;
        }

        .profile {

            display: flex;
            align-items: center;

            gap: 9px;

            background: #ffffff;

            border: 1px solid #f7e5e0;

            padding: 10px 15px;

            border-radius: 11px;

            color: #977970;

            font-size: 12px;

            flex-shrink: 0;
        }

        .profile strong {

            color: #172033;

            font-weight: 800;
        }

        /* =========================================================
           ALERTS
        ========================================================= */

        .alert {

            padding: 13px 17px;

            border-radius: 11px;

            margin-bottom: 20px;

            font-size: 12px;
            font-weight: 600;

            border: 1px solid transparent;
        }

        .success {

            background: #ecfdf5;

            color: #047857;

            border-color: #bbf7d0;
        }

        .error {

            background: #fff1f2;

            color: #be123c;

            border-color: #fecdd3;
        }

        /* =========================================================
           FILTER
        ========================================================= */

        .filter-box {

            display: flex;
            align-items: center;

            gap: 12px;

            background: #ffffff;

            border: 1px solid #f7e5e0;

            padding: 16px 18px;

            border-radius: 14px;

            margin-bottom: 25px;
        }

        .filter-box label {

            color: #977970;

            font-size: 12px;
            font-weight: 700;
        }

        .filter-box select {

            min-width: 190px;

            padding: 9px 12px;

            border: 1px solid #f3d8d0;

            border-radius: 9px;

            outline: none;

            background: #ffffff;

            color: #523d36;

            font-size: 12px;

            cursor: pointer;
        }

        .filter-box select:focus {

            border-color: #ef4715;

            box-shadow: 0 0 0 3px rgba(232, 66, 15, .08);
        }

        /* =========================================================
           DELIVERY GRID
        ========================================================= */

        .deliveries {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px;
        }

        /* =========================================================
           DELIVERY CARD
        ========================================================= */

        .delivery-card {

            background: #ffffff;

            border: 1px solid #f7e5e0;

            border-radius: 15px;

            padding: 20px;

            transition: .2s ease;

            overflow: hidden;
        }

        .delivery-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 10px 28px rgba(232, 66, 15, .08);
        }

        .delivery-header {

            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 12px;

            margin-bottom: 17px;
        }

        .order-id {

            color: #172033;

            font-family: 'Baloo 2', sans-serif;

            font-size: 19px;
            font-weight: 800;
        }

        /* =========================================================
           STATUS
        ========================================================= */

        .status {

            padding: 6px 10px;

            border-radius: 999px;

            font-size: 10px;
            font-weight: 800;

            white-space: nowrap;
        }

        .status.delivered {

            background: #ecfdf5;

            color: #059669;
        }

        .status.transit {

            background: #fff0eb;

            color: #e8420f;
        }

        .status.pending {

            background: #fff8ed;

            color: #b77900;
        }

        .status.ready {

            background: #fff0eb;

            color: #e8420f;
        }

        /* =========================================================
           INFO
        ========================================================= */

        .info {

            border-top: 1px solid #f7e5e0;

            padding-top: 13px;
        }

        .info-row {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            padding: 7px 0;

            font-size: 12px;
        }

        .info-label {

            color: #977970;

            flex-shrink: 0;
        }

        .info-value {

            color: #523d36;

            font-weight: 600;

            text-align: right;

            word-break: break-word;
        }

        /* =========================================================
           BUTTONS
        ========================================================= */

        .buttons {

            display: flex;

            gap: 8px;

            margin-top: 17px;

            flex-wrap: wrap;
        }

        .btn {

            min-height: 36px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 8px 12px;

            border: none;

            border-radius: 9px;

            text-decoration: none;

            text-align: center;

            cursor: pointer;

            font-size: 11px;
            font-weight: 800;

            transition: .2s ease;
        }

        .btn:hover {

            transform: translateY(-1px);
        }

        /* VIEW */

        .view-btn {

            background: #ef4715;

            color: #ffffff;

            flex: 1;
        }

        .view-btn:hover {

            background: #cf370b;
        }

        /* CLAIM */

        .claim-form {

            flex: 1;
        }

        .claim-btn {

            width: 100%;

            background: #16a34a;

            color: #ffffff;

            font-weight: 800;
        }

        .claim-btn:hover {

            background: #15803d;
        }

        /* UPDATE */

        .status-btn {

            background: #fff0eb;

            color: #e8420f;

            border: 1px solid #f6cfc4;

            flex: 1;
        }

        .status-btn:hover {

            background: #ffe3da;
        }

        /* =========================================================
           EMPTY
        ========================================================= */

        .empty {

            background: #ffffff;

            border: 1px solid #f7e5e0;

            border-radius: 15px;

            padding: 55px 20px;

            text-align: center;

            box-shadow: 0 6px 20px rgba(232, 66, 15, .04);
        }

        .empty-icon {

            width: 60px;
            height: 60px;

            display: flex;

            align-items: center;
            justify-content: center;

            margin: 0 auto 13px;

            background: #fff0eb;

            border-radius: 16px;

            font-size: 27px;
        }

        .empty h2 {

            color: #172033;

            font-family: 'Baloo 2', sans-serif;

            font-size: 21px;
            font-weight: 800;

            margin-bottom: 5px;
        }

        .empty p {

            color: #977970;

            font-size: 12px;
        }

        /* =========================================================
           FOOTER
        ========================================================= */

        .page-footer {

            margin-top: 35px;

            padding-top: 22px;

            border-top: 1px solid #f7e5e0;

            display: flex;

            justify-content: space-between;

            gap: 15px;

            color: #977970;

            font-size: 11px;
        }

        .page-footer strong {

            color: #e8420f;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1000px) {

            .deliveries {

                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 760px) {

            

            

            

            

            

            

            

            

            

            

            .main {

                padding: 28px 22px 55px;
            }

        }

        @media (max-width: 560px) {

            

            

            

            

            

            

            

            

            

            

            

            .main {

                padding: 22px 14px 45px;
            }

            .topbar {

                flex-direction: column;

                align-items: flex-start;

                margin-bottom: 22px;
            }

            .topbar h1 {

                font-size: 28px;
            }

            .profile {

                width: 100%;

                justify-content: center;
            }

            .filter-box {

                flex-direction: column;

                align-items: flex-start;
            }

            .filter-box select {

                width: 100%;
            }

            .delivery-header {

                align-items: flex-start;

                flex-direction: column;
            }

            .buttons {

                flex-direction: column;
            }

            .buttons .btn,
            .claim-form {

                width: 100%;

                flex: none;
            }

            .page-footer {

                flex-direction: column;

                text-align: center;
            }

        }

    </style>

</head>


<body>


    <!-- =========================================================
         RIDER SIDEBAR
    ========================================================= -->

    <x-layout.rider-sidebar active="deliveries" :user="$user" />



    <!-- =========================================================
         MAIN
    ========================================================= -->

    <main class="main main-content">


        <div class="content-wrapper">


            <!-- =====================================================
                 TOPBAR
            ===================================================== -->

            <div class="topbar">


                <div>

                    <h1>
                        My Deliveries
                    </h1>

                    <p class="subtitle">
                        Manage and track your assigned orders.
                    </p>

                </div>


                <div class="profile">

                    <i class="bi bi-bicycle"></i>

                    <strong>
                        {{ $user['name'] ?? 'Rider' }}
                    </strong>

                </div>


            </div>



            <!-- =====================================================
                 ALERTS
            ===================================================== -->

            @if(session('success'))

                <div class="alert success">
                    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                </div>

            @endif


            @if(session('error'))

                <div class="alert error">
                    <i class="bi bi-x-circle-fill"></i> {{ session('error') }}
                </div>

            @endif



            <!-- =====================================================
                 FILTER
            ===================================================== -->

            <div class="filter-box">

                <label for="statusFilter">
                    Filter by Status:
                </label>


                <select id="statusFilter">

                    <option value="all">
                        All Deliveries
                    </option>

                    <option value="Pending">
                        Pending
                    </option>

                    <option value="Ready for Pickup">
                        Ready for Pickup
                    </option>

                    <option value="Assigned">
                        Assigned
                    </option>

                    <option value="Picked Up">
                        Picked Up
                    </option>

                    <option value="At Sorting Center">
                        At Sorting Center
                    </option>

                    <option value="Assigned for Delivery">
                        Assigned for Delivery
                    </option>

                    <option value="Out for Delivery">
                        Out for Delivery
                    </option>

                    <option value="Delivered">
                        Delivered
                    </option>

                </select>

            </div>



            <!-- =====================================================
                 DELIVERIES
            ===================================================== -->

            @if(count($deliveries ?? []) > 0)


                <div class="deliveries">


                    @foreach($deliveries as $delivery)


                        @php

                            $status =
                                $delivery['status']
                                ?? 'Pending';

                        @endphp



                        <!-- DELIVERY CARD -->

                        <div
                            class="delivery-card"
                            data-status="{{ $status }}"
                        >


                            <!-- HEADER -->

                            <div class="delivery-header">


                                <div class="order-id">

                                    <i class="bi bi-box-seam-fill"></i>

                                    Order #{{ $delivery['id'] ?? 'N/A' }}

                                </div>


                                <x-status-pill :status="$status" />


                            </div>



                            <!-- INFO -->

                            <div class="info">


                                <div class="info-row">

                                    <span class="info-label">
                                        Customer
                                    </span>

                                    <span class="info-value">

                                        {{ $delivery['buyer_name'] ?? 'Customer' }}

                                    </span>

                                </div>



                                <div class="info-row">

                                    <span class="info-label">
                                        Address
                                    </span>

                                    <span class="info-value">

                                        {{ $delivery['address'] ?? 'No address provided' }}

                                    </span>

                                </div>



                                <div class="info-row">

                                    <span class="info-label">
                                        Amount
                                    </span>

                                    <span class="info-value">

                                        ₱{{ number_format($delivery['total'] ?? 0, 2) }}

                                    </span>

                                </div>



                                <div class="info-row">

                                    <span class="info-label">
                                        Payment
                                    </span>

                                    <span class="info-value">

                                        {{ $delivery['payment'] ?? 'Cash on Delivery' }}

                                    </span>

                                </div>



                                @if(
                                    isset($delivery['items']) &&
                                    is_array($delivery['items'])
                                )

                                    <div class="info-row">

                                        <span class="info-label">
                                            Items
                                        </span>

                                        <span class="info-value">

                                            {{ count($delivery['items']) }}

                                            item(s)

                                        </span>

                                    </div>

                                @endif


                            </div>



                            <!-- BUTTONS -->

                            <div class="buttons">


                                {{-- READY FOR PICKUP --}}

                                @if(
                                    $status === 'Ready for Pickup' &&
                                    empty($delivery['rider_id'] ?? null)
                                )


                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'rider.delivery.claim',
                                            $delivery['id']
                                        ) }}"
                                        class="claim-form"
                                    >

                                        @csrf

                                        <button
                                            type="submit"
                                            class="btn claim-btn"
                                        >

                                            <i class="bi bi-truck"></i> Accept Delivery

                                        </button>

                                    </form>


                                @endif



                                {{-- VIEW DETAILS --}}

                                <a
                                    href="{{ route(
                                        'rider.delivery.details',
                                        $delivery['id']
                                    ) }}"
                                    class="btn view-btn"
                                >

                                    <i class="bi bi-eye-fill"></i> View Details

                                </a>



                                {{-- UPDATE STATUS --}}

                                @if(
                                    (
                                        !empty($delivery['rider_id'] ?? null) ||
                                        !empty($delivery['delivery_rider_id'] ?? null)
                                    ) &&
                                    $status !== 'Delivered'
                                )


                                    <a
                                        href="{{ route(
                                            'rider.delivery.details',
                                            $delivery['id']
                                        ) }}"
                                        class="btn status-btn"
                                    >

                                        <i class="bi bi-arrow-repeat"></i> Update Status

                                    </a>


                                @endif


                            </div>


                        </div>


                    @endforeach


                </div>


            @else


                <!-- EMPTY STATE -->

                <div class="empty">


                    <div class="empty-icon">
                        <i class="bi bi-truck"></i>
                    </div>


                    <h2>
                        No Deliveries Yet
                    </h2>


                    <p>
                        Orders assigned to you will appear here.
                    </p>


                </div>


            @endif



            <!-- =====================================================
                 FOOTER
            ===================================================== -->

            <div class="page-footer">

                <div>

                    © 2026

                    <strong>
                        BoomBuy
                    </strong>

                </div>


                <div>
                    Rider Center
                </div>

            </div>


        </div>


    </main>



    <!-- =========================================================
         FILTER SCRIPT
    ========================================================= -->

    <script>

        const statusFilter =
            document.getElementById('statusFilter');


        const deliveryCards =
            document.querySelectorAll('.delivery-card');


        statusFilter.addEventListener(
            'change',
            function () {

                const selected =
                    this.value;


                deliveryCards.forEach(
                    function (card) {

                        const status =
                            card.dataset.status;


                        if (
                            selected === 'all' ||
                            status === selected
                        ) {

                            card.style.display = '';

                        } else {

                            card.style.display = 'none';

                        }

                    }
                );

            }
        );

    </script>


    @include('partials.pwa-register')
    @include('partials.confirm-modal')

</body>

</html>