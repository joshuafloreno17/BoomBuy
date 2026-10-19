{{-- Online/Offline and the job offers waiting for this rider (Rider Modules R2/R3). --}}
@php
    $shiftRiderId = (int) ($user['id'] ?? 0);
    $shiftOnline = \App\Support\RiderJobs::isOnline($shiftRiderId);
    $shiftOffers = app(\App\Services\DispatchService::class)->offersFor($shiftRiderId);
    $shiftActive = \App\Support\RiderJobs::activeCount($shiftRiderId);
@endphp
<section class="shift-bar {{ $shiftOnline ? 'is-online' : 'is-offline' }}" aria-label="Your shift">
    <div class="shift-state">
        <span class="shift-dot" aria-hidden="true"></span>
        <strong>{{ $shiftOnline ? 'Online' : 'Offline' }}</strong>
        <small>{{ $shiftActive }} of {{ \App\Support\RiderJobs::ACTIVE_LIMIT }} active jobs</small>
    </div>
    <form method="POST" action="{{ route('rider.availability') }}">
        @csrf
        <input type="hidden" name="online" value="{{ $shiftOnline ? 0 : 1 }}">
        <button type="submit" class="btn {{ $shiftOnline ? 'view-btn' : 'status-btn' }}">
            <i class="bi {{ $shiftOnline ? 'bi-pause-circle' : 'bi-play-circle-fill' }}"></i> {{ $shiftOnline ? 'Go Offline' : 'Go Online' }}
        </button>
    </form>
</section>

@foreach($shiftOffers as $offer)
    <article class="delivery-card job-offer" data-offer="{{ $offer->id }}" data-expires="{{ \Illuminate\Support\Carbon::parse($offer->expires_at)->toIso8601String() }}">
        <div class="delivery-header">
            <div class="order-id"><i class="bi bi-lightning-charge-fill"></i> New job · {{ \App\Support\Waybill::number((int) $offer->order_id) }}</div>
            <span class="offer-timer" data-offer-timer>{{ $offer->seconds_left }}s</span>
        </div>
        <div class="info">
            <div class="info-row"><span class="info-label">Pick up</span><span class="info-value">{{ $offer->shop_name }}{{ $offer->pickup_address ? ' · ' . $offer->pickup_address : '' }}</span></div>
            <div class="info-row"><span class="info-label">Drop-off area</span><span class="info-value">{{ $offer->dropoff_area ?: '—' }}</span></div>
            <div class="info-row"><span class="info-label">COD</span><span class="info-value">₱{{ number_format($offer->cod, 2) }}</span></div>
            <div class="info-row"><span class="info-label">Your pay</span><span class="info-value">₱{{ number_format($offer->pay, 2) }} pickup</span></div>
        </div>
        <div class="buttons">
            <form method="POST" action="{{ route('rider.offers.respond', $offer->id) }}">
                @csrf
                <input type="hidden" name="answer" value="decline">
                <button type="submit" class="btn view-btn"><i class="bi bi-x-lg"></i> Decline</button>
            </form>
            <form method="POST" action="{{ route('rider.offers.respond', $offer->id) }}">
                @csrf
                <input type="hidden" name="answer" value="accept">
                <button type="submit" class="btn status-btn" data-offer-accept><i class="bi bi-check-lg"></i> Accept</button>
            </form>
        </div>
    </article>
@endforeach

@once
    <style>
        .shift-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; margin: 0 0 16px; border-radius: 14px; background: #fff; border: 1px solid #e9e4fa; }
        .shift-bar form { margin: 0; }
        .shift-state { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .shift-state small { color: #645a80; }
        .shift-dot { width: 10px; height: 10px; border-radius: 50%; background: #b3aac8; }
        .shift-bar.is-online .shift-dot { background: #0d9488; box-shadow: 0 0 0 4px #e3f6f4; }
        .job-offer { border: 2px solid #6d4ad1; }
        .offer-timer { font-weight: 800; color: #6d4ad1; }
        .scan-form { display: flex; gap: 8px; flex-wrap: wrap; width: 100%; }
        .scan-form input { flex: 1; min-width: 0; padding: 10px 12px; border: 1px solid #e9e4fa; border-radius: 10px; font: inherit; }
    </style>
    <script>
        // Count each offer down; at 0 Accept is disabled (the server refuses it too).
        setInterval(function () {
            document.querySelectorAll('[data-offer]').forEach(function (card) {
                var left = Math.max(0, Math.round((new Date(card.dataset.expires) - new Date()) / 1000));
                card.querySelector('[data-offer-timer]').textContent = left + 's';
                if (left === 0) { card.querySelector('[data-offer-accept]').disabled = true; card.style.opacity = .55; }
            });
        }, 1000);
    </script>
@endonce
