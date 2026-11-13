<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Shipments & Dispatch — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/logistics-ops.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="ops" :user="$user" />

    @php
        $limit = \App\Support\RiderJobs::ACTIVE_LIMIT;
        $bucketOf = function (string $status) {
            foreach (\App\Support\Shipments::BUCKETS as $key => $b) {
                if (in_array($status, $b['statuses'], true)) return $key;
            }
            return 'other';
        };
        $bucketIcon = ['pending' => 'bi-box-seam', 'in_transit' => 'bi-truck', 'delivered' => 'bi-check2-circle', 'failed' => 'bi-exclamation-triangle'];
        $onlineCount = $riders->where('is_online', true)->count();
        $riderName = fn ($id) => $id ? \Illuminate\Support\Facades\DB::table('users')->where('id', $id)->value('name') : null;

        // Which tab opens first: the shipment list when it was filtered, else what needs doing.
        $filtered = $filters['status'] || $filters['q'] || $filters['from'] || $filters['to'];
        $firstTab = $filtered ? 'shipments' : ($readyForPickup->isNotEmpty() || $unassigned->isNotEmpty() ? 'dispatch' : 'shipments');
        $tabs = [
            'shipments' => ['Shipments', 'bi-list-ul', $shipments->count()],
            'dispatch' => ['Pickups to assign', 'bi-send', $readyForPickup->count()],
            'hub' => ['Hub scan', 'bi-upc-scan', null],
            'returns' => ['Return to Sender', 'bi-arrow-return-left', $rtsQueue->count()],
            'riders' => ['Riders', 'bi-bicycle', $onlineCount . '/' . $riders->count()],
        ];
    @endphp

    <main class="main-content">
        <div class="container ops">

            <div class="page-header">
                <h1>Shipments &amp; Dispatch</h1>
                <p>Every parcel of your Sorting Center at a glance, the pickups waiting for a rider, hub scans, returns and your riders.</p>
                <p class="center-badge">
                    <i class="bi bi-building"></i>
                    @if ($centerName)
                        {{ $centerName }}
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

            {{-- L1: counts — each one filters the shipment list --}}
            <div class="ops-counts">
                @foreach(\App\Support\Shipments::BUCKETS as $key => $bucket)
                    <a href="{{ route('logistics.ops', array_filter(['status' => $filters['status'] === $key ? null : $key, 'from' => $filters['from'], 'to' => $filters['to']])) }}#shipments"
                       class="ops-count is-{{ $key }} {{ $filters['status'] === $key ? 'active' : '' }}" data-count="{{ $key }}">
                        <span class="ops-count-ic"><i class="bi {{ $bucketIcon[$key] }}"></i></span>
                        <span class="ops-count-text">
                            <span>{{ $bucket['label'] }}</span>
                            <strong>{{ $counts[$key] }}</strong>
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- Tabs (without JavaScript every section simply shows) --}}
            <nav class="ops-tabs" role="tablist" data-ops-tabs data-first="{{ $firstTab }}">
                @foreach($tabs as $key => [$label, $icon, $n])
                    <a href="#{{ $key }}" class="ops-tab" role="tab" data-ops-tab="{{ $key }}">
                        <i class="bi {{ $icon }}"></i> {{ $label }}
                        @if($n !== null)<span class="ops-tab-n">{{ $n }}</span>@endif
                    </a>
                @endforeach
            </nav>

            {{-- ============ SHIPMENTS ============ --}}
            <section class="ops-card" id="shipments" data-ops-panel="shipments">
                <div class="ops-card-head">
                    <div>
                        <h2><i class="bi bi-list-ul"></i> Shipments <small>{{ $shipments->count() }}</small></h2>
                        <p class="muted">
                            @if($filters['status'])
                                Showing <strong>{{ \App\Support\Shipments::BUCKETS[$filters['status']]['label'] ?? $filters['status'] }}</strong> ·
                                <a href="{{ route('logistics.ops', array_filter(['from' => $filters['from'], 'to' => $filters['to'], 'q' => $filters['q']])) }}#shipments">show all</a>
                            @else
                                All parcels of your center, newest first. Tap a count above to filter.
                            @endif
                        </p>
                    </div>
                </div>

                <form method="GET" action="{{ route('logistics.ops') }}#shipments" class="ops-filter">
                    <label class="grow ops-search">
                        <span class="sr-only">Search</span>
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Tracking number, order ID or buyer">
                    </label>
                    <label>From <input type="date" name="from" value="{{ $filters['from'] }}"></label>
                    <label>To <input type="date" name="to" value="{{ $filters['to'] }}"></label>
                    @if($filters['status'])
                        <input type="hidden" name="status" value="{{ $filters['status'] }}">
                    @endif
                    <button type="submit" class="btn-main">Apply</button>
                </form>

                @if($shipments->isEmpty())
                    <div class="ops-empty"><i class="bi bi-inbox"></i><p>No shipments match.</p></div>
                @else
                    <div class="table-wrap">
                        <table class="ops-table">
                            <thead><tr><th>Tracking</th><th>Order</th><th>Buyer</th><th>Status</th><th>Placed</th><th></th></tr></thead>
                            <tbody>
                                @foreach($shipments as $shipment)
                                    <tr data-shipment="{{ $shipment->id }}">
                                        <td><span class="wb-chip"><i class="bi bi-upc"></i> {{ $shipment->tracking_number }}</span></td>
                                        <td class="strong">#{{ $shipment->id }}</td>
                                        <td>{{ $shipment->shipping_name }}</td>
                                        <td>
                                            <span class="ops-pill is-{{ $bucketOf($shipment->status) }}">{{ $shipment->status_label }}</span>
                                            @if(!empty($shipment->delivered_at))
                                                {{-- Where and when the rider's phone was at the handover (M13). --}}
                                                <small class="muted ops-sub">
                                                    {{ \Illuminate\Support\Carbon::parse($shipment->delivered_at)->format('M d, g:i A') }}
                                                    @if($shipment->delivered_lat !== null)
                                                        · <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $shipment->delivered_lat }},{{ $shipment->delivered_lng }}" target="_blank" rel="noopener" data-delivered-gps><i class="bi bi-geo-alt"></i> {{ $shipment->delivered_lat }}, {{ $shipment->delivered_lng }}</a>
                                                    @endif
                                                </small>
                                            @endif
                                        </td>
                                        <td class="muted">{{ \Illuminate\Support\Carbon::parse($shipment->created_at)->format('M d, Y') }}</td>
                                        <td class="ops-fix-cell">
                                            <details class="ops-fix">
                                                <summary><i class="bi bi-wrench-adjustable"></i> Fix</summary>
                                                <div class="ops-fix-panel">
                                                    <form method="POST" action="{{ route('logistics.shipments.override', $shipment->id) }}">
                                                        @csrf
                                                        <strong>Set status</strong>
                                                        <select name="status">
                                                            @foreach(array_keys(\App\Services\ShipmentExceptionService::OVERRIDES) as $target)
                                                                <option>{{ $target }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="text" name="reason" minlength="10" maxlength="300" placeholder="Reason (10–300 characters)" required>
                                                        <button type="submit">Set status</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('logistics.shipments.lost', $shipment->id) }}">
                                                        @csrf
                                                        <strong>Lost</strong>
                                                        <input type="text" name="reason" minlength="10" maxlength="300" placeholder="Why it is lost" required>
                                                        <button type="submit" class="is-danger">Mark Lost</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('logistics.shipments.damaged', $shipment->id) }}" enctype="multipart/form-data">
                                                        @csrf
                                                        <strong>Damaged</strong>
                                                        <input type="text" name="reason" minlength="10" maxlength="300" placeholder="What is damaged" required>
                                                        <input type="file" name="photo" accept="image/jpeg,image/png" required>
                                                        <button type="submit" class="is-danger">Mark Damaged</button>
                                                    </form>
                                                </div>
                                            </details>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- ============ L3: PICKUPS TO ASSIGN ============ --}}
            <section class="ops-card" id="dispatch" data-ops-panel="dispatch">
                <div class="ops-card-head">
                    <div>
                        <h2><i class="bi bi-send"></i> Pickups to assign <small>{{ $readyForPickup->count() }}</small></h2>
                        <p class="muted">Packed parcels waiting at the seller for a pickup rider.</p>
                    </div>
                </div>

                <div class="ops-hint">
                    <i class="bi bi-lightning-charge-fill"></i>
                    <span><strong>Auto-assign</strong> offers the job to your nearest Online rider ({{ \App\Services\DispatchService::OFFER_SECONDS }} seconds each, up to {{ \App\Services\DispatchService::MAX_TRIES }} riders). A rider has at most {{ $limit }} active jobs.</span>
                </div>

                @if($readyForPickup->isEmpty())
                    <div class="ops-empty"><i class="bi bi-check2-all"></i><p>No pickups waiting for a rider.</p></div>
                @else
                    <form method="POST" action="{{ route('logistics.dispatch.bulk') }}" id="bulkAssign" class="ops-bulk">
                        @csrf
                        <span><i class="bi bi-check2-square"></i> Tick parcels, then give them all to one rider:</span>
                        <select name="rider_id" required>
                            <option value="">Assign selected to…</option>
                            @foreach($assignableRiders as $rider)
                                <option value="{{ $rider->id }}">{{ $rider->name }} ({{ $rider->active_jobs }}/{{ $limit }})</option>
                            @endforeach
                        </select>
                        <button type="submit">Assign selected</button>
                    </form>

                    <div class="ops-jobs">
                        @foreach($readyForPickup as $job)
                            @php $offer = $offers->get($job->id); @endphp
                            <div class="ops-job">
                                <label class="ops-job-pick">
                                    <input type="checkbox" name="order_ids[]" value="{{ $job->id }}" form="bulkAssign" aria-label="Select {{ \App\Support\Waybill::number($job->id) }}">
                                </label>
                                <div class="ops-job-main">
                                    <div class="ops-job-top">
                                        <span class="wb-chip"><i class="bi bi-upc"></i> {{ \App\Support\Waybill::number($job->id) }}</span>
                                        <span class="strong">Order #{{ $job->id }}</span>
                                        @if($job->dispatch_unassigned_at)
                                            <span class="ops-pill is-failed">Unassigned</span>
                                        @elseif($offer)
                                            <span class="ops-pill is-pending"><i class="bi bi-hourglass-split"></i> Offered to {{ $riderName($offer->rider_id) }}</span>
                                        @else
                                            <span class="ops-pill is-other">Waiting</span>
                                        @endif
                                    </div>
                                    <p class="muted"><i class="bi bi-geo-alt"></i> To {{ $job->shipping_name }} — {{ \Illuminate\Support\Str::limit($job->shipping_address, 70) }}</p>
                                </div>
                                <div class="ops-job-actions">
                                    <form method="POST" action="{{ route('logistics.dispatch.auto', $job->id) }}">@csrf<button type="submit"><i class="bi bi-lightning-charge-fill"></i> Auto-assign</button></form>
                                    <form method="POST" action="{{ route('logistics.dispatch.assign', $job->id) }}" class="ops-inline">
                                        @csrf
                                        <select name="rider_id" required>
                                            <option value="">Rider…</option>
                                            @foreach($assignableRiders as $rider)
                                                <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="is-ghost">Assign</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="ops-sub-grid">
                    <div class="ops-subcard">
                        <h3><i class="bi bi-hourglass-bottom"></i> Unassigned queue <small>{{ $unassigned->count() }}</small></h3>
                        @if($unassigned->isEmpty())
                            <p class="muted">Nothing waiting.</p>
                        @else
                            <p class="muted">No rider took these — assign one above:</p>
                            <div class="ops-chips">
                                @foreach($unassigned as $o)
                                    <span class="wb-chip is-light">{{ \App\Support\Waybill::number($o->id) }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="ops-subcard">
                        <h3><i class="bi bi-person-check"></i> Assigned, not yet picked up <small>{{ $assignedPickups->count() }}</small></h3>
                        @forelse($assignedPickups as $job)
                            <form method="POST" action="{{ route('logistics.dispatch.reassign', $job->id) }}" class="ops-inline ops-row">
                                @csrf
                                <span><span class="wb-chip is-light">{{ \App\Support\Waybill::number($job->id) }}</span> {{ $riderName($job->rider_id) }}</span>
                                <select name="rider_id" required>
                                    <option value="">Reassign to…</option>
                                    @foreach($assignableRiders as $rider)
                                        <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="is-ghost">Reassign</button>
                            </form>
                        @empty
                            <p class="muted">None.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            {{-- ============ L4: HUB SCAN ============ --}}
            <section class="ops-card" id="hub" data-ops-panel="hub">
                <div class="ops-card-head">
                    <div>
                        <h2><i class="bi bi-upc-scan"></i> Hub scan</h2>
                        <p class="muted"><strong>Inbound</strong>: the parcel arrived at the hub. <strong>Outbound</strong>: sorted and handed to a delivery rider.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logistics.hub.scan') }}" class="ops-scan">
                    @csrf
                    <label class="ops-scan-input">
                        <i class="bi bi-upc-scan"></i>
                        <input type="text" name="code" placeholder="Scan or type the tracking number" required>
                    </label>
                    <div class="ops-seg" role="radiogroup" aria-label="Scan type">
                        <label><input type="radio" name="stage" value="inbound" checked> <span><i class="bi bi-box-arrow-in-down"></i> Inbound</span></label>
                        <label><input type="radio" name="stage" value="outbound"> <span><i class="bi bi-box-arrow-up"></i> Outbound</span></label>
                    </div>
                    <select name="rider_id" aria-label="Rider for outbound">
                        <option value="">Rider (outbound)</option>
                        @foreach($assignableRiders as $rider)
                            <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit"><i class="bi bi-upc-scan"></i> Scan</button>
                </form>

                <h3><i class="bi bi-grid-3x3-gap"></i> Sorted by zone</h3>
                @forelse($zoneLists as $zone => $ids)
                    <div class="ops-zone" data-zone="{{ $zone }}">
                        <strong>{{ $zone }} <small>{{ count($ids) }}</small></strong>
                        <div class="ops-chips">
                            @foreach($ids as $id)
                                <span class="wb-chip is-light">{{ \App\Support\Waybill::number($id) }}</span>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="muted">No sorted parcels waiting.</p>
                @endforelse
            </section>

            {{-- ============ L6: RETURN TO SENDER ============ --}}
            <section class="ops-card" id="returns" data-ops-panel="returns">
                <div class="ops-card-head">
                    <div>
                        <h2><i class="bi bi-arrow-return-left"></i> Return to Sender <small>{{ $rtsQueue->count() }}</small></h2>
                        <p class="muted">Failed deliveries going back to the seller, with every failure reason.</p>
                    </div>
                </div>

                @forelse($rtsQueue as $parcel)
                    <div class="ops-rts" data-rts="{{ $parcel->id }}">
                        <span class="wb-chip"><i class="bi bi-upc"></i> {{ $parcel->tracking_number }}</span>
                        <span class="ops-pill is-failed">{{ $parcel->status_label }}</span>
                        <span class="muted">{{ implode(' / ', $parcel->failure_reasons) ?: 'No reason recorded' }}</span>
                    </div>
                @empty
                    <div class="ops-empty"><i class="bi bi-emoji-smile"></i><p>No parcels going back.</p></div>
                @endforelse

                @foreach($returnReady as $parcel)
                    <form method="POST" action="{{ route('logistics.shipments.return-rider', $parcel->id) }}" class="ops-inline ops-row">
                        @csrf
                        <span><span class="wb-chip is-light">{{ \App\Support\Waybill::number($parcel->id) }}</span> waiting to go back to the seller</span>
                        <select name="rider_id" required>
                            <option value="">Rider…</option>
                            @foreach($assignableRiders as $rider)
                                <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit">Send back with rider</button>
                    </form>
                @endforeach
            </section>

            {{-- ============ RIDERS ============ --}}
            <section class="ops-card" id="riders" data-ops-panel="riders">
                <div class="ops-card-head">
                    <div>
                        <h2><i class="bi bi-bicycle"></i> Riders <small>{{ $onlineCount }} online of {{ $riders->count() }}</small></h2>
                        <p class="muted">Online while their app keeps sending a location. At most {{ $limit }} active jobs each.</p>
                    </div>
                </div>

                @if($riders->isEmpty())
                    <div class="ops-empty"><i class="bi bi-people"></i><p>No riders yet.</p></div>
                @else
                    <div class="ops-riders">
                        {{-- Online riders first, then the busiest. --}}
                        @foreach($riders->sortByDesc(fn ($r) => [(int) $r->is_online, $r->active_jobs]) as $rider)
                            <div class="ops-rider {{ $rider->is_online ? 'is-online' : '' }}" data-rider="{{ $rider->id }}">
                                <div class="ops-rider-top">
                                    <span class="ops-avatar">{{ mb_strtoupper(mb_substr($rider->name, 0, 1)) }}</span>
                                    <div>
                                        <strong>{{ $rider->name }}</strong>
                                        <span class="ops-shift"><i class="bi bi-circle-fill"></i> {{ $rider->is_online ? 'Online' : 'Offline' }}</span>
                                    </div>
                                    <span class="ops-pill {{ $rider->status === 'Active' ? 'is-delivered' : 'is-failed' }}">{{ $rider->status }}</span>
                                </div>
                                <div class="ops-meter" title="{{ $rider->active_jobs }} of {{ $limit }} active jobs">
                                    <span style="width: {{ min(100, round($rider->active_jobs / max(1, $limit) * 100)) }}%"></span>
                                </div>
                                <div class="ops-rider-stats">
                                    <span><b>{{ $rider->active_jobs }}/{{ $limit }}</b> active jobs</span>
                                    <span><b>{{ $rider->delivered_today }}</b> delivered today</span>
                                </div>
                                <div class="ops-rider-loc">
                                    @if($rider->location_at && $rider->location_lat !== null)
                                        <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $rider->location_lat }},{{ $rider->location_lng }}" target="_blank" rel="noopener">
                                            <i class="bi bi-geo-alt"></i> Seen {{ \Illuminate\Support\Carbon::parse($rider->location_at)->diffForHumans() }}
                                        </a>
                                    @else
                                        <span class="muted"><i class="bi bi-geo"></i> No location yet</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

        </div>
    </main>

    <script>
        // One section at a time, like the Parcels page. The tab is kept in the
        // address (#dispatch …) so a form that comes back lands on the same one.
        (function () {
            var nav = document.querySelector('[data-ops-tabs]');
            if (!nav) return;
            var panels = document.querySelectorAll('[data-ops-panel]');
            var KEY = 'bbOpsTab';

            function show(name) {
                var found = false;
                panels.forEach(function (p) { var on = p.dataset.opsPanel === name; p.hidden = !on; found = found || on; });
                if (!found) return show(nav.dataset.first);
                nav.querySelectorAll('[data-ops-tab]').forEach(function (t) {
                    var on = t.dataset.opsTab === name;
                    t.classList.toggle('is-on', on);
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                try { sessionStorage.setItem(KEY, name); } catch (e) {}
            }

            nav.addEventListener('click', function (e) {
                var t = e.target.closest('[data-ops-tab]');
                if (!t) return;
                e.preventDefault();
                history.replaceState(null, '', '#' + t.dataset.opsTab);
                show(t.dataset.opsTab);
            });

            // Forms post back to this page: stay on the tab they came from.
            document.querySelectorAll('[data-ops-panel] form[method="POST"]').forEach(function (f) {
                f.addEventListener('submit', function () {
                    try { sessionStorage.setItem(KEY, f.closest('[data-ops-panel]').dataset.opsPanel); } catch (e) {}
                });
            });

            var saved = null;
            try { saved = sessionStorage.getItem(KEY); } catch (e) {}
            show((location.hash || '').slice(1) || saved || nav.dataset.first);

            // Opening "…#riders" must not jump past the tabs to the section.
            if (location.hash) window.addEventListener('load', function () { window.scrollTo(0, 0); });
        })();
    </script>

</body>
</html>
