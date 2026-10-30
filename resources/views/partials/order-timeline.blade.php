{{--
    An order's trip: what happened, where, and when.
    @include('partials.order-timeline', ['steps' => $timelines[$orderId] ?? collect()])

    With a 'plan' (OrderTimeline::plan) the whole route shows, oldest stop
    first: stops reached light up, the ones ahead stay grey. Without one
    (or for a cancelled / returning order) only the logged steps show, newest first.
--}}
@once
<link rel="stylesheet" href="{{ vasset('css/partials/order-timeline.css') }}">
@endonce

@php $steps = collect($steps ?? [])->reverse()->values(); @endphp

@if(!empty($proof))
    @include('partials.viewer-modal')
    <a href="{{ $proof }}" class="order-proof-link" data-viewer
       data-viewer-badge="Delivered" data-viewer-title="Proof of delivery"
       data-viewer-text="Photo taken by the rider when the parcel was handed over.">
        <i class="bi bi-camera-fill"></i> View the delivery photo
    </a>
@endif

@if(!empty($plan))
    <ol class="order-timeline is-route" aria-label="Order route">
        @php $nextRank = optional($plan->firstWhere('done', false))->rank; @endphp
        @foreach($plan as $stop)
            <li @class([
                'is-reached' => $stop->done,
                'is-ahead' => !$stop->done,
                'is-latest' => $stop->current,
                'is-done' => $stop->current && $stop->rank === 11,
            ])>
                <span class="order-timeline-dot" aria-hidden="true"></span>
                <div class="order-timeline-body">
                    @if(in_array($stop->rank, $mapStops ?? [], true))
                        {{-- Track package: shows this Sorting Center on the map. --}}
                        <button type="button" class="order-timeline-maplink" data-map-stop="{{ $stop->rank }}">
                            <strong>{{ $stop->title }}</strong>
                            <i class="bi bi-geo-alt" aria-hidden="true"></i>
                        </button>
                    @else
                        <strong>{{ $stop->title }}</strong>
                    @endif
                    @if($stop->detail)
                        <span>{{ $stop->detail }}</span>
                    @endif
                    @if($stop->at)
                        <time datetime="{{ \Illuminate\Support\Carbon::parse($stop->at)->toIso8601String() }}">
                            {{ \Illuminate\Support\Carbon::parse($stop->at)->format('M j, Y · g:i A') }}
                        </time>
                    @elseif($stop->rank === 11 && !empty($eta))
                        <span class="order-timeline-next">Estimated {{ $eta }}</span>
                    @elseif($stop->rank === $nextRank)
                        <span class="order-timeline-next is-next">Up next</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
@elseif($steps->isNotEmpty())
    <ol class="order-timeline" aria-label="Order timeline">
        @foreach($steps as $i => $step)
            @php
                $tone = match ($step->status) {
                    'Delivered', 'Completed' => 'is-done',
                    'Cancelled', 'Delivery Failed', 'Returning', 'Returning to Branch', 'Return Ready', 'Returned to Seller' => 'is-bad',
                    default => '',
                };
            @endphp
            <li class="{{ $i === 0 ? 'is-latest' : '' }} {{ $tone }}">
                <span class="order-timeline-dot" aria-hidden="true"></span>
                <div class="order-timeline-body">
                    <strong>{{ $step->title }}</strong>
                    @if($step->detail)
                        <span>{{ $step->detail }}</span>
                    @endif
                    <time datetime="{{ \Illuminate\Support\Carbon::parse($step->at)->toIso8601String() }}">
                        {{ \Illuminate\Support\Carbon::parse($step->at)->format('M j, Y · g:i A') }}
                    </time>
                </div>
            </li>
        @endforeach
    </ol>
@endif
