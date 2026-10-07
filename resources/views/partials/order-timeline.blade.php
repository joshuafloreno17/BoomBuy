{{--
    An order's trip, newest step first: what happened, where, and when.
    @include('partials.order-timeline', ['steps' => $timelines[$orderId] ?? collect()])
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

@if($steps->isNotEmpty())
    <ol class="order-timeline" aria-label="Order timeline">
        @foreach($steps as $i => $step)
            @php
                $tone = match ($step->status) {
                    'Delivered', 'Completed' => 'is-done',
                    'Cancelled', 'Delivery Failed', 'Returning', 'Return Ready', 'Returned to Seller' => 'is-bad',
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
