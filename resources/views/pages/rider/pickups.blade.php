<!DOCTYPE html>
<html lang="en">

<head>

    @include('partials.head', ['title' => 'Items for Pickup — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/rider-deliveries.css') }}">

    <style>
        .pickup-section { margin-top: 26px; }
        .pickup-section > h2 { display: flex; align-items: center; gap: 8px; margin: 0 0 4px; font-size: 19px; }
        .pickup-section > h2 .count { padding: 1px 9px; border-radius: 999px; background: var(--accent-soft, var(--theme-soft)); color: var(--accent-dark, var(--theme-primary-hover)); font-size: 13px; }
        .pickup-section > p { margin: 0 0 14px; color: var(--muted, var(--theme-muted)); font-size: 14px; }
        .pickup-day { font-weight: 800; color: var(--accent-dark, var(--theme-primary-hover)); }
        .pickup-empty { padding: 22px; border: 1px dashed var(--line, var(--theme-border)); border-radius: 16px; background: #fff; color: var(--muted, var(--theme-muted)); text-align: center; }
        .delivery-card form { flex: 1; margin: 0; }
        .delivery-card form .btn { width: 100%; border: 0; cursor: pointer; font: inherit; }
    </style>

</head>


<body>

    <x-layout.rider-sidebar active="pickups" :user="$user" />

    <main class="main main-content">

        <div class="content-wrapper">

            <div class="topbar">
                <div>
                    <h1>Items for Pickup</h1>
                    <p class="subtitle">Sellers in your areas who booked a rider. Collect the parcel, then bring it to their Sorting Center.</p>
                </div>

                <div class="profile">
                    <i class="bi bi-bicycle"></i>
                    <strong>{{ $user['name'] ?? 'Rider' }}</strong>
                </div>
            </div>

            @if(session('success'))
                <div class="alert success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert error"><i class="bi bi-x-circle-fill"></i> {{ session('error') }}</div>
            @endif

            @include('pages.rider.partials.shift-bar')

            {{-- ===== MY PICKUPS ===== --}}
            <section class="pickup-section">
                <h2><i class="bi bi-bag-check-fill"></i> My pickups <span class="count">{{ $mine->count() }}</span></h2>
                <p>Pickups you accepted. Tap <strong>Picked up</strong> once the parcel is with you.</p>

                @if($mine->isEmpty())
                    <div class="pickup-empty">You have no pickups in progress.</div>
                @else
                    <div class="deliveries">
                        @foreach($mine as $order)
                            @include('pages.rider.partials.pickup-card', ['order' => $order, 'mineCard' => true])
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- ===== BUYER RETURNS TO COLLECT ===== --}}
            @if($returnPickups->isNotEmpty())
            <section class="pickup-section">
                <h2><i class="bi bi-arrow-return-left"></i> Returns to collect <span class="count">{{ $returnPickups->count() }}</span></h2>
                <p>Your Sorting Center gave you these. Collect the item from the buyer, take a photo, then bring it to the center.</p>

                <div class="deliveries">
                    @foreach($returnPickups as $rp)
                        @php $rpAddress = $rp->shipping_address ?: trim(($rp->shipping_city ?? '') . ', ' . ($rp->shipping_province ?? ''), ', '); @endphp
                        <div class="delivery-card" data-status="{{ $rp->status }}">
                            <div class="delivery-header">
                                <div class="order-id"><i class="bi bi-arrow-return-left"></i> Return #{{ $rp->id }} · Order #{{ $rp->order_id }}</div>
                                <span class="status-badge">{{ $rp->status === 'picked_up' ? 'With you' : 'To pick up' }}</span>
                            </div>

                            <div class="info">
                                <div class="info-row">
                                    <span class="info-label">Buyer</span>
                                    <span class="info-value">{{ $rp->shipping_name }}@if($rp->shipping_phone) · <a href="tel:{{ $rp->shipping_phone }}">{{ $rp->shipping_phone }}</a>@endif</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Pick up at</span>
                                    <span class="info-value">{{ $rpAddress ?: 'Ask the buyer for the address' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Bring to</span>
                                    <span class="info-value">{{ $rp->center_name ?? 'Your Sorting Center' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Item</span>
                                    <span class="info-value">{{ ($rp->quantity ?? 1) . '× ' . ($rp->product_name ?? 'Item') }} · {{ $rp->reason }}</span>
                                </div>
                            </div>

                            <div class="buttons">
                                @if($rp->status === 'approved')
                                    @if($rpAddress)
                                        <a class="btn view-btn" href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($rpAddress) }}" target="_blank" rel="noopener"><i class="bi bi-map-fill"></i> Map</a>
                                    @endif
                                    <form method="POST" action="{{ route('rider.return-pickup.picked-up', $rp->id) }}" enctype="multipart/form-data" class="scan-form">
                                        @csrf
                                        <input type="file" name="photo" accept="image/*" capture="environment" required aria-label="Photo of the item">
                                        <button type="submit" class="btn status-btn"><i class="bi bi-camera-fill"></i> Picked up</button>
                                    </form>
                                @else
                                    <p style="margin:0;color:var(--muted,var(--theme-muted));font-size:14px">
                                        <i class="bi bi-signpost-2-fill"></i> Bring it to {{ $rp->center_name ?? 'the Sorting Center' }}. Staff there will tap "Received".
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- ===== AVAILABLE ===== --}}
            <section class="pickup-section">
                <h2><i class="bi bi-box-arrow-in-down"></i> Pickup requests near you <span class="count">{{ $available->count() }}</span></h2>
                <p>First come, first served — accept one and it's yours.</p>

                @if(!$hasAreas)
                    <div class="pickup-empty">You don't have a delivery area yet. Your Sorting Center assigns your areas; pickup requests from sellers there will show up here.</div>
                @elseif($available->isEmpty())
                    <div class="pickup-empty">No pickup requests in your areas right now. You'll get a notification when a seller books one.</div>
                @else
                    <div class="deliveries">
                        @foreach($available as $order)
                            @include('pages.rider.partials.pickup-card', ['order' => $order, 'mineCard' => false])
                        @endforeach
                    </div>
                @endif
            </section>

        </div>

    </main>

    @include('partials.pwa-register')

</body>

</html>
