{{--
    Where a parcel is on its trip, as a bar: seller → (seller's branch) →
    seller's Sorting Center → (buyer's Sorting Center) → (buyer's branch) → buyer.
    Green = passed, ring = where it is now, dashed = on the way, grey = ahead.
    The stop that is this staff member's center says "(you)".
    Uses $centerNames (and $myCenter) from the page.
--}}
@php
    $cn = fn ($id) => $id ? ($centerNames[$id] ?? null) : null;

    $routeStops = [['id' => null, 'name' => 'Seller']];
    foreach ([$order->origin_branch_id ?? null, $order->origin_center_id ?? null, $order->destination_center_id ?? null, $order->branch_id ?? null] as $stopId) {
        if ($stopId && !collect($routeStops)->contains('id', (int) $stopId) && $cn($stopId)) {
            $routeStops[] = ['id' => (int) $stopId, 'name' => $cn($stopId)];
        }
    }
    $routeStops[] = ['id' => null, 'name' => 'Buyer'];

    $idx = function ($id) use ($routeStops) {
        foreach ($routeStops as $i => $s) {
            if ($id && $s['id'] === (int) $id) return $i;
        }
        return null;
    };
    $last = count($routeStops) - 1;
    $lastCenter = $last - 1;
    $st = $order->status ?? '';

    // Where it is now ($at) and where it is heading ($coming), from its status.
    [$at, $coming, $bad] = match (true) {
        in_array($st, ['Pending', 'Confirmed', 'Preparing', 'Ready for Pickup', 'Pickup Assigned'], true) => [0, null, false],
        in_array($st, ['Picked Up', 'Dropped Off'], true) => [0, 1, false],
        $st === 'To Sorting Center' => [$idx($order->origin_branch_id ?? null) ?? 0, $idx($order->origin_center_id ?? null), false],
        $st === 'In Transit' => [$idx($order->origin_center_id ?? null) ?? 1, $idx($order->destination_center_id ?? null), false],
        $st === 'To Branch' => [$idx($order->destination_center_id ?? $order->origin_center_id ?? null) ?? 1, $idx($order->branch_id ?? null), false],
        in_array($st, ['Assigned for Delivery', 'Out for Delivery'], true) => [$idx($order->current_center_id ?? null) ?? $lastCenter, $last, false],
        $st === 'Delivery Failed' => [$last, null, true],
        in_array($st, ['Delivered', 'Completed'], true) => [$last, null, false],
        default => [$idx($order->current_center_id ?? null) ?? $idx($order->origin_center_id ?? null) ?? 1, null, false],
    };
    $myId = isset($myCenter) && $myCenter ? (int) $myCenter->id : null;
@endphp

@if (count($routeStops) > 2)
    <div class="parcel-route">
        <span class="parcel-waybill"><i class="bi bi-upc-scan"></i> {{ \App\Support\Waybill::number((int) $order->id) }}</span>

        <ol class="route-bar" aria-label="Route of order #{{ $order->id }}">
            @foreach ($routeStops as $i => $stop)
                @php
                    $moving = $coming !== null;
                    $state = ($i < $at || ($moving && $i === $at)) ? 'is-passed'
                        : ($i === $at ? ($bad ? 'is-bad' : 'is-here') : ($i === $coming ? 'is-coming' : ''));
                    $isMe = $myId && $stop['id'] === $myId;
                @endphp
                <li class="{{ $state }} {{ $isMe ? 'is-me' : '' }}" title="{{ $stop['name'] }}">
                    <span class="route-dot" aria-hidden="true"></span>
                    <span class="route-name">{{ $stop['name'] }}{{ $isMe ? ' (you)' : '' }}</span>
                </li>
            @endforeach
        </ol>
    </div>
@endif
