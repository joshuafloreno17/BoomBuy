{{-- One pickup request: where to collect it, what's in it, and the next step. --}}
<div class="delivery-card" data-status="{{ $order->status }}">

    <div class="delivery-header">
        <div class="order-id">
            <i class="bi bi-box-seam-fill"></i>
            Order #{{ $order->id }} · {{ \App\Support\Waybill::number((int) $order->id) }}
        </div>
        <x-status-pill :status="$order->status" />
    </div>

    <div class="info">
        <div class="info-row">
            <span class="info-label">Pickup day</span>
            <span class="info-value pickup-day">{{ $order->pickup_day ?? 'Any day' }}</span>
        </div>

        <div class="info-row">
            <span class="info-label">Seller</span>
            <span class="info-value">
                {{ $order->shop_name }}
                @if($mineCard && $order->seller_phone)
                    · <a href="tel:{{ $order->seller_phone }}">{{ $order->seller_phone }}</a>
                @endif
            </span>
        </div>

        <div class="info-row">
            <span class="info-label">Pick up at</span>
            <span class="info-value">{{ $order->pickup_address ?: 'Ask the seller for the address' }}</span>
        </div>

        <div class="info-row">
            <span class="info-label">Bring to</span>
            <span class="info-value">{{ $order->center_name ?? 'The Sorting Center' }}</span>
        </div>

        <div class="info-row">
            <span class="info-label">Items</span>
            <span class="info-value">
                {{ $order->items->map(fn ($i) => $i->quantity . '× ' . $i->product_name)->implode(', ') }}
            </span>
        </div>
    </div>

    <div class="buttons">
        @if(!$mineCard)
            <form method="POST" action="{{ route('rider.pickup.accept', $order->id) }}">
                @csrf
                <button type="submit" class="btn status-btn"><i class="bi bi-hand-thumbs-up-fill"></i> Accept pickup</button>
            </form>
        @elseif($order->status === 'Pickup Assigned')
            @if($order->pickup_address)
                <a class="btn view-btn" href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($order->pickup_address) }}" target="_blank" rel="noopener">
                    <i class="bi bi-map-fill"></i> Map
                </a>
            @endif
            <form method="POST" action="{{ route('rider.pickup.picked-up', $order->id) }}" data-confirm="Do you have order #{{ $order->id }} with you? The seller and buyer will be told it's on its way." data-confirm-ok="Yes, picked up">
                @csrf
                <button type="submit" class="btn status-btn"><i class="bi bi-check2-circle"></i> Picked up</button>
            </form>
        @else
            <p style="margin:0;color:var(--muted,#6b6570);font-size:14px">
                <i class="bi bi-signpost-2-fill"></i> Bring it to {{ $order->center_name ?? 'the Sorting Center' }}. Staff there will confirm they received it.
            </p>
        @endif
    </div>

</div>
