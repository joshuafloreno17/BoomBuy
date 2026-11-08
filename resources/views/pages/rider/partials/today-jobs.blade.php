{{--
    Rider "Today": the cash the rider holds, then every job in one list —
    deliveries, pickups they accepted, and new pickup offers nearby.
    Each card has one main button. The pickup card keeps the scan form
    (form.scan-form input[name=tracking_number]) so the app adds its camera button.
--}}
@php
    $tjCash = collect($codHeld ?? [])->sum('total_amount');
    $tjDeliveries = collect($myDeliveryAssignments ?? [])->sortBy(fn ($o) => ($o['status'] ?? '') === 'Out for Delivery' ? 0 : 1)->values();
    $tjMine = collect($pickupsMine ?? []);
    $tjOffers = collect($pickupsAvailable ?? []);
    $tjTodo = $tjDeliveries->count() + $tjMine->count();
    $tjDone = collect($delivered ?? [])->filter(fn ($o) => !empty($o['delivered_at'] ?? null) && \Illuminate\Support\Carbon::parse($o['delivered_at'])->isToday())->count();
@endphp

<link rel="stylesheet" href="{{ vasset('css/partials/rider-today.css') }}">

<section class="tj" aria-label="Today's jobs">

    <div class="tj-cash {{ $tjCash > 0 ? 'has-cash' : '' }}">
        <div>
            <small>Cash you're holding</small>
            <strong>₱{{ number_format($tjCash, 2) }}</strong>
        </div>
        @if($tjCash > 0)
            <a href="{{ route('rider.profit') }}#remitAmount" class="tj-cash-btn">Remit</a>
        @else
            <span class="tj-cash-none">Nothing to remit</span>
        @endif
    </div>

    <div class="tj-head">
        <h2>Today's jobs</h2>
        <span>{{ $tjTodo }} to do · {{ $tjDone }} done today</span>
    </div>

    @forelse($tjDeliveries as $job)
        @php $tjOut = ($job['status'] ?? '') === 'Out for Delivery'; $tjIsCod = \App\Support\CodPolicy::isCod($job['payment_method'] ?? ''); @endphp
        <article @class(['tj-card', 'is-now' => $tjOut])>
            <div class="tj-tags">
                <span class="tj-tag is-deliver">Deliver</span>
                <span class="tj-wb">{{ \App\Support\Waybill::number((int) $job['id']) }}</span>
                <span class="tj-state">{{ $tjOut ? 'Out for delivery' : 'Get it at the center' }}</span>
            </div>
            <div class="tj-who">
                <strong>{{ $job['buyer_name'] ?? 'Buyer' }}</strong>
                <span>{{ $job['address'] ?? '' }}</span>
            </div>
            <div class="tj-money">
                @if($tjIsCod)
                    <span class="tj-collect">Collect ₱{{ number_format((float) ($job['total'] ?? 0), 2) }}</span>
                @else
                    <span class="tj-paid">Paid online</span>
                @endif
                @if(!empty($job['delivery_otp']))
                    <span>· code needed</span>
                @endif
            </div>
            <div class="tj-actions">
                @if(!empty($job['phone']))
                    <a href="tel:{{ $job['phone'] }}" class="tj-btn is-ghost"><i class="bi bi-telephone-fill"></i> Call</a>
                @endif
                <a href="{{ route('rider.delivery.details', $job['id']) }}" class="tj-btn is-main">
                    {{ $tjOut ? 'Hand over' : 'Start delivery' }}
                </a>
            </div>
        </article>
    @empty
    @endforelse

    @foreach($tjMine as $order)
        <article @class(['tj-card', 'is-pickup', 'is-now' => $order->status === 'Pickup Assigned'])>
            <div class="tj-tags">
                <span class="tj-tag is-pickup">Pick up</span>
                <span class="tj-wb">{{ \App\Support\Waybill::number((int) $order->id) }}</span>
                <span class="tj-state">{{ $order->status === 'Pickup Assigned' ? ($order->pickup_day ?? 'Today') : 'Picked up' }}</span>
            </div>
            <div class="tj-who">
                <strong>{{ $order->shop_name }}</strong>
                <span>{{ $order->pickup_address ?: 'Ask the seller for the address' }}</span>
            </div>
            <div class="tj-money"><span>Bring it to <b>{{ $order->center_name ?? 'the Sorting Center' }}</b></span></div>

            @if($order->status === 'Pickup Assigned')
                <form method="POST" action="{{ route('rider.pickup.scan', $order->id) }}" class="scan-form tj-scan">
                    @csrf
                    <input type="text" name="tracking_number" placeholder="Scan or type {{ \App\Support\Waybill::number((int) $order->id) }}" autocomplete="off" required aria-label="Tracking number for order #{{ $order->id }}">
                    <button type="submit" class="tj-btn is-main is-green"><i class="bi bi-upc-scan"></i> At the seller — confirm</button>
                </form>
                <div class="tj-actions">
                    @if($order->seller_phone)
                        <a href="tel:{{ $order->seller_phone }}" class="tj-btn is-ghost"><i class="bi bi-telephone-fill"></i> Call seller</a>
                    @endif
                    @if($order->pickup_address)
                        <a href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($order->pickup_address) }}" target="_blank" rel="noopener" class="tj-btn is-ghost"><i class="bi bi-map-fill"></i> Map</a>
                    @endif
                </div>
            @else
                <p class="tj-note"><i class="bi bi-signpost-2-fill"></i> Bring it to {{ $order->center_name ?? 'the Sorting Center' }}. Staff there confirm they received it.</p>
            @endif
        </article>
    @endforeach

    @foreach($tjOffers as $order)
        <article class="tj-card is-offer">
            <div class="tj-offer-text">
                <small>New pickup nearby · {{ $order->pickup_day ?? 'Any day' }}</small>
                <strong>{{ $order->shop_name }}</strong>
                <span>{{ $order->items->map(fn ($i) => $i->quantity . '× ' . $i->product_name)->implode(', ') }}</span>
            </div>
            <form method="POST" action="{{ route('rider.pickup.accept', $order->id) }}">
                @csrf
                <button type="submit" class="tj-btn is-main">Accept</button>
            </form>
        </article>
    @endforeach

    @if($tjTodo === 0 && $tjOffers->isEmpty())
        <div class="tj-empty">
            <strong>No jobs right now</strong>
            <span>Stay Online — new pickups and deliveries show up here.</span>
        </div>
    @endif
</section>
