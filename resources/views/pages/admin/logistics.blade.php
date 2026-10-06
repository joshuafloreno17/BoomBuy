<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Logistics Overview — BoomBuy Admin'])

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-logistics.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/logistics-riders.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/logistics-parcels.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="logistics" />

    <main class="main">

    <div class="container">

        <div class="header">
            <small>Admin Panel</small>
            <h1>Logistics Overview</h1>
            <p>A read-only view of rider vetting and parcel operations. Riders and parcels are managed by the Logistics Center.</p>
        </div>

        <div class="readonly-note">
            <i class="bi bi-eye-fill"></i> This is a read-only view — approving riders, assigning parcels, and rescheduling deliveries are handled by Logistics staff.
        </div>

        <div class="stats">

            <div class="stat-card">
                <span>Active Riders</span>
                <strong>{{ $activeRiders }}</strong>
            </div>

            <div class="stat-card">
                <span>Parcels In Transit / Sorting</span>
                <strong>{{ count($awaitingConfirmation) + count($awaitingAssignment) }}</strong>
            </div>

            <div class="stat-card">
                <span>Delivered Today</span>
                <strong>{{ $deliveredToday }}</strong>
            </div>

        </div>

        {{-- =========================
             RIDER APPLICATIONS
        ========================== --}}

        <h2 class="section-heading"><i class="bi bi-bicycle"></i> Rider Applications ({{ count($riderApplications) }})</h2>

        @forelse ($riderApplications as $app)

            @php
                $statusClass = 'status-' . strtolower(str_replace(' ', '-', $app->status));
                $accountStatusClass = 'status-' . strtolower($app->account_status ?? 'active');
            @endphp

            <div class="app-card">

                <div class="app-card-top">
                    <div>
                        <div class="app-name">{{ $app->full_name }}</div>
                        <div class="app-email">{{ $app->user_email }}</div>
                    </div>
                    <div class="badge-group">
                        <span class="status-badge {{ $statusClass }}">{{ $app->status }}</span>
                        @if($app->status === 'Approved')
                            <span class="status-badge {{ $accountStatusClass }}">{{ $app->account_status ?? 'Active' }}</span>
                        @endif
                    </div>
                </div>

                <div class="app-details">
                    <div>
                        <strong>Phone</strong>
                        {{ $app->phone }}
                    </div>
                    <div>
                        <strong>Vehicle</strong>
                        {{ $app->vehicle_type }} — {{ $app->vehicle_model }}
                    </div>
                    <div>
                        <strong>Applied</strong>
                        {{ \Illuminate\Support\Carbon::parse($app->created_at)->format('M d, Y') }}
                    </div>
                </div>

                @if ($app->status === 'Rejected' && $app->admin_remarks)
                    <div class="remarks-note">
                        Rejection reason: {{ $app->admin_remarks }}
                    </div>
                @endif

                @if ($app->status === 'Approved')

                    <div class="areas-section">

                        <div class="areas-label">Delivery Areas</div>

                        <div class="area-chips">
                            @forelse (($riderAreas[$app->user_id] ?? []) as $area)
                                <span class="area-chip">
                                    {{ $area->city_municipality }}, {{ $area->province }}
                                </span>
                            @empty
                                <span class="no-areas">No areas assigned yet.</span>
                            @endforelse
                        </div>

                    </div>

                @endif

            </div>

        @empty

            <div class="empty">
                <div class="empty-icon"><i class="bi bi-bicycle"></i></div>
                <h3>No Rider Applications</h3>
                <p>Rider applications will appear here once submitted.</p>
            </div>

        @endforelse

        {{-- =========================
             AWAITING CONFIRMATION
        ========================== --}}

        <h2 class="section-heading"><i class="bi bi-envelope-paper-fill"></i> Awaiting Sorting Center Confirmation ({{ count($awaitingConfirmation) }})</h2>

        @forelse ($awaitingConfirmation as $order)

            <div class="app-card">
                <div class="app-card-top">
                    <div>
                        <div class="app-name">Order #{{ $order->id }}</div>
                        <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                    </div>
                    <x-status-pill :status="$order->status" />
                </div>
            </div>

        @empty

            <div class="empty">
                <div class="empty-icon"><i class="bi bi-box-seam-fill"></i></div>
                <h3>No Parcels In Transit</h3>
                <p>Parcels picked up by riders from sellers will appear here.</p>
            </div>

        @endforelse

        {{-- =========================
             AWAITING ASSIGNMENT
        ========================== --}}

        <h2 class="section-heading"><i class="bi bi-inbox-fill"></i> Awaiting Rider Assignment ({{ count($awaitingAssignment) }})</h2>

        @forelse ($awaitingAssignment as $order)

            <div class="app-card">
                <div class="app-card-top">
                    <div>
                        <div class="app-name">Order #{{ $order->id }}</div>
                        <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                    </div>
                    <span class="status-badge status-approved">At Sorting Center</span>
                </div>
            </div>

        @empty

            <div class="empty">
                <div class="empty-icon"><i class="bi bi-inbox-fill"></i></div>
                <h3>No Parcels Awaiting Assignment</h3>
                <p>Confirmed parcels ready for rider assignment will appear here.</p>
            </div>

        @endforelse

        {{-- =========================
             FAILED DELIVERIES
        ========================== --}}

        <h2 class="section-heading"><i class="bi bi-exclamation-triangle-fill"></i> Failed Deliveries ({{ count($failedDeliveries) }})</h2>

        @forelse ($failedDeliveries as $order)

            <div class="app-card">
                <div class="app-card-top">
                    <div>
                        <div class="app-name">Order #{{ $order->id }}</div>
                        <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                    </div>
                    <span class="status-badge status-rejected">Delivery Failed ({{ $order->delivery_attempts }}/2 attempts)</span>
                </div>
                <div class="remarks-note">
                    Reason: {{ $order->failure_reason ?? 'No reason provided.' }}
                </div>
            </div>

        @empty

            <div class="empty">
                <div class="empty-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <h3>No Failed Deliveries</h3>
                <p>Parcels that couldn't be delivered will appear here.</p>
            </div>

        @endforelse

        {{-- =========================
             RETURNED TO SELLER
        ========================== --}}

        <h2 class="section-heading"><i class="bi bi-arrow-return-left"></i> Returned to Seller ({{ count($returnedToSeller) }})</h2>

        @forelse ($returnedToSeller as $order)

            <div class="app-card">
                <div class="app-card-top">
                    <div>
                        <div class="app-name">Order #{{ $order->id }}</div>
                        <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                    </div>
                    <span class="status-badge {{ !empty($order->restocked_at) ? 'status-active' : 'status-deactivated' }}">
                        {{ !empty($order->restocked_at) ? 'Restocked' : 'Awaiting Restock' }}
                    </span>
                </div>
            </div>

        @empty

            <div class="empty">
                <div class="empty-icon"><i class="bi bi-arrow-return-left"></i></div>
                <h3>No Returned Parcels</h3>
                <p>Orders returned to sellers after failed delivery attempts will appear here.</p>
            </div>

        @endforelse

    </div>

    </main>

</div>

@include('partials.pwa-register')

</body>
</html>
