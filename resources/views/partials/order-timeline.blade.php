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
    <a href="{{ $proof }}" target="_blank" rel="noopener" class="order-proof-link">
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
                    <strong>{{ $stop->title }}</strong>
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
