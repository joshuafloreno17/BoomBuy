<!DOCTYPE html>
<html lang="en">

<head>

    @include('partials.head', ['title' => 'Items for Pickup — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/rider-deliveries.css') }}">

    <style>
        .pickup-section { margin-top: 26px; }
        .pickup-section > h2 { display: flex; align-items: center; gap: 8px; margin: 0 0 4px; font-size: 19px; }
        .pickup-section > h2 .count { padding: 1px 9px; border-radius: 999px; background: var(--accent-soft, #fff1ea); color: var(--accent-dark, #c2380f); font-size: 13px; }
        .pickup-section > p { margin: 0 0 14px; color: var(--muted, #6b6570); font-size: 14px; }
        .pickup-day { font-weight: 800; color: var(--accent-dark, #c2380f); }
        .pickup-empty { padding: 22px; border: 1px dashed var(--line, #f0e2da); border-radius: 16px; background: #fff; color: var(--muted, #6b6570); text-align: center; }
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
