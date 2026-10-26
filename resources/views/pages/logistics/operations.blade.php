<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Shipments & Dispatch — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/logistics-ops.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="ops" :user="$user" />

    <main class="main-content">
        <div class="container ops">

            <div class="page-header">
                <div>
                    <h1>Shipments &amp; Dispatch</h1>
                    <p>Counts, the shipment list, pickups to assign, hub scans and returns — for your Sorting Center.</p>
                </div>
            </div>

            @if (session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            {{-- L1: counts (each one opens the matching list) --}}
            <form method="GET" action="{{ route('logistics.ops') }}" class="ops-filter">
                <label>From <input type="date" name="from" value="{{ $filters['from'] }}"></label>
                <label>To <input type="date" name="to" value="{{ $filters['to'] }}"></label>
                <label class="grow">Search <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Tracking number, order ID or buyer"></label>
                @if($filters['status'])
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                @endif
                <button type="submit" class="btn-main">Apply</button>
            </form>

            <div class="ops-counts">
                @foreach(\App\Support\Shipments::BUCKETS as $key => $bucket)
                    <a href="{{ route('logistics.ops', array_filter(['status' => $key, 'from' => $filters['from'], 'to' => $filters['to']])) }}" class="ops-count {{ $filters['status'] === $key ? 'active' : '' }}" data-count="{{ $key }}">
                        <span>{{ $bucket['label'] }}</span>
                        <strong>{{ $counts[$key] }}</strong>
                    </a>
                @endforeach
            </div>

            <section class="ops-card">
                <h2>Shipments <small>{{ $shipments->count() }}</small></h2>
                @if($shipments->isEmpty())
                    <p class="muted">No shipments match.</p>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Tracking</th><th>Order</th><th>Buyer</th><th>Status</th><th>Placed</th><th>Fix</th></tr></thead>
                            <tbody>
                                @foreach($shipments as $shipment)
                                    <tr data-shipment="{{ $shipment->id }}">
                                        <td>{{ $shipment->tracking_number }}</td>
                                        <td>#{{ $shipment->id }}</td>
                                        <td>{{ $shipment->shipping_name }}</td>
                                        <td>
                                            {{ $shipment->status_label }}
                                            @if(!empty($shipment->delivered_at))
                                                {{-- Where and when the rider's phone was at the handover (M13). --}}
                                                <small class="muted" style="display:block">
                                                    {{ \Illuminate\Support\Carbon::parse($shipment->delivered_at)->format('M d, g:i A') }}
                                                    @if($shipment->delivered_lat !== null)
                                                        · <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $shipment->delivered_lat }},{{ $shipment->delivered_lng }}" target="_blank" rel="noopener" data-delivered-gps><i class="bi bi-geo-alt"></i> {{ $shipment->delivered_lat }}, {{ $shipment->delivered_lng }}</a>
                                                    @endif
                                                </small>
                                            @endif
                                        </td>
                                        <td>{{ \Illuminate\Support\Carbon::parse($shipment->created_at)->format('M d, Y') }}</td>
                                        <td>
                                            <details>
                                                <summary>Override / exception</summary>
                                                <form method="POST" action="{{ route('logistics.shipments.override', $shipment->id) }}">
                                                    @csrf
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
                                                    <input type="text" name="reason" minlength="10" maxlength="300" placeholder="Why it is lost" required>
                                                    <button type="submit">Mark Lost</button>
                                                </form>
                                                <form method="POST" action="{{ route('logistics.shipments.damaged', $shipment->id) }}" enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="text" name="reason" minlength="10" maxlength="300" placeholder="What is damaged" required>
                                                    <input type="file" name="photo" accept="image/jpeg,image/png" required>
                                                    <button type="submit">Mark Damaged</button>
                                                </form>
                                            </details>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- L3: pickups to dispatch --}}
            <section class="ops-card">
                <h2>Pickups to assign <small>{{ $readyForPickup->count() }}</small></h2>
                <p class="muted">Auto-assign offers the job to your nearest Online rider ({{ \App\Services\DispatchService::OFFER_SECONDS }} seconds each, up to {{ \App\Services\DispatchService::MAX_TRIES }} riders). Riders: at most {{ \App\Support\RiderJobs::ACTIVE_LIMIT }} active jobs.</p>
                @if($readyForPickup->isNotEmpty())
                    <form method="POST" action="{{ route('logistics.dispatch.bulk') }}" id="bulkAssign" class="ops-inline">
                        @csrf
                        <select name="rider_id" required>
                            <option value="">Assign selected to…</option>
                            @foreach($assignableRiders as $rider)
                                <option value="{{ $rider->id }}">{{ $rider->name }} ({{ $rider->active_jobs }}/{{ \App\Support\RiderJobs::ACTIVE_LIMIT }})</option>
                            @endforeach
                        </select>
                        <button type="submit">Assign selected</button>
                    </form>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th></th><th>Tracking</th><th>Offer</th><th>Auto</th><th>Manual</th></tr></thead>
                            <tbody>
                                @foreach($readyForPickup as $job)
                                    <tr>
                                        <td><input type="checkbox" name="order_ids[]" value="{{ $job->id }}" form="bulkAssign" aria-label="Select {{ \App\Support\Waybill::number($job->id) }}"></td>
                                        <td>{{ \App\Support\Waybill::number($job->id) }}{{ $job->dispatch_unassigned_at ? ' · Unassigned' : '' }}</td>
                                        <td>{{ ($offer = $offers->get($job->id)) ? 'Offered to ' . \Illuminate\Support\Facades\DB::table('users')->where('id', $offer->rider_id)->value('name') : '—' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('logistics.dispatch.auto', $job->id) }}">@csrf<button type="submit">Auto-assign</button></form>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('logistics.dispatch.assign', $job->id) }}" class="ops-inline">
                                                @csrf
                                                <select name="rider_id" required>
                                                    <option value="">Rider…</option>
                                                    @foreach($assignableRiders as $rider)
                                                        <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit">Assign</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h3>Unassigned queue <small>{{ $unassigned->count() }}</small></h3>
                <p class="muted">{{ $unassigned->isEmpty() ? 'Nothing waiting.' : 'No rider took these: ' . $unassigned->map(fn ($o) => \App\Support\Waybill::number($o->id))->implode(', ') . '.' }}</p>

                <h3>Assigned, not yet picked up <small>{{ $assignedPickups->count() }}</small></h3>
                @foreach($assignedPickups as $job)
                    <form method="POST" action="{{ route('logistics.dispatch.reassign', $job->id) }}" class="ops-inline">
                        @csrf
                        <span>{{ \App\Support\Waybill::number($job->id) }} · {{ \Illuminate\Support\Facades\DB::table('users')->where('id', $job->rider_id)->value('name') }}</span>
                        <select name="rider_id" required>
                            <option value="">Reassign to…</option>
                            @foreach($assignableRiders as $rider)
                                <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit">Reassign</button>
                    </form>
                @endforeach
            </section>

            {{-- L4: hub scans --}}
            <section class="ops-card">
                <h2>Hub scan</h2>
                <form method="POST" action="{{ route('logistics.hub.scan') }}" class="ops-inline">
                    @csrf
                    <input type="text" name="code" placeholder="Scan or type the tracking number" required autofocus>
                    <select name="stage">
                        <option value="inbound">Inbound (received)</option>
                        <option value="outbound">Outbound (to rider)</option>
                    </select>
                    <select name="rider_id">
                        <option value="">Rider (outbound)</option>
                        @foreach($assignableRiders as $rider)
                            <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit">Scan</button>
                </form>
                <h3>Sorted by zone</h3>
                @forelse($zoneLists as $zone => $ids)
                    <p data-zone="{{ $zone }}"><strong>{{ $zone }}</strong>: {{ collect($ids)->map(fn ($id) => \App\Support\Waybill::number($id))->implode(', ') }}</p>
                @empty
                    <p class="muted">No sorted parcels waiting.</p>
                @endforelse
            </section>

            {{-- L6: Return to Sender --}}
            <section class="ops-card">
                <h2>Return to Sender <small>{{ $rtsQueue->count() }}</small></h2>
                @forelse($rtsQueue as $parcel)
                    <p data-rts="{{ $parcel->id }}"><strong>{{ $parcel->tracking_number }}</strong> · {{ $parcel->status_label }} · {{ implode(' / ', $parcel->failure_reasons) }}</p>
                @empty
                    <p class="muted">No parcels going back.</p>
                @endforelse
                @foreach($returnReady as $parcel)
                    <form method="POST" action="{{ route('logistics.shipments.return-rider', $parcel->id) }}" class="ops-inline">
                        @csrf
                        <span>{{ \App\Support\Waybill::number($parcel->id) }} — waiting to go back to the seller</span>
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

            {{-- Riders at a glance --}}
            <section class="ops-card">
                <h2>Riders</h2>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Rider</th><th>Status</th><th>Shift</th><th>Location</th><th>Active jobs</th><th>Delivered today</th></tr></thead>
                        <tbody>
                            @foreach($riders as $rider)
                                <tr data-rider="{{ $rider->id }}">
                                    <td>{{ $rider->name }}</td>
                                    <td>{{ $rider->status }}</td>
                                    <td>{{ $rider->is_online ? 'Online' : 'Offline' }}</td>
                                    <td>
                                        @if($rider->location_at && $rider->location_lat !== null)
                                            <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $rider->location_lat }},{{ $rider->location_lng }}" target="_blank" rel="noopener">
                                                <i class="bi bi-geo-alt"></i> {{ \Illuminate\Support\Carbon::parse($rider->location_at)->diffForHumans() }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $rider->active_jobs }}/{{ \App\Support\RiderJobs::ACTIVE_LIMIT }}</td>
                                    <td>{{ $rider->delivered_today }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>

</body>
</html>
