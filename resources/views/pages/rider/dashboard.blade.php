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

        $myDeliveriesList = $myDeliveries ?? [];

        $totalDeliveries = count($myDeliveriesList);

        $inTransitCount = count($inTransit ?? []);

        $deliveredCount = count($delivered ?? []);

    @endphp


    <!-- =========================================================
         MAIN
    ========================================================= -->

    <main class="main-content">

        <div class="container">

            @include('partials.announcement-banner')

            {{-- PHONES: orange hero (greeting + today's job) and three tiles, instead of the welcome, shortcuts and stats below. --}}
            @php
                $greetHour = (int) now()->format('G');
                $toDeliverCount = count($myDeliveryAssignments ?? []);
                $pickupTotal = $pickupCounts['available'] + $pickupCounts['mine'];
                $riderUnreadNotifications = \App\Support\Inbox::unreadNotifications((int) $user['id']);
            @endphp
            <section class="rd-hero" aria-label="Today">
                <div class="rd-hero-top">
                    <span class="rd-hero-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user['name'] ?? 'R', 0, 1)) }}</span>
                    <span class="rd-hero-who">
                        <span class="rd-hero-greet" data-greet>{{ $greetHour >= 5 && $greetHour < 12 ? 'Good morning' : ($greetHour >= 12 && $greetHour < 18 ? 'Good afternoon' : 'Good evening') }}</span>
                        <strong>{{ $user['name'] ?? 'Rider' }}</strong>
                    </span>
                    <a href="{{ route('rider.notifications') }}" class="rd-hero-bell" aria-label="Notifications{{ $riderUnreadNotifications > 0 ? ', ' . $riderUnreadNotifications . ' unread' : '' }}">
                        <i class="bi bi-bell"></i>
                        @if($riderUnreadNotifications > 0)
                            <span>{{ $riderUnreadNotifications > 99 ? '99+' : $riderUnreadNotifications }}</span>
                        @endif
                    </a>
                </div>
                <div class="rd-hero-today">
                    <span class="rd-hero-today-icon" aria-hidden="true"><i class="bi bi-truck"></i></span>
                    @if($toDeliverCount > 0)
                        <span><strong>{{ $toDeliverCount }} {{ $toDeliverCount === 1 ? 'parcel' : 'parcels' }} to deliver</strong>{{ $inTransitCount > 0 ? $inTransitCount . ' out for delivery now' : 'Pick them up at the Sorting Center' }}</span>
                    @else
                        <span><strong>No deliveries right now</strong>Parcels from the Sorting Center show up here.</span>
                    @endif
                </div>
            </section>

            <div class="rd-tiles">
                <a href="{{ route('rider.deliveries') }}" class="rd-tile">
                    <span class="rd-tile-icon is-orange" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
                    <strong>{{ $toDeliverCount }}</strong>
                    <span>To deliver</span>
                </a>
                <a href="{{ route('rider.pickups') }}" class="rd-tile">
                    <span class="rd-tile-icon is-yellow" aria-hidden="true"><i class="bi bi-box-arrow-in-down"></i></span>
                    <strong>{{ $pickupTotal }}</strong>
                    <span>Pickups</span>
                </a>
                <a href="{{ route('rider.deliveries.history') }}" class="rd-tile">
                    <span class="rd-tile-icon is-teal" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                    <strong>{{ $deliveredCount }}</strong>
                    <span>Delivered</span>
                </a>
            </div>

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

                @php
                    // Riders a logistics company added show that company.
                    $riderCompany = \App\Services\RiderAccountService::companyName(
                        \Illuminate\Support\Facades\DB::table('rider_applications')->where('user_id', $user['id'] ?? 0)->latest('id')->value('created_by')
                    );
                @endphp
                @if ($riderCompany)
                    <p style="font-weight:700;"><i class="bi bi-building"></i> {{ $riderCompany }}</p>
                @endif

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
                        To Deliver
                    </div>

                    <div class="stat-number">
                        {{ count($myDeliveryAssignments ?? []) }}
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

            @if(isset($codHeld) && $codHeld->isNotEmpty())
                <div class="cod-remit rd-cod">
                    <i class="bi bi-cash-stack"></i>
                    <div>
                        <strong>Cash to hand in: ₱{{ number_format((float) $codHeld->sum('total_amount'), 2) }}</strong>
                        <span>From {{ $codHeld->count() }} Cash on Delivery order(s). Give it to your Sorting Center so the sellers get paid.</span>
                    </div>
                </div>
            @endif


            <!-- =====================================================
                 ITEMS FOR PICKUP (sellers' pickup requests)
            ===================================================== -->

            <div class="section-title">
                <div>
                    <h2><i class="bi bi-box-arrow-in-down"></i> Items for Pickup</h2>
                    <p>Sellers in your areas who booked a rider — collect the parcel and bring it to their Sorting Center.</p>
                </div>
            </div>

            <a href="{{ route('rider.pickups') }}" class="cod-remit rd-pickup" style="text-decoration:none;color:inherit;margin-bottom:24px">
                <i class="bi bi-box-arrow-in-down"></i>
                <div>
                    <strong>
                        {{ $pickupCounts['available'] }} new pickup request{{ $pickupCounts['available'] === 1 ? '' : 's' }} near you
                        @if($pickupCounts['mine'] > 0)
                            · {{ $pickupCounts['mine'] }} in progress
                        @endif
                    </strong>
                    <span>{{ $pickupCounts['available'] + $pickupCounts['mine'] > 0 ? 'Open Items for Pickup to accept or finish them.' : 'Nothing to pick up right now. You\'ll get a notification when a seller books one.' }}</span>
                </div>
            </a>


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

                    Your Sorting Center assigns you parcels for buyers in your area.
                    Pick them up at the center, mark them
                    <strong>Out for Delivery</strong>,
                    then take a photo of the parcel with the buyer when you hand it over.

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

    <script>
        // Phones: the greeting follows the phone's own clock and changes on its own.
        (function () {
            var greet = document.querySelector('[data-greet]');
            if (!greet) return;

            function setGreeting() {
                var h = new Date().getHours();
                greet.textContent = h >= 5 && h < 12 ? 'Good morning' : (h >= 12 && h < 18 ? 'Good afternoon' : 'Good evening');
            }

            setGreeting();
            setInterval(setGreeting, 60000);
        })();
    </script>

</body>

</html>