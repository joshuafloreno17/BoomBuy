<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Incoming Parcels — BoomBuy Logistics</title>

    @include('partials.pwa-head')

    <link rel="stylesheet" href="{{ asset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/logistics-parcels.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="parcels" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header">
                <h1>Incoming Parcels</h1>
                <p>Confirm parcels picked up by riders, then assign them for final-mile delivery.</p>
            </div>

            @if (session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            <h2 class="section-heading">📥 Awaiting Confirmation ({{ count($awaitingConfirmation) }})</h2>

            @forelse ($awaitingConfirmation as $order)

                <div class="app-card">

                    <div class="app-card-top">
                        <div>
                            <div class="app-name">Order #{{ $order->id }}</div>
                            <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                        </div>
                        <span class="status-badge status-pending-verification">Picked Up</span>
                    </div>

                    <div class="app-actions">
                        <form method="POST" action="{{ route('logistics.parcels.confirm-received', $order->id) }}">
                            @csrf
                            <button type="submit" class="approve-btn">✓ Confirm Parcel Received</button>
                        </form>
                    </div>

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon">📦</div>
                    <h3>No Parcels In Transit</h3>
                    <p>Parcels picked up by riders from sellers will appear here.</p>
                </div>

            @endforelse

            <h2 class="section-heading">🗂️ Awaiting Assignment ({{ count($awaitingAssignment) }})</h2>

            @forelse ($awaitingAssignment as $order)

                @php
                    $suggested = $suggestedRidersByOrder[$order->id] ?? collect();
                @endphp

                <div class="app-card">

                    <div class="app-card-top">
                        <div>
                            <div class="app-name">Order #{{ $order->id }}</div>
                            <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                        </div>
                        <span class="status-badge status-approved">At Sorting Center</span>
                    </div>

                    <div class="app-actions">
                        <form class="assign-form" method="POST" action="{{ route('logistics.parcels.assign', $order->id) }}">
                            @csrf
                            <select name="rider_id" required>
                                <option value="">Select Rider</option>
                                @foreach ($activeRiders as $rider)
                                    <option value="{{ $rider->id }}">
                                        {{ $rider->name }}
                                        {{ $suggested->contains('id', $rider->id) ? '(area match)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="approve-btn">Assign for Delivery</button>
                        </form>
                    </div>

                    @if($suggested->isEmpty())
                        <div class="remarks-note" style="margin-top:10px;">
                            No rider has a matching delivery area for this address yet — pick any available rider below.
                        </div>
                    @endif

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon">🗂️</div>
                    <h3>No Parcels Awaiting Assignment</h3>
                    <p>Confirmed parcels ready for rider assignment will appear here.</p>
                </div>

            @endforelse

            <h2 class="section-heading">⚠️ Failed Deliveries ({{ count($failedDeliveries) }})</h2>

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

                    <div class="app-actions">

                        @if($order->delivery_attempts < 2)

                            <form class="assign-form" method="POST" action="{{ route('logistics.parcels.reschedule', $order->id) }}">
                                @csrf
                                <select name="rider_id" required>
                                    <option value="">Select Rider</option>
                                    @foreach ($activeRiders as $rider)
                                        <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="approve-btn">↻ Reschedule</button>
                            </form>

                        @endif

                        <form method="POST" action="{{ route('logistics.parcels.return-to-seller', $order->id) }}" onsubmit="return confirm('Return this parcel to the seller? This cannot be undone.');">
                            @csrf
                            <button type="submit" class="reject-btn">↩ Return to Seller</button>
                        </form>

                    </div>

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon">⚠️</div>
                    <h3>No Failed Deliveries</h3>
                    <p>Parcels that couldn't be delivered will appear here for rescheduling or return.</p>
                </div>

            @endforelse

        </div>

    </main>

    @include('partials.pwa-register')

</body>
</html>
