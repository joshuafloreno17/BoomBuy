<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Parcels — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/logistics-parcels.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="parcels" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header">
                <h1>Parcels</h1>
                <p>{{ $isBranch ? 'Confirm what arrives, send seller parcels to the Sorting Center, give riders their deliveries and hand pick-ups to buyers.' : 'Confirm what arrives, sort it, send it on, and give riders their deliveries — one tab at a time.' }}</p>
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

            @if (!empty($unassigned))
                <div class="error-box"><i class="bi bi-hourglass-split"></i> You are not assigned to a Sorting Center yet, so there are no parcels to show. The BoomBuy admin will assign you.</div>
            @endif

            {{-- FIND A PARCEL --}}
            @php $canScan = (bool) $myCenter; @endphp
            <div class="parcel-find-row">
            <form method="GET" action="{{ route('logistics.parcels') }}" class="parcel-search" data-live-search data-live-target="#liveClear, #liveResults">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ $search }}" placeholder="Scan or type a waybill no. (BB4829105736), order #, buyer or address" aria-label="Find a parcel">
                <button type="submit">Search</button>
                <span id="liveClear" style="display:contents;">
                @if ($search !== '')
                    <a href="{{ route('logistics.parcels') }}">Clear</a>
                @endif
                </span>
            </form>
            @if ($canScan)
                <button type="button" class="ss-open-btn" data-scan-toggle aria-controls="scanStation" aria-expanded="false"><i class="bi bi-upc-scan"></i> <span>Open Scan Station</span></button>
            @endif
            </div>

            {{-- SCAN STATION (center / branch staff only). A scanned waybill does the
                 parcel's next step here: IN = it arrived, OUT = it left on the truck.
                 Every button on the parcel cards below still works the same. --}}
            @if ($canScan)
                @php
                    $scanNow = session('scan');
                    $scanStats = session('scan_stats');
                    $scanStats = is_array($scanStats) && ($scanStats['date'] ?? '') === now()->toDateString() ? $scanStats : ['in' => 0, 'out' => 0, 'bad' => 0];
                    $scanLog = ($scanStats['in'] + $scanStats['out'] + $scanStats['bad'] > 0 || is_array($scanNow)) ? session('scan_log', []) : [];
                    $undoToken = is_array($scanNow) ? ($scanNow['undo'] ?? null) : null;
                @endphp
                <section class="scan-station" id="scanStation" aria-label="Scan Station" @if (!request()->boolean('scanned')) hidden @endif data-opened="{{ request()->boolean('scanned') ? '1' : '0' }}">
                    <div class="ss-left">
                        <div class="ss-head">
                            <strong><i class="bi bi-upc-scan"></i> Scan Station</strong>
                            <button type="button" class="ss-close" data-scan-toggle aria-label="Close the Scan Station"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <div class="ss-mode" role="group" aria-label="Scan type">
                            <button type="button" data-scan-mode="in" aria-pressed="true"><i class="bi bi-box-arrow-in-down"></i> Arriving (IN)</button>
                            <button type="button" data-scan-mode="out" aria-pressed="false"><i class="bi bi-truck"></i> Leaving (OUT)</button>
                        </div>
                        <div class="ss-cam" id="scanCam" hidden>
                            <div id="scanCamView" class="scan-cam-view"></div>
                            <span><i class="bi bi-qr-code-scan"></i> Hold the waybill's QR code up to the camera.</span>
                        </div>
                        <form method="POST" action="{{ route('logistics.parcels.scan') }}" id="parcelScanForm" class="ss-input" autocomplete="off">
                            @csrf
                            <input type="hidden" name="mode" value="in" id="scanMode">
                            <i class="bi bi-upc"></i>
                            <input type="text" name="code" id="scanCode" placeholder="Scan or type a waybill no." aria-label="Waybill to scan" required>
                            <button type="submit">Go</button>
                        </form>
                        <div class="ss-tools">
                            <span class="ss-ready"><b></b> USB scanner ready</span>
                            <button type="button" class="ss-cam-btn" data-scan-cam aria-label="Scan with the camera"><i class="bi bi-camera-fill"></i> <span>Camera</span></button>
                        </div>
                    </div>
                    <div class="ss-right">
                        <div class="ss-stats">
                            <div><small>IN today</small><strong>{{ $scanStats['in'] }}</strong></div>
                            <div><small>OUT today</small><strong>{{ $scanStats['out'] }}</strong></div>
                            <div><small>Problems</small><strong class="is-bad">{{ $scanStats['bad'] }}</strong></div>
                        </div>
                        <h3>Last scans</h3>
                        <ul class="ss-log" role="status">
                            @forelse ($scanLog as $row)
                                <li class="ss-row is-{{ $row['kind'] }}">
                                    <i class="bi {{ ['done' => 'bi-check-circle-fill', 'manual' => 'bi-hand-index-thumb-fill', 'warn' => 'bi-exclamation-triangle-fill', 'bad' => 'bi-x-octagon-fill', 'undone' => 'bi-arrow-counterclockwise'][$row['kind']] ?? 'bi-dot' }}"></i>
                                    <span>{{ $row['message'] }}</span>
                                    @if ($undoToken && ($row['undo'] ?? null) === $undoToken)
                                        <form method="POST" action="{{ route('logistics.parcels.scan.undo') }}" class="scan-undo" data-scan-undo data-seconds="10">
                                            @csrf
                                            <input type="hidden" name="token" value="{{ $undoToken }}">
                                            <button type="submit"><i class="bi bi-arrow-counterclockwise"></i> Undo <small data-scan-undo-left>10</small></button>
                                        </form>
                                    @endif
                                </li>
                            @empty
                                <li class="ss-row is-empty"><span>No scans yet. Ready.</span></li>
                            @endforelse
                        </ul>
                        <p class="ss-note">Assigning the rider, the buyer's pickup code and returns are still done with the buttons below.</p>
                    </div>
                </section>
            @endif

            <div id="liveResults">

            {{-- THE WORK TABS: a Sorting Center and a branch each see only their own jobs. --}}
            @php
                $hasBranchQueues = $isBranch || $isHeadOffice;
                $sectionCount = [
                    'awaiting-confirmation' => count($awaitingConfirmation),
                    'to-center' => $hasBranchQueues ? count($toCenter) : 0,
                    'awaiting-pickup' => count($awaitingPickup ?? []),
                    'to-dispatch' => count($toDispatch),
                    'incoming' => count($incoming),
                    'to-sort' => count($toSort),
                    'to-branch' => $isBranch ? 0 : count($toBranch),
                    'from-center' => $hasBranchQueues ? count($incomingToBranch) : 0,
                    'awaiting-assignment' => count($awaitingAssignment),
                    'failed-deliveries' => count($failedDeliveries),
                    'on-the-road' => count($onTheRoad),
                    'buyer-returns' => collect($buyerReturns)->sum(fn ($l) => count($l)),
                    'ready-to-collect' => count($readyToCollect),
                    'returns' => count($incomingReturns) + count($returnReady),
                    'cod' => count($codToReceive),
                ];
                $sectionTab = [
                    'awaiting-confirmation' => 'arrivals', 'incoming' => 'arrivals', 'from-center' => 'arrivals',
                    'to-sort' => $isBranch ? 'arrivals' : 'sort',
                    'to-center' => 'sendout', 'to-dispatch' => 'sendout', 'to-branch' => 'sendout',
                    'awaiting-assignment' => 'assign',
                    'ready-to-collect' => 'collect',
                    'failed-deliveries' => 'problems',
                    'buyer-returns' => 'returns', 'returns' => 'returns',
                    'cod' => 'cod',
                    'awaiting-pickup' => 'sellers',
                    'on-the-road' => 'road',
                ];
                $tabDefs = [
                    'arrivals' => ['Arrivals', $isBranch ? 'From the center & sellers' : 'Confirm it arrived', 'bi-box-arrow-in-down',
                        $isBranch ? 'Parcels on their way to this branch — on the truck from the Sorting Center, or with a pickup rider from a seller in town. Confirm each one when it is in your hands.'
                                  : 'Parcels on their way here — from pickup riders, branches and other Sorting Centers. Confirm each one when it is in your hands.'],
                    'sort' => ['To sort', 'Put in the right bin', 'bi-diagram-3-fill', 'Parcels here for buyers near this center. Sort each one to its delivery area.'],
                    'sendout' => ['Send out', $isBranch ? 'To the Sorting Center' : 'Load on the truck', 'bi-truck',
                        $isBranch ? 'Parcels from sellers in this town. Send them on the truck to the Sorting Center.'
                                  : 'Parcels ready to leave — to another Sorting Center or to a branch.'],
                    'assign' => ['Assign rider', 'Last mile', 'bi-bicycle', 'Parcels for buyers nearby. Give each one a delivery rider.'],
                    'collect' => ['Ready to collect', 'Buyer picks it up here', 'bi-person-check-fill', 'Buyers who chose pick-up come here. Check their pickup code, then hand the parcel over.'],
                    'problems' => ['Problems', 'Failed deliveries', 'bi-exclamation-triangle-fill', 'Deliveries that did not go through. Reschedule them, or return them to the seller.'],
                    'returns' => ['Returns', 'Back to the seller', 'bi-arrow-return-left', 'Items buyers sent back, and parcels going back to their seller.'],
                    'cod' => ['COD cash', 'From riders', 'bi-cash-stack', 'Cash riders collected on delivery. Count it, then confirm.'],
                    'sellers' => ['Waiting at sellers', 'View only', 'bi-shop', 'Parcels still with the seller, waiting for a pickup rider. Nothing to do here yet.'],
                    'road' => ['On the road', 'With delivery riders', 'bi-truck', 'Parcels out with a delivery rider. Take one back only if the rider cannot finish it.'],
                ];
                $mainTabs = $isBranch ? ['arrivals', 'sendout', 'assign', 'collect', 'problems', 'returns', 'cod'] : ['arrivals', 'sort', 'sendout', 'assign', 'problems', 'returns', 'cod'];
                $moreTabs = $isBranch ? ['sellers', 'road'] : ['sellers', 'road', 'collect'];
                $allTabs = array_merge($mainTabs, $moreTabs);
                $tabCount = fn (string $t) => collect($sectionTab)->filter(fn ($v) => $v === $t)->keys()->sum(fn ($k) => $sectionCount[$k]);
                $activeTab = $search === '' && in_array(request('tab'), $allTabs, true)
                    ? request('tab')
                    : (collect($search !== '' ? $allTabs : $mainTabs)->first(fn ($t) => $tabCount($t) > 0) ?? 'arrivals');
                // A section shows only in its tab; in a tab with work, its empty sections stay out of the way.
                $pqHidden = fn (string $s) => $sectionTab[$s] !== $activeTab || $tabCount($activeTab) === 0 || $sectionCount[$s] === 0;
                $nextWork = fn (string $t) => collect($mainTabs)->first(fn ($m) => $m !== $t && $tabCount($m) > 0);
            @endphp

            <nav class="pq-tabs" role="tablist" aria-label="Parcel work">
                @foreach ($mainTabs as $t)
                    <button type="button" role="tab" data-pq-tab="{{ $t }}" aria-selected="{{ $t === $activeTab ? 'true' : 'false' }}"
                            @class(['pq-tab', 'is-on' => $t === $activeTab, 'is-bad' => $t === 'problems' && $tabCount($t) > 0])>
                        <span class="pq-ic"><i class="bi {{ $tabDefs[$t][2] }}"></i></span>
                        <span class="pq-txt"><strong>{{ $tabDefs[$t][0] }}</strong><small>{{ $tabDefs[$t][1] }}</small></span>
                        <span class="pq-n">{{ $tabCount($t) }}</span>
                    </button>
                @endforeach
            </nav>

            <div class="pq-more">
                <span>Also:</span>
                @foreach ($moreTabs as $t)
                    <button type="button" data-pq-tab="{{ $t }}" @class(['is-on' => $t === $activeTab])>{{ $tabDefs[$t][0] }} · {{ $tabCount($t) }}</button>
                @endforeach
            </div>

            @foreach ($allTabs as $t)
                <div class="pq-head" data-pq-head="{{ $t }}" @if ($t !== $activeTab) hidden @endif>
                    <div>
                        <h2><i class="bi {{ $tabDefs[$t][2] }}"></i> {{ $tabDefs[$t][0] }} <span>{{ $tabCount($t) }}</span></h2>
                        <p>{{ $tabDefs[$t][3] }}</p>
                    </div>

                    @if ($t === 'arrivals' && (count($awaitingConfirmation) + count($incoming) + ($hasBranchQueues ? count($incomingToBranch) : 0)) > 1)
                        @php $arrivalTotal = count($awaitingConfirmation) + count($incoming) + ($hasBranchQueues ? count($incomingToBranch) : 0); @endphp
                        <form method="POST" action="{{ route('logistics.parcels.bulk') }}" data-confirm="Confirm all {{ $arrivalTotal }} parcels are here?" data-confirm-ok="Confirm All">
                            @csrf
                            @foreach ($awaitingConfirmation as $o)<input type="hidden" name="received[]" value="{{ $o->id }}">@endforeach
                            @foreach ($incoming as $o)<input type="hidden" name="arrival[]" value="{{ $o->id }}">@endforeach
                            @if ($hasBranchQueues)
                                @foreach ($incomingToBranch as $o)<input type="hidden" name="branch[]" value="{{ $o->id }}">@endforeach
                            @endif
                            <button type="submit" class="approve-btn"><i class="bi bi-check2-all"></i> Confirm all {{ $arrivalTotal }} arrived</button>
                        </form>
                    @elseif ($t === 'sort' && count($toSort) > 1)
                        <form method="POST" action="{{ route('logistics.parcels.bulk') }}" data-confirm="Sort all {{ count($toSort) }} parcels to their delivery areas?" data-confirm-ok="Sort All">
                            @csrf
                            @foreach ($toSort as $o)<input type="hidden" name="sort[]" value="{{ $o->id }}">@endforeach
                            <button type="submit" class="approve-btn"><i class="bi bi-diagram-3-fill"></i> Sort all {{ count($toSort) }}</button>
                        </form>
                    @endif
                </div>

                <div class="pq-empty" data-pq-empty="{{ $t }}" @if ($t !== $activeTab || $tabCount($t) > 0) hidden @endif>
                    <strong>Nothing to do here</strong>
                    @if ($next = $nextWork($t))
                        <span>Next work: <button type="button" data-pq-tab="{{ $next }}">{{ $tabDefs[$next][0] }} ({{ $tabCount($next) }})</button></span>
                    @else
                        <span>All caught up.</span>
                    @endif
                </div>
            @endforeach

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
                            @if (in_array($order->status, ['Pending', 'Confirmed', 'Preparing', 'Ready for Pickup', 'Pickup Assigned']))
                                Still with the seller — it will show up under "Confirm arrival" once a rider picks it up.
                            @else
                                Last updated {{ \Carbon\Carbon::parse($order->updated_at)->diffForHumans() }}.
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif

            <div class="pq" data-pq="{{ $sectionTab['awaiting-confirmation'] }}" data-count="{{ $sectionCount['awaiting-confirmation'] }}" @if ($pqHidden('awaiting-confirmation')) hidden @endif>
            {{-- 1. DROP-OFFS AWAITING CONFIRMATION --}}
            <h2 class="section-heading" id="awaiting-confirmation"><i class="bi bi-box-arrow-in-down"></i> Pickup Arrivals ({{ count($awaitingConfirmation) }})</h2>

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
                        <i class="bi bi-clock"></i> {{ match ($order->status) { 'Dropped Off' => 'Dropped off by the seller', 'To Sorting Center' => 'Sent by ' . ($centerNames[$order->current_center_id] ?? 'a branch'), default => 'Picked up by a rider' } }} {{ \Carbon\Carbon::parse($order->updated_at)->diffForHumans() }}
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
                    <h3>{{ $search !== '' ? 'No matches here' : 'No Pickups Waiting' }}</h3>
                    <p>Parcels pickup riders bring here (or a branch's truck brings in) appear until you confirm them.</p>
                </div>

            @endforelse

            </div>

            <div class="pq" data-pq="{{ $sectionTab['to-center'] }}" data-count="{{ $sectionCount['to-center'] }}" @if ($pqHidden('to-center')) hidden @endif>
            {{-- AT THE SELLER'S BRANCH: TRUCK IT TO THE SORTING CENTER --}}
            @if ($isBranch || $isHeadOffice)
                <h2 class="section-heading" id="to-center"><i class="bi bi-box-arrow-up-right"></i> To the Sorting Center ({{ count($toCenter) }})</h2>

                @if (count($toCenter) > 1 && $isBranch)
                    <div class="dispatch-batch">
                        <form method="POST" action="{{ route('logistics.parcels.to-center-all') }}" data-confirm="Send all {{ count($toCenter) }} parcel(s) to {{ $centerNames[$toCenter->first()->origin_center_id] ?? 'the Sorting Center' }}?" data-confirm-ok="Send All">
                            @csrf
                            <button type="submit" class="approve-btn"><i class="bi bi-truck"></i> Send all {{ count($toCenter) }} to {{ $centerNames[$toCenter->first()->origin_center_id] ?? 'the Sorting Center' }}</button>
                        </form>
                    </div>
                @endif

                @forelse ($toCenter as $order)
                    <div class="app-card">
                        <div class="app-card-top">
                            <div>
                                <div class="app-name">Order #{{ $order->id }} · {{ \App\Support\Waybill::number((int) $order->id) }}</div>
                                <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                            </div>
                            <x-status-pill :status="$order->status" />
                        </div>
                        @include('pages.logistics.partials.parcel-route', ['order' => $order])

                        <div class="parcel-meta">
                            <i class="bi bi-clock"></i> Received {{ $order->origin_branch_received_at ? \Carbon\Carbon::parse($order->origin_branch_received_at)->diffForHumans() : '' }}
                            @if ($isHeadOffice)
                                at {{ $centerNames[$order->current_center_id] ?? 'a branch' }}
                            @endif
                        </div>
                        <div class="app-actions">
                            <form method="POST" action="{{ route('logistics.parcels.to-center', $order->id) }}">
                                @csrf
                                <button type="submit" class="approve-btn"><i class="bi bi-truck"></i> Send to {{ $centerNames[$order->origin_center_id] ?? 'the Sorting Center' }}</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="empty">
                        <div class="empty-icon"><i class="bi bi-box-arrow-up-right"></i></div>
                        <h3>{{ $search !== '' ? 'No matches here' : 'Nothing to Send' }}</h3>
                        <p>Parcels pickup riders bring to this branch wait here for the truck to the Sorting Center.</p>
                    </div>
                @endforelse
            @endif

            </div>

            <div class="pq" data-pq="{{ $sectionTab['awaiting-pickup'] }}" data-count="{{ $sectionCount['awaiting-pickup'] }}" @if ($pqHidden('awaiting-pickup')) hidden @endif>
            {{-- WAITING AT THE SELLER FOR A PICKUP RIDER (view only) --}}
            <h2 class="section-heading" id="awaiting-pickup"><i class="bi bi-shop"></i> Waiting for Pickup ({{ count($awaitingPickup ?? []) }})</h2>
            @forelse($awaitingPickup ?? [] as $pickup)
                <div class="parcel-card" data-awaiting-pickup="{{ $pickup->id }}">
                    <strong>#{{ $pickup->id }}</strong> · {{ \App\Support\Waybill::number((int) $pickup->id) }} · {{ \App\Support\OrderStatus::label($pickup->status) }}
                    <small>{{ $pickup->shipping_name }} · pickup {{ $pickup->pickup_date ? \Illuminate\Support\Carbon::parse($pickup->pickup_date)->format('M d') : 'not booked' }} · {{ \App\Support\CodPolicy::isCod($pickup->payment_method) ? 'COD ₱' . number_format((float) $pickup->total_amount, 2) : 'Paid' }}</small>
                </div>
            @empty
                <p class="empty-note">No parcels waiting at sellers.</p>
            @endforelse

            </div>

            <div class="pq" data-pq="{{ $sectionTab['to-dispatch'] }}" data-count="{{ $sectionCount['to-dispatch'] }}" @if ($pqHidden('to-dispatch')) hidden @endif>
            {{-- 2. RECEIVED HERE, BUYER IS IN ANOTHER TOWN --}}
            <h2 class="section-heading" id="to-dispatch"><i class="bi bi-send-fill"></i> To Dispatch ({{ count($toDispatch) }})</h2>

            {{-- One click per destination when several parcels go the same way. --}}
            {{-- Hub relay: the truck's next stop (a hub on the way, or the buyer's center). --}}
            @php $hopOf = fn ($o) => \App\Support\HubRoute::nextHop((int) $o->current_center_id, (int) $o->destination_center_id); @endphp
            @if (count($toDispatch) > 1)
                <div class="dispatch-batch">
                    @foreach ($toDispatch->groupBy($hopOf) as $destinationId => $group)
                        <form method="POST" action="{{ route('logistics.parcels.dispatch-all') }}" data-confirm="Dispatch all {{ count($group) }} parcel(s) to {{ $centerNames[$destinationId] ?? 'that center' }}?" data-confirm-ok="Dispatch All">
                            @csrf
                            <input type="hidden" name="destination_center_id" value="{{ $destinationId }}">
                            <button type="submit" class="approve-btn"><i class="bi bi-send-check-fill"></i> Dispatch all {{ count($group) }} to {{ $centerNames[$destinationId] ?? 'destination' }}</button>
                        </form>
                    @endforeach
                </div>
            @endif

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
                            @php $hop = $hopOf($order); @endphp
                            <button type="submit" class="approve-btn"><i class="bi bi-send-fill"></i> Dispatch to {{ $centerNames[$hop] ?? 'destination' }}@if ($hop !== (int) $order->destination_center_id) <small class="hub-note">· hub on the way to {{ $centerNames[$order->destination_center_id] ?? 'the buyer center' }}</small>@endif</button>
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

            </div>

            <div class="pq" data-pq="{{ $sectionTab['incoming'] }}" data-count="{{ $sectionCount['incoming'] }}" @if ($pqHidden('incoming')) hidden @endif>
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

            </div>

            <div class="pq" data-pq="{{ $sectionTab['to-sort'] }}" data-count="{{ $sectionCount['to-sort'] }}" @if ($pqHidden('to-sort')) hidden @endif>
            {{-- TO SORT --}}
            <h2 class="section-heading" id="to-sort"><i class="bi bi-diagram-3-fill"></i> To Sort ({{ count($toSort) }})</h2>

            @forelse ($toSort as $order)
                @php $area = \App\Services\SortingCenterService::deliveryArea($order); @endphp
                <div class="app-card">
                    <div class="app-card-top">
                        <div>
                            <div class="app-name">Order #{{ $order->id }} · {{ \App\Support\Waybill::number((int) $order->id) }}</div>
                            <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                        </div>
                        <x-status-pill status="At Sorting Center" />
                    </div>
                    @include('pages.logistics.partials.parcel-route', ['order' => $order])

                    <div class="parcel-meta">
                        <i class="bi bi-geo-alt-fill"></i> Delivery area: <strong>{{ $area ?? 'Unknown — check the address' }}</strong>
                        @if (($suggestedRidersByOrder[$order->id] ?? collect())->isNotEmpty())
                            · Rider for this area: {{ $suggestedRidersByOrder[$order->id]->pluck('name')->join(', ') }}
                        @endif
                    </div>
                    <div class="app-actions">
                        <form method="POST" action="{{ route('logistics.parcels.sort', $order->id) }}">
                            @csrf
                            <button type="submit" class="approve-btn"><i class="bi bi-diagram-3-fill"></i> Sort to {{ $area ?? 'area' }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-diagram-3-fill"></i></div>
                    <h3>{{ $search !== '' ? 'No matches here' : 'Nothing to Sort' }}</h3>
                    <p>Parcels that arrived for buyers near this center are sorted by area here.</p>
                </div>
            @endforelse

            </div>

            <div class="pq" data-pq="{{ $sectionTab['to-branch'] }}" data-count="{{ $sectionCount['to-branch'] }}" @if ($pqHidden('to-branch')) hidden @endif>
            {{-- SORTED FOR A TOWN WITH A BRANCH: TRUCK IT THERE --}}
            @unless ($isBranch)
                <h2 class="section-heading" id="to-branch"><i class="bi bi-truck"></i> To Branches ({{ count($toBranch) }})</h2>

                {{-- One truck per branch when several parcels go the same way. --}}
                @if (count($toBranch) > 1)
                    <div class="dispatch-batch">
                        @foreach ($toBranch->groupBy('branch_id') as $branchId => $group)
                            <form method="POST" action="{{ route('logistics.parcels.to-branch-all') }}" data-confirm="Send all {{ count($group) }} parcel(s) to {{ $centerNames[$branchId] ?? 'that branch' }}?" data-confirm-ok="Send All">
                                @csrf
                                <input type="hidden" name="branch_id" value="{{ $branchId }}">
                                <button type="submit" class="approve-btn"><i class="bi bi-truck"></i> Send all {{ count($group) }} to {{ $centerNames[$branchId] ?? 'branch' }}</button>
                            </form>
                        @endforeach
                    </div>
                @endif

                @forelse ($toBranch as $order)
                    <div class="app-card">
                        <div class="app-card-top">
                            <div>
                                <div class="app-name">Order #{{ $order->id }} · {{ \App\Support\Waybill::number((int) $order->id) }}</div>
                                <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                            </div>
                            <x-status-pill :status="$order->status" />
                        </div>
                        @include('pages.logistics.partials.parcel-route', ['order' => $order])

                        <div class="parcel-meta">
                            <i class="bi bi-geo-alt-fill"></i> Delivery area: <strong>{{ \App\Services\SortingCenterService::deliveryArea($order) ?? 'Unknown' }}</strong>
                            · <i class="bi bi-shop-window"></i> Branch: <strong>{{ $centerNames[$order->branch_id] ?? 'Branch' }}</strong>
                        </div>
                        <div class="app-actions">
                            <form method="POST" action="{{ route('logistics.parcels.to-branch', $order->id) }}">
                                @csrf
                                <button type="submit" class="approve-btn"><i class="bi bi-truck"></i> Send to {{ $centerNames[$order->branch_id] ?? 'branch' }}</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="empty">
                        <div class="empty-icon"><i class="bi bi-truck"></i></div>
                        <h3>{{ $search !== '' ? 'No matches here' : 'Nothing for the Branches' }}</h3>
                        <p>Sorted parcels for a town with a BoomBuy branch wait here for the truck. That branch's riders deliver them.</p>
                    </div>
                @endforelse
            @endunless

            </div>

            <div class="pq" data-pq="{{ $sectionTab['from-center'] }}" data-count="{{ $sectionCount['from-center'] }}" @if ($pqHidden('from-center')) hidden @endif>
            {{-- AT A BRANCH: ON THE TRUCK FROM THE CENTER --}}
            @if ($isBranch || $isHeadOffice)
                <h2 class="section-heading" id="from-center"><i class="bi bi-truck"></i> From the Sorting Center ({{ count($incomingToBranch) }})</h2>

                @forelse ($incomingToBranch as $order)
                    <div class="app-card">
                        <div class="app-card-top">
                            <div>
                                <div class="app-name">Order #{{ $order->id }} · {{ \App\Support\Waybill::number((int) $order->id) }}</div>
                                <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_address }}</div>
                            </div>
                            <x-status-pill :status="$order->status" />
                        </div>
                        @include('pages.logistics.partials.parcel-route', ['order' => $order])

                        <div class="parcel-meta">
                            <i class="bi bi-clock"></i> Sent {{ $order->branch_dispatched_at ? \Carbon\Carbon::parse($order->branch_dispatched_at)->diffForHumans() : '' }}
                            from {{ $centerNames[$order->destination_center_id] ?? 'the Sorting Center' }}
                            @if ($isHeadOffice)
                                · <i class="bi bi-shop-window"></i> To {{ $centerNames[$order->branch_id] ?? 'branch' }}
                            @endif
                        </div>
                        <div class="app-actions">
                            <form method="POST" action="{{ route('logistics.parcels.at-branch', $order->id) }}">
                                @csrf
                                <button type="submit" class="approve-btn"><i class="bi bi-check-circle-fill"></i> Confirm Arrival</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="empty">
                        <div class="empty-icon"><i class="bi bi-truck"></i></div>
                        <h3>{{ $search !== '' ? 'No matches here' : 'Nothing on the Truck' }}</h3>
                        <p>Parcels the Sorting Center sends to this branch appear here until you confirm they arrived.</p>
                    </div>
                @endforelse
            @endif

            </div>

            <div class="pq" data-pq="{{ $sectionTab['awaiting-assignment'] }}" data-count="{{ $sectionCount['awaiting-assignment'] }}" @if ($pqHidden('awaiting-assignment')) hidden @endif>
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
                        <x-status-pill :status="$order->status" />
                    </div>

                    @include('pages.logistics.partials.parcel-route', ['order' => $order])

                    @php $arrivedAt = $order->status === 'At Branch' ? $order->at_branch_at : $order->sorting_center_received_at; @endphp
                    <div class="parcel-meta">
                        <i class="bi bi-diagram-3-fill"></i> Area: <strong>{{ \App\Services\SortingCenterService::deliveryArea($order) ?? 'Unknown' }}</strong>
                        · <i class="bi bi-clock"></i>
                        Arrived {{ $arrivedAt ? \Carbon\Carbon::parse($arrivedAt)->diffForHumans() : 'recently' }}
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
                    <p>Sorted parcels ready for rider assignment will appear here.</p>
                </div>

            @endforelse

            </div>

            <div class="pq" data-pq="{{ $sectionTab['failed-deliveries'] }}" data-count="{{ $sectionCount['failed-deliveries'] }}" @if ($pqHidden('failed-deliveries')) hidden @endif>
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

                    @include('pages.logistics.partials.parcel-route', ['order' => $order])

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

                        @if(empty($order->back_at_center_at))

                            {{-- The rider still has it: nothing else can happen until it's back here. --}}
                            <div class="remarks-note"><i class="bi bi-bicycle"></i> Still with the rider. Confirm once they bring it back to the Sorting Center.</div>
                            <form method="POST" action="{{ route('logistics.parcels.confirm-back', $order->id) }}">
                                @csrf
                                <button type="submit" class="approve-btn"><i class="bi bi-box-arrow-in-down"></i> Parcel Is Back Here</button>
                            </form>

                        @elseif($order->delivery_attempts < 2 && empty($order->buyer_refused_at))

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

                        @if(!empty($order->back_at_center_at))
                            <form method="POST" action="{{ route('logistics.parcels.return-to-seller', $order->id) }}" data-confirm="Return this parcel to the seller? This cannot be undone." data-confirm-ok="Return Parcel" data-confirm-danger>
                                @csrf
                                <button type="submit" class="reject-btn"><i class="bi bi-arrow-return-left"></i> Return to Seller</button>
                            </form>
                        @endif

                    </div>

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <h3>{{ $search !== '' ? 'No matches here' : 'No Failed Deliveries' }}</h3>
                    <p>Parcels that couldn't be delivered will appear here for rescheduling or return.</p>
                </div>

            @endforelse

            </div>

            <div class="pq" data-pq="{{ $sectionTab['on-the-road'] }}" data-count="{{ $sectionCount['on-the-road'] }}" @if ($pqHidden('on-the-road')) hidden @endif>
            {{-- 4. ON THE ROAD (take back only, when the rider can't finish it) --}}
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
                    @include('pages.logistics.partials.parcel-route', ['order' => $order])

                    <div class="parcel-meta">
                        <i class="bi bi-bicycle"></i> {{ $riderNames[$order->delivery_rider_id] ?? 'Unknown rider' }}
                        · updated {{ \Carbon\Carbon::parse($order->updated_at)->diffForHumans() }}
                        @if ($order->delivery_attempts)
                            · attempt {{ $order->delivery_attempts + 1 }} of 2
                        @endif
                    </div>
                    {{-- The rider can't finish it (suspended, quit, sick): take it back for another rider. --}}
                    <form method="POST" action="{{ route('logistics.parcels.take-back', $order->id) }}"
                          data-confirm="{{ $order->status === 'Out for Delivery' ? 'Only take it back once the parcel is here at the Sorting Center. It will wait for a new rider.' : 'Take this parcel back from the rider? It will wait for a new rider.' }}">
                        @csrf
                        <button type="submit" class="reject-btn"><i class="bi bi-arrow-counterclockwise"></i> Take Back From Rider</button>
                    </form>
                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-truck"></i></div>
                    <h3>{{ $search !== '' ? 'No matches here' : 'Nothing out for delivery' }}</h3>
                    <p>Parcels handed to a delivery rider show up here until they are delivered.</p>
                </div>

            @endforelse

            </div>

            <div class="pq" data-pq="{{ $sectionTab['buyer-returns'] }}" data-count="{{ $sectionCount['buyer-returns'] }}" @if ($pqHidden('buyer-returns')) hidden @endif>
            {{-- RETURNS FROM BUYERS: items going back to their seller --}}
            @php
                $returnCount = collect($buyerReturns)->sum(fn ($list) => count($list));
                $returnSteps = [
                    'toReceive' => ['Buyer brings it here', 'logistics.returns.receive', 'Received From Buyer', 'bi-box-arrow-in-down', 'Check the item matches return #:id, then take it.'],
                    'toSend' => ['Send to the seller\'s center', 'logistics.returns.send', 'Send to Seller\'s Center', 'bi-send-fill', null],
                    'incoming' => ['On its way here', 'logistics.returns.arrive', 'It Arrived', 'bi-box-seam-fill', null],
                    'forSeller' => ['Waiting for the seller', 'logistics.returns.hand-to-seller', 'Seller Collected It', 'bi-bag-check-fill', null],
                ];
            @endphp
            <h2 class="section-heading" id="buyer-returns"><i class="bi bi-arrow-return-left"></i> Returns from Buyers ({{ $returnCount }})</h2>

            @if($returnCount === 0)
                <div class="empty">
                    <p>No returned items to handle right now.</p>
                </div>
            @endif

            @foreach($returnSteps as $queue => [$stepLabel, $stepRoute, $stepButton, $stepIcon, $stepHint])
                @foreach($buyerReturns[$queue] as $return)
                    <div class="app-card">
                        <div class="app-card-top">
                            <div>
                                <div class="app-name">Return #{{ $return->id }} · {{ $return->product_name ?? 'Item' }}{{ $return->quantity ? ' × ' . $return->quantity : '' }}</div>
                                <div class="app-email">Order #{{ $return->order_id }} · Buyer {{ $return->buyer_name ?? '' }}</div>
                            </div>
                            <span class="status-badge status-pending">{{ $stepLabel }}</span>
                        </div>

                        <div class="parcel-meta">
                            <i class="bi bi-signpost-2"></i>
                            {{ $centerNames[$return->destination_center_id] ?? 'Buyer\'s center' }} → {{ $centerNames[$return->origin_center_id] ?? 'Seller\'s center' }}
                            · Reason: {{ $return->reason }}
                        </div>

                        @if($stepHint)
                            <div class="remarks-note">{{ str_replace(':id', $return->id, $stepHint) }}</div>
                        @endif

                        <div class="app-actions">
                            <form method="POST" action="{{ route($stepRoute, $return->id) }}">
                                @csrf
                                <button type="submit" class="approve-btn"><i class="bi {{ $stepIcon }}"></i> {{ $stepButton }}</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endforeach
            </div>

            <div class="pq" data-pq="{{ $sectionTab['ready-to-collect'] }}" data-count="{{ $sectionCount['ready-to-collect'] }}" @if ($pqHidden('ready-to-collect')) hidden @endif>
            {{-- 5. PICK-UP ORDERS WAITING FOR THEIR BUYER --}}
            <h2 class="section-heading" id="ready-to-collect"><i class="bi bi-person-check-fill"></i> Ready to Collect ({{ count($readyToCollect) }})</h2>

            @forelse ($readyToCollect as $order)

                <div class="app-card">
                    <div class="app-card-top">
                        <div>
                            <div class="app-name">Order #{{ $order->id }}</div>
                            <div class="app-email">{{ $order->shipping_name }} — {{ $order->shipping_phone }}</div>
                        </div>
                        <x-status-pill :status="$order->status" />
                    </div>

                    @include('pages.logistics.partials.parcel-route', ['order' => $order])

                    <div class="parcel-meta">
                        <i class="bi bi-clock"></i> Waiting {{ \Carbon\Carbon::parse($order->updated_at)->diffForHumans(null, true) }}
                        @if (\App\Support\CodPolicy::isCod($order->payment_method))
                            · <strong>collect ₱{{ number_format((float) $order->total_amount, 2) }} in cash</strong>
                        @else
                            · already paid ({{ $order->payment_method }})
                        @endif
                    </div>

                    <div class="app-actions">
                        <form method="POST" action="{{ route('logistics.parcels.hand-to-buyer', $order->id) }}" class="pickup-form" data-confirm="Hand order #{{ $order->id }} to {{ $order->shipping_name }}?{{ \App\Support\CodPolicy::isCod($order->payment_method) ? ' Collect the cash first.' : '' }}" data-confirm-ok="Handed Over">
                            @csrf
                            @if (!empty($order->pickup_code))
                                <label class="pickup-code" for="pickup-code-{{ $order->id }}">
                                    <span>Buyer's pickup code</span>
                                    <input type="text" id="pickup-code-{{ $order->id }}" name="pickup_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="6 digits" autocomplete="off" required>
                                </label>
                            @endif
                            <button type="submit" class="approve-btn"><i class="bi bi-bag-check-fill"></i> Buyer Collected It</button>
                        </form>
                        <form method="POST" action="{{ route('logistics.parcels.return-to-seller', $order->id) }}" data-confirm="Buyer never came for order #{{ $order->id }}? Send it back to the seller." data-confirm-ok="Return to Seller" data-confirm-danger>
                            @csrf
                            <button type="submit" class="reject-btn"><i class="bi bi-arrow-return-left"></i> Not Collected — Return</button>
                        </form>
                    </div>
                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-person-check"></i></div>
                    <h3>{{ $search !== '' ? 'No matches here' : 'No Pick-ups Waiting' }}</h3>
                    <p>Orders whose buyer chose to pick up here wait in this list until they come.</p>
                </div>

            @endforelse

            </div>

            <div class="pq" data-pq="{{ $sectionTab['returns'] }}" data-count="{{ $sectionCount['returns'] }}" @if ($pqHidden('returns')) hidden @endif>
            {{-- 6. RETURNS TO THIS CENTER'S SELLERS --}}
            <h2 class="section-heading" id="returns"><i class="bi bi-arrow-return-left"></i> Returns ({{ count($incomingReturns) + count($returnReady) }})</h2>

            @foreach ($incomingReturns as $order)

                <div class="app-card">
                    <div class="app-card-top">
                        <div>
                            <div class="app-name">Order #{{ $order->id }} · coming back</div>
                            <div class="app-email">For the seller — {{ $order->failure_reason ?: 'returned' }}</div>
                        </div>
                        <x-status-pill :status="$order->status" />
                    </div>

                    @include('pages.logistics.partials.parcel-route', ['order' => $order])

                    <div class="app-actions">
                        <form method="POST" action="{{ route('logistics.parcels.confirm-return', $order->id) }}">
                            @csrf
                            <button type="submit" class="approve-btn"><i class="bi bi-check-circle-fill"></i> Confirm Return Arrived</button>
                        </form>
                    </div>
                </div>

            @endforeach

            @foreach ($returnReady as $order)

                @php $returnSeller = \Illuminate\Support\Facades\DB::table('order_items')->join('users', 'users.id', '=', 'order_items.seller_id')->where('order_items.order_id', $order->id)->value('users.name'); @endphp

                <div class="app-card">
                    <div class="app-card-top">
                        <div>
                            <div class="app-name">Order #{{ $order->id }} · waiting for the seller</div>
                            <div class="app-email">{{ $returnSeller ?: 'Seller' }} collects it here</div>
                        </div>
                        <x-status-pill :status="$order->status" />
                    </div>

                    <div class="app-actions">
                        <form method="POST" action="{{ route('logistics.parcels.hand-back', $order->id) }}" data-confirm="Hand returned order #{{ $order->id }} back to {{ $returnSeller ?: 'the seller' }}?" data-confirm-ok="Handed Back">
                            @csrf
                            <button type="submit" class="approve-btn"><i class="bi bi-shop"></i> Seller Took It Back</button>
                        </form>
                    </div>
                </div>

            @endforeach

            @if (count($incomingReturns) + count($returnReady) === 0)
                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-arrow-return-left"></i></div>
                    <h3>{{ $search !== '' ? 'No matches here' : 'No Returns' }}</h3>
                    <p>Parcels going back to sellers who drop off here show up until the seller collects them.</p>
                </div>
            @endif

            </div>

            <div class="pq" data-pq="{{ $sectionTab['cod'] }}" data-count="{{ $sectionCount['cod'] }}" @if ($pqHidden('cod')) hidden @endif>
            {{-- 7. CASH ON DELIVERY MONEY FROM RIDERS --}}
            <h2 class="section-heading" id="cod"><i class="bi bi-cash-stack"></i> COD Cash From Riders ({{ count($codToReceive) }})</h2>

            @forelse ($codToReceive as $cash)

                <div class="app-card compact">
                    <div class="app-card-top">
                        <div>
                            <div class="app-name">{{ $cash->rider_name }}</div>
                            <div class="app-email">{{ $cash->parcels }} COD delivery(ies) · holding ₱{{ number_format((float) $cash->amount, 2) }}</div>
                        </div>
                        <form method="POST" action="{{ route('logistics.riders.receive-cod', $cash->rider_id) }}" data-confirm="Received ₱{{ number_format((float) $cash->amount, 2) }} in cash from {{ $cash->rider_name }}? This releases the sellers' payouts." data-confirm-ok="Cash Received">
                            @csrf
                            <button type="submit" class="approve-btn"><i class="bi bi-cash-coin"></i> Receive ₱{{ number_format((float) $cash->amount, 2) }}</button>
                        </form>
                    </div>
                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-cash-stack"></i></div>
                    <h3>No Cash to Collect</h3>
                    <p>When riders deliver Cash on Delivery orders, the money they hold shows up here until they hand it in.</p>
                </div>

            @endforelse
            </div>

            </div>

        </div>

    </main>

    <script>
        // Work tabs: show one tab's lists; remember it in the address (?tab=)
        // so a button press comes back to the same tab.
        (function () {
            function show(tab) {
                var root = document.getElementById('liveResults');
                if (!root) return;
                var groups = root.querySelectorAll('.pq[data-pq="' + tab + '"]');
                var total = 0;
                groups.forEach(function (g) { total += Number(g.dataset.count || 0); });

                root.querySelectorAll('.pq').forEach(function (g) {
                    g.hidden = g.dataset.pq !== tab || total === 0 || Number(g.dataset.count || 0) === 0;
                });
                root.querySelectorAll('[data-pq-head]').forEach(function (h) { h.hidden = h.dataset.pqHead !== tab; });
                root.querySelectorAll('[data-pq-empty]').forEach(function (e) { e.hidden = e.dataset.pqEmpty !== tab || total > 0; });
                root.querySelectorAll('.pq-tabs [data-pq-tab], .pq-more [data-pq-tab]').forEach(function (b) {
                    var on = b.dataset.pqTab === tab;
                    b.classList.toggle('is-on', on);
                    if (b.getAttribute('role') === 'tab') b.setAttribute('aria-selected', on ? 'true' : 'false');
                });

                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('tab', tab);
                    history.replaceState(null, '', url);
                } catch (e) {}
            }

            document.addEventListener('click', function (e) {
                var b = e.target.closest('[data-pq-tab]');
                if (!b) return;
                show(b.dataset.pqTab);
                var tabs = document.querySelector('.pq-tabs');
                if (tabs && b.closest('.pq-empty')) tabs.scrollIntoView({ block: 'nearest' });
            });
        })();
    </script>

    @if ($canScan)
    <script>
        // Scan Station: open / close, the IN / OUT switch, the camera, and Undo.
        // A USB scanner types the waybill into the box and presses Enter itself.
        (function () {
            var station = document.getElementById('scanStation');
            var form = document.getElementById('parcelScanForm');
            var code = document.getElementById('scanCode');
            var modeInput = document.getElementById('scanMode');
            if (!station || !form || !code) return;

            var OPEN = 'bbScanStation', MODE = 'bbScanMode', CAM = 'bbScanCamera';
            function get(k) { try { return sessionStorage.getItem(k); } catch (e) { return null; } }
            function set(k, v) { try { v === null ? sessionStorage.removeItem(k) : sessionStorage.setItem(k, v); } catch (e) {} }

            var toggles = document.querySelectorAll('[data-scan-toggle]');
            var openBtn = document.querySelector('.ss-open-btn');

            function show(open) {
                station.hidden = !open;
                set(OPEN, open ? '1' : null);
                if (openBtn) {
                    openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    openBtn.querySelector('span').textContent = open ? 'Close Scan Station' : 'Open Scan Station';
                }
                if (open) code.focus(); else closeCam();
            }
            toggles.forEach(function (b) { b.addEventListener('click', function () { show(station.hidden); }); });

            // IN / OUT
            var modeBtns = station.querySelectorAll('[data-scan-mode]');
            function setMode(m) {
                modeInput.value = m;
                set(MODE, m);
                modeBtns.forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.scanMode === m ? 'true' : 'false'); });
                code.focus();
            }
            modeBtns.forEach(function (b) { b.addEventListener('click', function () { setMode(b.dataset.scanMode); }); });
            setMode(get(MODE) === 'out' ? 'out' : 'in');

            // Undo: a few seconds, counted down on the button.
            var undo = station.querySelector('[data-scan-undo]');
            if (undo) {
                var left = parseInt(undo.dataset.seconds, 10) || 10;
                var label = undo.querySelector('[data-scan-undo-left]');
                var tick = setInterval(function () {
                    left -= 1;
                    if (label) label.textContent = left;
                    if (left <= 0) { clearInterval(tick); undo.remove(); }
                }, 1000);
            }

            // Camera: reads the waybill's QR code and sends it; it opens again
            // after the page reloads so parcels can be scanned one after another.
            var cam = document.getElementById('scanCam');
            var camBtn = station.querySelector('[data-scan-cam]');
            var reader = null;

            function loadLib(done) {
                if (window.Html5Qrcode) return done();
                var s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js';
                s.onload = done;
                s.onerror = function () { closeCam(); if (window.bbAlert) bbAlert('The camera scanner could not load. Use the USB scanner or type the waybill no.'); };
                document.head.appendChild(s);
            }

            function openCam() {
                cam.hidden = false;
                set(CAM, '1');
                camBtn.querySelector('span').textContent = 'Close camera';
                loadLib(function () {
                    reader = new Html5Qrcode('scanCamView');
                    reader.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 200, height: 200 } }, function (text) {
                        reader.stop().catch(function () {}).finally(function () { code.value = text; form.submit(); });
                    }).catch(function () {
                        closeCam();
                        if (window.bbAlert) bbAlert('Allow the camera for boombuy.store to scan, or use the USB scanner / type the waybill no.');
                    });
                });
            }

            function closeCam() {
                if (!cam || cam.hidden) return;
                set(CAM, null);
                if (reader) { reader.stop().catch(function () {}); reader = null; }
                cam.hidden = true;
                camBtn.querySelector('span').textContent = 'Camera';
            }

            camBtn.addEventListener('click', function () { cam.hidden ? openCam() : (closeCam(), code.focus()); });

            // Open again after a scan, or if it was left open.
            if (station.dataset.opened === '1' || get(OPEN) === '1') {
                show(true);
                if (get(CAM) === '1') openCam();
            }
        })();
    </script>
    @endif


    @include('partials.live-search')
    @include('partials.pwa-register')

</body>
</html>
