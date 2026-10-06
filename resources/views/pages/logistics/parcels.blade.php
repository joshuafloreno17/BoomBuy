<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Parcels — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/logistics-parcels.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="parcels" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header">
                <h1>Parcels</h1>
                <p>Confirm what sellers drop off, send parcels on to the Sorting Center near the buyer, assign riders for final-mile delivery, and handle failed deliveries.</p>
                <p class="center-badge">
                    <i class="bi bi-building"></i>
                    @if ($myCenter)
                        {{ $myCenter->name }} <span>· {{ $myCenter->town }}</span>
                    @else
                        Head office <span>· all Sorting Centers</span>
                    @endif
                </p>
            </div>

            @if (session('success'))
                <div class="success-box"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="error-box"><i class="bi bi-x-circle-fill"></i> {{ session('error') }}</div>
            @endif

            {{-- FIND A PARCEL --}}
            <form method="GET" action="{{ route('logistics.parcels') }}" class="parcel-search" data-live-search data-live-target="#liveClear, #liveResults">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ $search }}" placeholder="Scan or type a waybill no. (BB-000066), order #, buyer or address" aria-label="Find a parcel">
                <button type="submit">Search</button>
                <span id="liveClear" style="display:contents;">
                @if ($search !== '')
                    <a href="{{ route('logistics.parcels') }}">Clear</a>
                @endif
                </span>
            </form>

            <div id="liveResults">

            {{-- JUMP LINKS --}}
            <nav class="section-jump" aria-label="Parcel sections">
                <a href="#awaiting-confirmation" class="{{ count($awaitingConfirmation) ? 'has-work' : '' }}">Drop-offs <span>{{ count($awaitingConfirmation) }}</span></a>
                <a href="#to-dispatch" class="{{ count($toDispatch) ? 'has-work' : '' }}">To dispatch <span>{{ count($toDispatch) }}</span></a>
                <a href="#incoming" class="{{ count($incoming) ? 'has-work' : '' }}">Incoming <span>{{ count($incoming) }}</span></a>
                <a href="#awaiting-assignment" class="{{ count($awaitingAssignment) ? 'has-work' : '' }}">Assign rider <span>{{ count($awaitingAssignment) }}</span></a>
                <a href="#failed-deliveries" class="{{ count($failedDeliveries) ? 'has-work' : '' }}">Failed <span>{{ count($failedDeliveries) }}</span></a>
                <a href="#on-the-road">On the road <span>{{ count($onTheRoad) }}</span></a>
            </nav>

            @if ($search !== '' && $elsewhere->isNotEmpty())
                <h2 class="section-heading"><i class="bi bi-geo-alt-fill"></i> Other matches</h2>

                @foreach ($elsewhere as $order)
                    <div class="app-card compact">
                        <div class="app-card-top">
                            <div>
                                <div class="app-name">Order #{{ $order->id }}</div>
                                <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                            </div>
                            <x-status-pill :status="$order->status" />
                        </div>
                        <div class="parcel-meta">
                            @if (in_array($order->status, ['Pending', 'Processing']))
                                Still with the seller — it will show up under "Confirm arrival" once the seller drops it off.
                            @else
                                Last updated {{ \Carbon\Carbon::parse($order->updated_at)->diffForHumans() }}.
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif

            {{-- 1. DROP-OFFS AWAITING CONFIRMATION --}}
            <h2 class="section-heading" id="awaiting-confirmation"><i class="bi bi-envelope-paper-fill"></i> Seller Drop-offs ({{ count($awaitingConfirmation) }})</h2>

            @forelse ($awaitingConfirmation as $order)

                <div class="app-card">

                    <div class="app-card-top">
                        <div>
                            <div class="app-name">Order #{{ $order->id }}</div>
                            <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                        </div>
                        <x-status-pill :status="$order->status" />
                    </div>

                    @include('pages.logistics.partials.parcel-route', ['order' => $order])

                    <div class="parcel-meta">
                        <i class="bi bi-clock"></i> {{ $order->status === 'Dropped Off' ? 'Dropped off by the seller' : 'Picked up' }} {{ \Carbon\Carbon::parse($order->updated_at)->diffForHumans() }}
                    </div>

                    <div class="app-actions">
                        <form method="POST" action="{{ route('logistics.parcels.confirm-received', $order->id) }}">
                            @csrf
                            <button type="submit" class="approve-btn"><i class="bi bi-check-circle-fill"></i> Confirm Parcel Received</button>
                        </form>
                    </div>

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-box-seam-fill"></i></div>
                    <h3>{{ $search !== '' ? 'No matches here' : 'No Drop-offs Waiting' }}</h3>
                    <p>Parcels sellers drop off here will appear until you confirm them.</p>
                </div>

            @endforelse

            {{-- 2. RECEIVED HERE, BUYER IS IN ANOTHER TOWN --}}
            <h2 class="section-heading" id="to-dispatch"><i class="bi bi-send-fill"></i> To Dispatch ({{ count($toDispatch) }})</h2>

            @forelse ($toDispatch as $order)

                <div class="app-card">

                    <div class="app-card-top">
                        <div>
                            <div class="app-name">Order #{{ $order->id }}</div>
                            <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                        </div>
                        <x-status-pill :status="$order->status" />
                    </div>

                    @include('pages.logistics.partials.parcel-route', ['order' => $order])

                    <div class="app-actions">
                        <form method="POST" action="{{ route('logistics.parcels.dispatch', $order->id) }}">
                            @csrf
                            <button type="submit" class="approve-btn"><i class="bi bi-send-fill"></i> Dispatch to {{ $centerNames[$order->destination_center_id] ?? 'destination' }}</button>
                        </form>
                    </div>

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-send"></i></div>
                    <h3>{{ $search !== '' ? 'No matches here' : 'Nothing To Dispatch' }}</h3>
                    <p>Parcels going to a buyer in another town wait here until you send them on.</p>
                </div>

            @endforelse

            {{-- 3. ON THE WAY HERE FROM ANOTHER CENTER --}}
            <h2 class="section-heading" id="incoming"><i class="bi bi-truck"></i> Incoming ({{ count($incoming) }})</h2>

            @forelse ($incoming as $order)

                <div class="app-card">

                    <div class="app-card-top">
                        <div>
                            <div class="app-name">Order #{{ $order->id }}</div>
                            <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                        </div>
                        <x-status-pill :status="$order->status" />
                    </div>

                    @include('pages.logistics.partials.parcel-route', ['order' => $order])

                    <div class="parcel-meta">
                        <i class="bi bi-clock"></i> Dispatched {{ $order->dispatched_at ? \Carbon\Carbon::parse($order->dispatched_at)->diffForHumans() : '' }}
                    </div>

                    <div class="app-actions">
                        <form method="POST" action="{{ route('logistics.parcels.confirm-arrival', $order->id) }}">
                            @csrf
                            <button type="submit" class="approve-btn"><i class="bi bi-check-circle-fill"></i> Confirm Arrival</button>
                        </form>
                    </div>

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-truck"></i></div>
                    <h3>{{ $search !== '' ? 'No matches here' : 'Nothing Incoming' }}</h3>
                    <p>Parcels sent here from other Sorting Centers appear until you confirm they arrived.</p>
                </div>

            @endforelse

            {{-- 2. AWAITING ASSIGNMENT --}}
            <h2 class="section-heading" id="awaiting-assignment"><i class="bi bi-inbox-fill"></i> Awaiting Assignment ({{ count($awaitingAssignment) }})</h2>

            @forelse ($awaitingAssignment as $order)

                @php
                    $suggested = $suggestedRidersByOrder[$order->id] ?? collect();
                @endphp

                <div class="app-card">

                    <div class="app-card-top">
                        <div>
                            <div class="app-name">Order #{{ $order->id }}</div>
                            <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                        </div>
                        <x-status-pill status="At Sorting Center" />
                    </div>

                    @include('pages.logistics.partials.parcel-route', ['order' => $order])

                    <div class="parcel-meta">
                        <i class="bi bi-clock"></i>
                        Arrived {{ $order->sorting_center_received_at ? \Carbon\Carbon::parse($order->sorting_center_received_at)->diffForHumans() : 'recently' }}
                        @if ($suggested->isNotEmpty())
                            · <i class="bi bi-geo-alt-fill"></i> Area match: {{ $suggested->pluck('name')->join(', ') }}
                        @endif
                    </div>

                    <div class="app-actions">
                        <form class="assign-form" method="POST" action="{{ route('logistics.parcels.assign', $order->id) }}">
                            @csrf
                            <select name="rider_id" required>
                                <option value="">Select Rider</option>
                                @foreach ($suggested as $rider)
                                    <option value="{{ $rider->id }}">{{ $rider->name }} (area match)</option>
                                @endforeach
                                @foreach ($activeRiders->whereNotIn('id', $suggested->pluck('id')) as $rider)
                                    <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="approve-btn">Assign for Delivery</button>
                        </form>
                    </div>

                    @if ($activeRiders->isEmpty())
                        <div class="remarks-note" style="margin-top:10px;">
                            There are no active riders yet. Approve a rider on the <a href="{{ route('logistics.riders') }}">Riders</a> page first.
                        </div>
                    @elseif ($suggested->isEmpty())
                        <div class="remarks-note" style="margin-top:10px;">
                            No rider has a matching delivery area for this address yet — pick any available rider.
                        </div>
                    @endif

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-inbox-fill"></i></div>
                    <h3>{{ $search !== '' ? 'No matches here' : 'No Parcels Awaiting Assignment' }}</h3>
                    <p>Confirmed parcels ready for rider assignment will appear here.</p>
                </div>

            @endforelse

            {{-- 3. FAILED --}}
            <h2 class="section-heading" id="failed-deliveries"><i class="bi bi-exclamation-triangle-fill"></i> Failed Deliveries ({{ count($failedDeliveries) }})</h2>

            @forelse ($failedDeliveries as $order)

                @php
                    $suggested = $suggestedRidersByOrder[$order->id] ?? collect();
                @endphp

                <div class="app-card">

                    <div class="app-card-top">
                        <div>
                            <div class="app-name">Order #{{ $order->id }}</div>
                            <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                        </div>
                        @if(!empty($order->buyer_refused_at))
                            <span class="status-badge status-rejected"><i class="bi bi-hand-thumbs-down-fill"></i> Refused by Buyer</span>
                        @else
                            <span class="status-badge status-rejected">Delivery Failed ({{ $order->delivery_attempts }}/2 attempts)</span>
                        @endif
                    </div>

                    <div class="parcel-meta">
                        <i class="bi bi-clock"></i>
                        Failed {{ $order->delivery_failed_at ? \Carbon\Carbon::parse($order->delivery_failed_at)->diffForHumans() : 'recently' }}
                        @if (!empty($riderNames[$order->delivery_rider_id]))
                            · <i class="bi bi-bicycle"></i> Last rider: {{ $riderNames[$order->delivery_rider_id] }}
                        @endif
                    </div>

                    <div class="remarks-note">
                        Reason: {{ $order->failure_reason ?? 'No reason provided.' }}
                        @if(!empty($order->buyer_refused_at))
                            <br>The buyer refused this parcel — it can only be returned to the seller.
                        @elseif($order->delivery_attempts >= 2)
                            <br>This parcel has reached the maximum of 2 delivery attempts — return it to the seller.
                        @endif
                    </div>

                    <div class="app-actions">

                        @if($order->delivery_attempts < 2 && empty($order->buyer_refused_at))

                            <form class="assign-form" method="POST" action="{{ route('logistics.parcels.reschedule', $order->id) }}">
                                @csrf
                                <select name="rider_id" required>
                                    <option value="">Select Rider</option>
                                    @foreach ($suggested as $rider)
                                        <option value="{{ $rider->id }}">{{ $rider->name }} (area match)</option>
                                    @endforeach
                                    @foreach ($activeRiders->whereNotIn('id', $suggested->pluck('id')) as $rider)
                                        <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="approve-btn"><i class="bi bi-arrow-repeat"></i> Reschedule</button>
                            </form>

                        @endif

                        <form method="POST" action="{{ route('logistics.parcels.return-to-seller', $order->id) }}" data-confirm="Return this parcel to the seller? This cannot be undone." data-confirm-ok="Return Parcel" data-confirm-danger>
                            @csrf
                            <button type="submit" class="reject-btn"><i class="bi bi-arrow-return-left"></i> Return to Seller</button>
                        </form>

                    </div>

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <h3>{{ $search !== '' ? 'No matches here' : 'No Failed Deliveries' }}</h3>
                    <p>Parcels that couldn't be delivered will appear here for rescheduling or return.</p>
                </div>

            @endforelse

            {{-- 4. ON THE ROAD (read-only) --}}
            <h2 class="section-heading" id="on-the-road"><i class="bi bi-truck"></i> On the Road ({{ count($onTheRoad) }})</h2>

            @forelse ($onTheRoad as $order)

                <div class="app-card compact">
                    <div class="app-card-top">
                        <div>
                            <div class="app-name">Order #{{ $order->id }}</div>
                            <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                        </div>
                        <x-status-pill :status="$order->status" />
                    </div>
                    <div class="parcel-meta">
                        <i class="bi bi-bicycle"></i> {{ $riderNames[$order->delivery_rider_id] ?? 'Unknown rider' }}
                        · updated {{ \Carbon\Carbon::parse($order->updated_at)->diffForHumans() }}
                        @if ($order->delivery_attempts)
                            · attempt {{ $order->delivery_attempts + 1 }} of 2
                        @endif
                    </div>
                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-truck"></i></div>
                    <h3>{{ $search !== '' ? 'No matches here' : 'Nothing out for delivery' }}</h3>
                    <p>Parcels handed to a delivery rider show up here until they are delivered.</p>
                </div>

            @endforelse

            </div>

        </div>

    </main>

    @include('partials.live-search')
    @include('partials.pwa-register')

</body>
</html>
