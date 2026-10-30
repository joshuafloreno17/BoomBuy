<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Track package — BoomBuy'])

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <link rel="stylesheet" href="{{ vasset('css/views/buyer-track.css') }}">
</head>

<body>

@include('partials.buyer-navbar', ['activeNav' => 'orders'])

<main class="track-page">

    @include('partials.page-head', [
        'title' => 'Track package',
        'note' => 'Order #' . $order->id,
        'crumbs' => ['My Orders' => route('buyer.orders')],
    ])

    <div class="track-layout">

        <div class="track-main">

            {{-- Seller's Sorting Center → buyer's address, on the real map. --}}
            @if($route)
                <section class="track-map-card" aria-label="Route map">
                    <div id="track-map" class="track-map"
                         data-points='@json($route['points'])'
                         data-truck='@json($truckAt)'
                         data-landmarks="{{ json_encode((object) $landmarks) }}"
                         data-road="{{ $route['road'] ? '1' : '0' }}"
                         data-from="{{ $fromLabel }}"
                         data-to="{{ $toLabel }}"></div>
                    <div class="track-map-legend">
                        <span><i class="is-line"></i> {{ $route['road'] ? 'Road route' : 'Route' }}</span>
                        @if($route['km'])
                            <span>~{{ number_format($route['km']) }} km by road</span>
                        @endif
                        <span class="track-status-pill">{{ $order->status }}</span>
                    </div>
                </section>
            @endif

            <div class="track-cards">
                <section class="track-card">
                    @if($eta)
                        <div class="track-kicker">Estimated delivery</div>
                        <div class="track-eta">{{ $eta }}</div>
                    @else
                        <div class="track-kicker">Status</div>
                        <div class="track-eta">{{ $order->status }}</div>
                    @endif

                    @if($codDue !== null)
                        <div class="track-cod">
                            <span class="track-cod-tag">COD</span>
                            Please prepare <strong>₱{{ number_format($codDue, 2) }}</strong> in cash
                        </div>
                    @endif
                </section>

                <section class="track-card">
                    <div class="track-courier">
                        <span class="track-courier-logo" aria-hidden="true">BB</span>
                        <span>BoomBuy Logistics · {{ ($order->fulfillment ?? 'delivery') === 'pickup' ? 'Pick-up' : 'Standard' }}</span>
                    </div>
                    <div class="track-waybill">
                        <span id="track-waybill">{{ $waybill }}</span>
                        <button type="button" class="track-copy" aria-label="Copy tracking number" onclick="copyWaybill(this)">
                            <i class="bi bi-copy"></i>
                        </button>
                    </div>
                    @if($fromLabel && $toLabel)
                        <div class="track-leg">
                            <span>{{ $fromLabel }}</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            <span>{{ $toLabel }}</span>
                        </div>
                    @endif
                </section>
            </div>
        </div>

        <aside class="track-side">
            <section class="track-card">
                <h2 class="track-heading">Tracking history</h2>
                @include('partials.order-timeline', [
                    'steps' => $steps,
                    'plan' => $plan,
                    'eta' => $eta,
                    'mapStops' => $route ? array_keys($landmarks) : [],
                    'proof' => !empty($order->delivery_proof) ? route('orders.delivery-proof', $order->id) : null,
                ])
                <p class="track-tz">Times shown in Philippine time</p>
            </section>

            <section class="track-card">
                <h2 class="track-heading">{{ $items->sum('quantity') }} {{ \Illuminate\Support\Str::plural('item', $items->sum('quantity')) }}</h2>
                <ul class="track-items">
                    @foreach($items as $item)
                        <li>
                            <a href="{{ $item['slug'] ? route('product.details', $item['slug']) : route('buyer.orders') }}" class="track-item">
                                <x-product-thumb :image="$item['image']" :category="$item['category']" size="54" />
                                <span class="track-item-text">
                                    <strong>{{ $item['name'] }}</strong>
                                    <small>
                                        {{ $item['variation'] ? $item['variation'] . ' · ' : '' }}Qty {{ $item['quantity'] }}{{ $item['shop'] ? ' · ' . $item['shop'] : '' }}
                                    </small>
                                </span>
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </aside>

    </div>
