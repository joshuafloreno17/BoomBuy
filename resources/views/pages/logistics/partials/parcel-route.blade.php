{{-- Where a parcel goes: seller's town center → buyer's town center. Uses $centerNames from the page. --}}
@php
    $from = $centerNames[$order->origin_center_id] ?? null;
    $to = $centerNames[$order->destination_center_id] ?? null;
@endphp

@if ($from || $to)
    <div class="parcel-route">
        <span class="parcel-waybill"><i class="bi bi-upc-scan"></i> {{ \App\Support\Waybill::number((int) $order->id) }}</span>
        <span><i class="bi bi-shop"></i> {{ $from ?? 'Unknown center' }}</span>
        @if ($from !== $to)
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
            <span><i class="bi bi-house-door"></i> {{ $to ?? 'No center near the buyer — deliver from here' }}</span>
        @else
            <em>same town — deliver from here</em>
        @endif
    </div>
@endif
