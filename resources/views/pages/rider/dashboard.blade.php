<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'BoomBuy — Rider Dashboard'])

    <link rel="stylesheet" href="{{ vasset('css/views/rider-dashboard.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">
</head>

<body>

    <x-layout.rider-sidebar active="dashboard" :user="$user" />


    <!-- =========================================================
         DASHBOARD DATA
    ========================================================= -->

    @php

        $availableDeliveries = $availableOrders ?? [];

        $myDeliveriesList = $myDeliveries ?? [];

        $availableCount = count($availableDeliveries);

        $totalDeliveries = count($myDeliveriesList);

        $inTransitCount = count($inTransit ?? []);

        $deliveredCount = count($delivered ?? []);

        $myActiveDeliveries = array_values(
            array_filter(
                $myDeliveriesList,
                function ($delivery) {

                    $status = $delivery['status'] ?? '';

                    return $status !== 'Delivered';

                }
            )
        );

    @endphp


    <!-- =========================================================
         MAIN
    ========================================================= -->

    <main class="main-content">

        <div class="container">

            @include('partials.announcement-banner')

            <!-- =====================================================
                 WELCOME
            ===================================================== -->

            <section class="welcome">

                <small>
                    Rider Center
                </small>

                <h1>
                    Welcome,
                    {{ $user['name'] ?? 'Rider' }}!
                </h1>

                <p>
                    Manage your deliveries, claim available orders,
                    and keep track of your delivery progress.
                </p>

            </section>


            <!-- =====================================================
                 QUICK ACTIONS
            ===================================================== -->

            <div class="quick-actions">

                <a
                    href="{{ route('rider.deliveries') }}"
                    class="quick-card"
                >

                    <div class="quick-card-icon">
                        <i class="bi bi-truck"></i>
                    </div>

                    <strong>
                        My Deliveries
                    </strong>

                    <span>
                        View available and assigned deliveries.
                    </span>

                </a>


                <a
                    href="{{ route('rider.profile') }}"
                    class="quick-card"
                >

                    <div class="quick-card-icon">
                        <i class="bi bi-person-fill"></i>
                    </div>

                    <strong>
                        My Profile
                    </strong>

                    <span>
                        View and manage your rider information.
                    </span>

                </a>


                <a
                    href="{{ route('rider.notifications') }}"
                    class="quick-card"
                >

                    <div class="quick-card-icon">
                        <i class="bi bi-bell-fill"></i>
                    </div>

                    <strong>
                        Notifications
                    </strong>

                    <span>
                        Check your latest rider updates.
                    </span>

                </a>

            </div>


            <!-- =====================================================
                 STATISTICS
            ===================================================== -->

            <section class="stats">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>

                    <div class="stat-title">
                        Total Deliveries
                    </div>

                    <div class="stat-number">
                        {{ $totalDeliveries }}
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-circle-fill" style="color:var(--teal);"></i>
                    </div>

                    <div class="stat-title">
                        Available Orders
                    </div>

                    <div class="stat-number">
                        {{ $availableCount }}
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-truck"></i>
                    </div>

                    <div class="stat-title">
                        In Transit
                    </div>

                    <div class="stat-number">
                        {{ $inTransitCount }}
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>

                    <div class="stat-title">
                        Delivered
                    </div>

                    <div class="stat-number">
                        {{ $deliveredCount }}
                    </div>

                </div>

            </section>


            <!-- =====================================================
                 AVAILABLE ORDERS
            ===================================================== -->

            <div class="section-title">

                <div>

                    <h2>
                        Available Orders
                    </h2>

                    <p>
                        Orders that are ready for pickup.
                    </p>

                </div>

                <a
                    href="{{ route('rider.deliveries') }}"
                    class="view-all"
                >
                    View All →
                </a>

            </div>


            @if(count($availableDeliveries) > 0)

                <div class="delivery-list">

                    @foreach(
                        array_slice($availableDeliveries, 0, 6)
                        as $delivery
                    )

                        <div class="delivery-card">

                            <div class="delivery-header">

                                <div class="delivery-id">

                                    <i class="bi bi-box-seam-fill"></i>

                                    Order #{{ $delivery['id'] ?? 'N/A' }}

                                </div>

                                <x-status-pill status="Ready for Pickup" />

                            </div>


                            <div class="available-label">

                                <i class="bi bi-truck"></i> This order is available for pickup.

                            </div>


                            <div class="delivery-info">

                                <div>

                                    <i class="bi bi-person-fill"></i>

                                    <strong>
                                        Customer:
                                    </strong>

                                    {{ $delivery['buyer_name'] ?? 'Customer' }}

                                </div>


                                <div>

                                    <i class="bi bi-geo-alt-fill"></i>

                                    <strong>
                                        Address:
                                    </strong>

                                    {{ $delivery['address'] ?? 'No address provided' }}

                                </div>


                                <div>

                                    <i class="bi bi-telephone-fill"></i>

                                    <strong>
                                        Phone:
                                    </strong>

                                    {{ $delivery['phone'] ?? 'No phone provided' }}

                                </div>


                                <div>

                                    <i class="bi bi-cash-stack"></i>

                                    <strong>
                                        Total:
                                    </strong>

                                    ₱{{ number_format($delivery['total'] ?? 0, 2) }}

                                </div>


                                <div>

                                    <i class="bi bi-credit-card-fill"></i>

                                    <strong>
                                        Payment:
                                    </strong>

                                    {{ $delivery['payment'] ?? 'N/A' }}

                                </div>


                                <div>

                                    <i class="bi bi-shop"></i>

                                    <strong>
                                        Items:
                                    </strong>

                                    {{ count($delivery['items'] ?? []) }}

                                </div>

                            </div>


                            <div class="delivery-actions">

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'rider.delivery.claim',
                                        $delivery['id']
                                    ) }}"
                                >

                                    @csrf

                                    <button
                                        type="submit"
                                        class="claim-btn"
                                    >
                                        <i class="bi bi-truck"></i> Claim Delivery
                                    </button>

                                </form>


                                <a
                                    href="{{ route(
                                        'rider.delivery.details',
                                        $delivery['id']
                                    ) }}"
                                    class="view-btn"
                                >
                                    <i class="bi bi-eye-fill"></i> View Details
                                </a>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="empty">

                    <div class="empty-icon">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>

                    <div class="empty-title">
                        No Available Orders
                    </div>

                    <div class="empty-text">

                        Orders marked

                        <strong>
                            "Ready for Pickup"
                        </strong>

                        will appear here.

                    </div>

                </div>

            @endif


            <!-- =====================================================
                 ACTIVE DELIVERIES
            ===================================================== -->

            <div class="section-title">

                <div>

                    <h2>
                        <i class="bi bi-envelope-paper-fill"></i> Items for Pickup
                    </h2>

                    <p>
                        Orders you've accepted from sellers — pick up and hand over to the Sorting Center.
                    </p>

                </div>

                <a
                    href="{{ route('rider.deliveries') }}"
                    class="view-all"
                >
                    View All →
                </a>

            </div>


            @if(count($myActiveDeliveries) > 0)

                <div class="delivery-list">

                    @foreach(
                        array_slice($myActiveDeliveries, 0, 6)
                        as $delivery
                    )

                        @php

                            $status =
                                $delivery['status']
                                ?? 'Picked Up';

                        @endphp


                        <div class="delivery-card">

                            <div class="delivery-header">

                                <div class="delivery-id">

                                    <i class="bi bi-box-seam-fill"></i>

                                    Order #{{ $delivery['id'] ?? 'N/A' }}

                                </div>

                                <x-status-pill :status="$status" />

                            </div>


                            <div class="active-label">

                                <i class="bi bi-truck"></i> This order is assigned to you.

                                @if($status === 'Assigned')

                                    Proceed to the seller's location and confirm pickup.

                                @elseif($status === 'Picked Up')

                                    Bring the parcel to the Sorting Center.

                                @elseif($status === 'Out for Delivery')

                                    This order is currently out for delivery.

                                @endif

                            </div>


                            <div class="delivery-info">

                                <div>

                                    <i class="bi bi-person-fill"></i>

                                    <strong>
                                        Customer:
                                    </strong>

                                    {{ $delivery['buyer_name'] ?? 'Customer' }}

                                </div>


                                <div>

                                    <i class="bi bi-geo-alt-fill"></i>

                                    <strong>
                                        Address:
                                    </strong>

                                    {{ $delivery['address'] ?? 'No address provided' }}

                                </div>


                                <div>

                                    <i class="bi bi-telephone-fill"></i>

                                    <strong>
                                        Phone:
                                    </strong>

                                    {{ $delivery['phone'] ?? 'No phone provided' }}

                                </div>


                                <div>

                                    <i class="bi bi-cash-stack"></i>

                                    <strong>
                                        Total:
                                    </strong>

                                    ₱{{ number_format($delivery['total'] ?? 0, 2) }}

                                </div>


                                <div>

                                    <i class="bi bi-credit-card-fill"></i>

                                    <strong>
                                        Payment:
                                    </strong>

                                    {{ $delivery['payment'] ?? 'N/A' }}

                                </div>


                                <div>

                                    <i class="bi bi-shop"></i>

                                    <strong>
                                        Items:
                                    </strong>

                                    {{ count($delivery['items'] ?? []) }}

                                </div>

                            </div>


                            <div class="delivery-actions">

                                <a
                                    href="{{ route(
                                        'rider.delivery.details',
                                        $delivery['id']
                                    ) }}"
                                    class="view-btn"
                                >
                                    <i class="bi bi-eye-fill"></i> View Details
                                </a>


                                <a
                                    href="{{ route(
                                        'rider.delivery.details',
                                        $delivery['id']
                                    ) }}"
                                    class="status-btn"
                                >
                                    <i class="bi bi-arrow-repeat"></i> Update Status
                                </a>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="empty">

                    <div class="empty-icon">
                        <i class="bi bi-truck"></i>
                    </div>

                    <div class="empty-title">
                        No Active Deliveries
                    </div>

                    <div class="empty-text">
                        Orders you claim will appear here.
                    </div>

                </div>

            @endif


            <!-- =====================================================
                 ITEMS FOR DELIVERY (assigned by the Sorting Center)
            ===================================================== -->

            <div class="section-title">

                <div>

                    <h2>
                        <i class="bi bi-box-seam-fill"></i> Items for Delivery
                    </h2>

                    <p>
                        Parcels assigned to you by the Sorting Center — pick up there and deliver to the buyer.
                    </p>

                </div>

            </div>

            @if(count($myDeliveryAssignments) > 0)

                <div class="delivery-list">

                    @foreach($myDeliveryAssignments as $delivery)

                        @php
                            $deliveryStatus = $delivery['status'] ?? 'Assigned for Delivery';
                        @endphp

                        <div class="delivery-card">

                            <div class="delivery-header">

                                <div class="delivery-id">
                                    <i class="bi bi-box-seam-fill"></i>
                                    Order #{{ $delivery['id'] ?? 'N/A' }}
                                </div>

                                <x-status-pill :status="$deliveryStatus" />

                            </div>

                            <div class="active-label">
                                <i class="bi bi-truck"></i>
                                @if($deliveryStatus === 'Assigned for Delivery')
                                    Pick up this parcel from the Sorting Center.
                                @else
                                    Currently out for delivery.
                                @endif
                            </div>

                            <div class="delivery-info">

                                <div>
                                    <i class="bi bi-person-fill"></i>
                                    <strong>Customer:</strong>
                                    {{ $delivery['buyer_name'] ?? 'Customer' }}
                                </div>

                                <div>
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <strong>Address:</strong>
                                    {{ $delivery['address'] ?? 'No address provided' }}
                                </div>

                                <div>
                                    <i class="bi bi-cash-stack"></i>
                                    <strong>Total:</strong>
                                    ₱{{ number_format($delivery['total'] ?? 0, 2) }}
                                </div>

                            </div>

                            <div class="delivery-actions">

                                <a href="{{ route('rider.delivery.details', $delivery['id']) }}" class="view-btn">
                                    <i class="bi bi-eye-fill"></i> View Details
                                </a>

                                <a href="{{ route('rider.delivery.details', $delivery['id']) }}" class="status-btn">
                                    <i class="bi bi-arrow-repeat"></i> Update Status
                                </a>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="empty">

                    <div class="empty-icon"><i class="bi bi-box-seam-fill"></i></div>

                    <div class="empty-title">
                        No Delivery Assignments
                    </div>

                    <div class="empty-text">
                        Parcels assigned to you by the Sorting Center will appear here.
                    </div>

                </div>

            @endif


            <!-- =====================================================
                 RIDER TIP
            ===================================================== -->

            <div class="rider-tip">

                <div class="rider-tip-title">
                    <i class="bi bi-lightbulb-fill"></i> Rider Tip
                </div>

                <div class="rider-tip-text">

                    Orders become available after the seller
                    changes the order status to
                    <strong>
                        Ready for Pickup
                    </strong>.

                    Claim the order to start your delivery.

                </div>

            </div>


        </div>

    </main>


    <!-- =========================================================
         FOOTER
    ========================================================= -->

    <footer>

        <div>

            © 2026

            <strong>
                BoomBuy
            </strong>

        </div>

        <div>
            Your Marketplace for Everything
        </div>

    </footer>


    @include('partials.pwa-register')
    @include('partials.confirm-modal')

</body>

</html>