</main>

@include('partials.buyer-footer')

@include('partials.pwa-register')

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
    function copyWaybill(button) {
        var text = document.getElementById('track-waybill').textContent.trim();
        navigator.clipboard.writeText(text).then(function () {
            button.innerHTML = '<i class="bi bi-check2"></i>';
            setTimeout(function () { button.innerHTML = '<i class="bi bi-copy"></i>'; }, 1500);
        });
    }

    (function () {
        var box = document.getElementById('track-map');
        if (!box || typeof L === 'undefined') {
            return;
        }

        var points = JSON.parse(box.dataset.points);
        var truck = JSON.parse(box.dataset.truck || 'null');
        var violet = getComputedStyle(document.documentElement).getPropertyValue('--theme-primary').trim() || '#6d4ad1';

        var philippines = L.latLngBounds([3.5, 114.5], [21.8, 128.5]);
        var map = L.map(box, { zoomControl: false, minZoom: 5, maxBounds: philippines, maxBoundsViscosity: 1 });
        L.control.zoom({ position: 'topright' }).addTo(map);
        // OpenStreetMap's credit has to stay (their licence); the "Leaflet" prefix doesn't.
        map.attributionControl.setPrefix(false);

        // A touchpad pinch (or Ctrl + wheel) over the map zooms the map, through
        // Leaflet's own wheel zoom; a plain wheel is stopped before it reaches
        // the map, so it scrolls the page.
        box.parentNode.addEventListener('wheel', function (event) {
            if (!event.ctrlKey) {
                event.stopPropagation();
            }
        }, { capture: true });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var start = points[0];
        var end = points[points.length - 1];

        // Travelled part solid, the rest dashed: split where the truck is.
        var split = truck ? points.findIndex(function (p) { return p[0] === truck[0] && p[1] === truck[1]; }) : 0;
        if (split > 0) {
            L.polyline(points.slice(0, split + 1), { color: violet, weight: 5, opacity: 0.95 }).addTo(map);
        }
        if (points.length > 1) {
            L.polyline(points.slice(Math.max(split, 0)), { color: violet, weight: 4, opacity: 0.8, dashArray: '2 10', lineCap: 'round' }).addTo(map);
        }

        // Sorting Centers on the trip: a round landmark each, filled once the
        // parcel has passed. Stops at the same spot (a center and its branch)
        // share one landmark and one pop-up.
        var landmarks = JSON.parse(box.dataset.landmarks || '{}');
        var spots = [];
        var spotOfStop = {};
        Object.keys(landmarks).forEach(function (rank) {
            var stop = landmarks[rank];
            var spot = spots.find(function (s) { return s.lat === stop.lat && s.lng === stop.lng; });
            if (!spot) {
                spot = { lat: stop.lat, lng: stop.lng, stops: [] };
                spots.push(spot);
            }
            spot.stops.push(stop);
            spotOfStop[rank] = spot;
        });

        // A truck at a Sorting Center is drawn as that landmark, not on top of it.
        var truckSpot = truck ? spotNear(truck, 0.03) : null;

        spots.forEach(function (spot) {
            var tone = spot === truckSpot || spot.stops.some(function (s) { return s.here; }) ? 'is-here'
                : (spot.stops.some(function (s) { return s.reached; }) ? 'is-reached' : 'is-ahead');
            spot.marker = L.marker([spot.lat, spot.lng], {
                icon: L.divIcon({ className: 'track-landmark ' + tone, html: '<i class="bi ' + (spot === truckSpot ? 'bi-truck' : 'bi-building') + '"></i>', iconSize: [32, 32], iconAnchor: [16, 16] }),
                title: spot.stops.map(function (s) { return s.name; }).join(' / '),
                zIndexOffset: 500
            }).addTo(map).bindPopup(spot.stops.map(function (s) {
                var status = s.here ? 'Your parcel is here' + (s.at ? ' · ' + s.at : '')
                    : (s.reached ? 'Parcel passed here' + (s.at ? ' · ' + s.at : '') : 'Not yet reached');
                return '<div class="track-pop">'
                    + '<b>' + escapeHtml(s.name) + '</b>'
                    + '<span>' + escapeHtml(s.address || s.place) + '</span>'
                    + '<em class="' + (s.reached ? 'is-reached' : '') + '">' + escapeHtml(status) + '</em>'
                    + '</div>';
            }).join(''), { className: 'track-popup', offset: [0, -10] });
        });

        // The "From" / "To" labels sit on a landmark at that end when there is one
        // (a seller or buyer in the same town as the center).
        function spotNear(point, tolerance) {
            return spots.find(function (s) { return Math.abs(s.lat - point[0]) < tolerance && Math.abs(s.lng - point[1]) < tolerance; });
        }

        var labelled = [];
        var startSpot = spotNear(start, 0.01);
        labelled[0] = (startSpot ? startSpot.marker : L.circleMarker(start, { radius: 7, color: violet, weight: 3, fillColor: '#fff', fillOpacity: 1 }).addTo(map))
            .bindTooltip('<small>From</small><br><b>' + escapeHtml(box.dataset.from) + '</b>', { permanent: true, direction: 'top', offset: [0, -14], className: 'track-tip' });

        if (points.length > 1) {
            var endSpot = spotNear(end, 0.01);
            labelled[1] = (endSpot && endSpot !== startSpot
                ? endSpot.marker
                : L.marker(end, { icon: L.divIcon({ className: 'track-pin', html: '<i class="bi bi-geo-alt-fill"></i>', iconSize: [30, 30], iconAnchor: [15, 28] }) }).addTo(map))
                .bindTooltip('<small>To</small><br><b>' + escapeHtml(box.dataset.to) + '</b>', { permanent: true, direction: 'top', offset: [0, endSpot && endSpot !== startSpot ? -14 : -24], className: 'track-tip' });
        }

        // A Sorting Center step in the tracking history: show it on the map.
        document.querySelectorAll('[data-map-stop]').forEach(function (button) {
            button.addEventListener('click', function () {
                var spot = spotOfStop[button.dataset.mapStop];
                if (!spot) {
                    return;
                }
                var rect = box.getBoundingClientRect();
                if (rect.top < 80 || rect.bottom > window.innerHeight) {
                    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                map.flyTo([spot.lat, spot.lng], Math.max(map.getZoom(), 11), { duration: 0.8 });
                map.once('moveend', function () { spot.marker.openPopup(); });
            });
        });

        if (truck && !truckSpot) {
            L.marker(truck, { icon: L.divIcon({ className: 'track-truck', html: '<i class="bi bi-truck"></i>', iconSize: [38, 38], iconAnchor: [19, 19] }), zIndexOffset: 1000 }).addTo(map);
        }

        // Zoomed far out the two labels would sit on top of each other: hide them then.
        function syncLabels() {
            if (labelled.length < 2) {
                return;
            }
            var apart = map.latLngToContainerPoint(start).distanceTo(map.latLngToContainerPoint(end)) > 140;
            labelled.forEach(function (marker) {
                if (apart) {
                    marker.openTooltip();
                } else {
                    marker.closeTooltip();
                }
            });
        }
        map.on('zoomend', syncLabels);

        var bounds = L.latLngBounds(points);
        if (points.length > 1) {
            // Extra room at the top for the "From" / "To" labels.
            map.fitBounds(bounds, { paddingTopLeft: [60, 90], paddingBottomRight: [60, 40] });
        } else {
            map.setView(start, 11);
        }
        syncLabels();

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    })();
</script>

</body>
</html>
