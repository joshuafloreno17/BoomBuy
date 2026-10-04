<!DOCTYPE html>
<html lang="en">

<head>

    @include('partials.head', ['title' => 'My Deliveries — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/rider-deliveries.css') }}">

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