<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Logistics — BoomBuy Admin'])

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-logistics.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <link rel="stylesheet" href="{{ vasset('css/views/admin-sorting-centers.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="logistics" />

    <main class="main">

    <div class="container">

        @include('partials.admin-section-head', ['section' => $section])

        @if (session('success'))
            <div class="sc-flash is-ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="sc-flash is-error"><i class="bi bi-x-circle-fill"></i> {{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="sc-flash is-error"><i class="bi bi-x-circle-fill"></i> {{ $errors->first() }}</div>
        @endif

        @if ($section['tab'] === 'centers')

        <div class="stats">
            <div class="stat-card">
                <span>Provinces With an Open Center</span>
                <strong>{{ $stats['centers'] }} <small>of {{ $stats['provinces'] }}</small></strong>
            </div>
            <div class="stat-card">
                <span>Parcels at or Between Centers</span>
                <strong>{{ $parcelCounts->sum() }}</strong>
            </div>
            <div class="stat-card">
                <span>Staff Assigned to a Center</span>
                <strong>{{ $stats['staff'] }}</strong>
            </div>
        </div>

        {{-- WHERE THE CENTERS ARE --}}
        <section class="sc-card">
            <h2><i class="bi bi-map-fill"></i> Sorting Center Map</h2>
            <p class="sc-note">One pin per province. A bigger pin holds more parcels right now; grey pins are closed centers. Zoom with the + / − buttons, Ctrl + scroll, or a pinch.</p>
            <div id="scMap" class="sc-map" role="img" aria-label="Map of the BoomBuy Sorting Centers"></div>
        </section>

        {{-- SET UP / MOVE A REGION'S CENTER --}}
        <section class="sc-card">
            <h2><i class="bi bi-geo-alt-fill"></i> Set Up or Move a Province's Center</h2>
            <p class="sc-note">Pick the province, then the town where the building is. A province that already has a center gets it moved there.</p>

            <form method="POST" action="{{ route('admin.sorting-centers.store') }}" class="sc-add">
                @csrf

                <label class="sc-add-wide">
                    <span>Region</span>
                    <select name="region" id="scRegion" required>
                        <option value="">Select region</option>
                        @foreach ($regions as $key => $region)
                            <option value="{{ $key }}" @selected(old('region') === $key)>{{ $region['label'] }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>Province</span>
                    <select name="province" id="scProvince" required data-old="{{ old('province') }}">
                        <option value="">Select a region first</option>
                    </select>
                </label>

                <label>
                    <span>City / Municipality</span>
                    <select name="city_municipality" id="scCity" required data-old="{{ old('city_municipality') }}">
                        <option value="">Select a province first</option>
                    </select>
                </label>

                <label class="sc-add-wide">
                    <span>Street address <em>(optional)</em></span>
                    <input type="text" name="address" maxlength="255" value="{{ old('address') }}" placeholder="e.g. 12 National Hwy, Real, Calamba City, Laguna">
                </label>

                <button type="submit"><i class="bi bi-building-check"></i> Save Center</button>
            </form>
        </section>

        {{-- LIST --}}
        <section class="sc-card">
            <div class="sc-list-head">
                <h2><i class="bi bi-building"></i> Centers (<span id="scCount">{{ $centers->where('matches_search', true)->count() }}</span>)</h2>

                <form method="GET" action="{{ route('admin.logistics') }}" class="sc-filter" id="scFilter">
                    <input type="hidden" name="tab" value="centers">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search province, town or region" aria-label="Search centers" autocomplete="off">
                </form>
            </div>

            <div class="sc-table-wrap">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th>Center</th>
                            <th>Region</th>
                            <th>Staff</th>
                            <th>Parcels now</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="scRows">
                        @foreach ($centers as $center)
                            <tr class="{{ $center->is_active ? '' : 'is-closed' }}" data-search="{{ $center->search_text }}" @if (!$center->matches_search) hidden @endif>
                                <td>
                                    <strong>{{ $center->name }}</strong>
                                    <span>{{ $center->address ?: $center->town }}</span>
                                </td>
                                <td class="sc-serves">{{ \App\Support\PhLocations::regionLabel($center->region) ?? '—' }}</td>
                                <td>{{ $center->staff_count }}</td>
                                <td>{{ $parcelCounts[$center->id] ?? 0 }}</td>
                                <td>
                                    <span class="sc-pill {{ $center->is_active ? 'is-open' : 'is-closed' }}">{{ $center->is_active ? 'Open' : 'Closed' }}</span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.sorting-centers.toggle', $center->id) }}"
                                        @if ($center->is_active) data-confirm="Close {{ $center->name }}? New orders in {{ $center->area }} will have no regional center until it reopens." data-confirm-ok="Close Center" data-confirm-danger @endif>
                                        @csrf
                                        <button type="submit" class="sc-toggle">{{ $center->is_active ? 'Close' : 'Reopen' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        <tr id="scNoMatch" @if ($centers->contains('matches_search', true)) hidden @endif><td colspan="6" class="sc-empty">No Sorting Centers match.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
        @endif

        @if ($section['tab'] === 'branches')

        {{-- DELIVERY BRANCHES --}}
        <section class="sc-card" id="branches">
            <h2><i class="bi bi-shop-window"></i> Delivery Branches ({{ count($branches) }})</h2>
            <p class="sc-note">Like a courier's local branch: the province's center sorts a parcel by town and trucks it to the branch in the buyer's town, whose riders deliver it. Towns without a branch get their parcels straight from the center. A branch needs staff (below) before parcels go there.</p>

            <form method="POST" action="{{ route('admin.sorting-centers.branches.store') }}" class="sc-add">
                @csrf

                <label>
                    <span>Sorting Center</span>
                    <select name="parent_id" id="brCenter" required>
                        <option value="">Select center</option>
                        @foreach ($centers->sortBy('province') as $center)
                            <option value="{{ $center->id }}" data-province="{{ $center->province }}" @selected((int) old('parent_id') === $center->id)>{{ $center->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>Town of the branch</span>
                    <select name="city_municipality" id="brCity" required data-old="{{ old('city_municipality') }}">
                        <option value="">Select a center first</option>
                    </select>
                </label>

                <label class="sc-add-wide">
                    <span>Street address <em>(optional)</em></span>
                    <input type="text" name="address" maxlength="255" value="{{ old('address') }}" placeholder="e.g. P. Guevarra St., Poblacion, Santa Cruz, Laguna">
                </label>

                <button type="submit"><i class="bi bi-shop-window"></i> Open Branch</button>
            </form>

            @if (count($branches))
                <div class="sc-table-wrap">
                    <table class="sc-table">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Center</th>
                                <th>Staff</th>
                                <th>Parcels now</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($branches as $branch)
                                <tr class="{{ $branch->is_active ? '' : 'is-closed' }}">
                                    <td>
                                        <strong>{{ $branch->name }}</strong>
                                        <span>{{ $branch->address ?: $branch->town }}</span>
                                    </td>
                                    <td class="sc-serves">{{ $branch->parent->name ?? '—' }}</td>
                                    <td>{{ $branch->staff_count }}@if (!$branch->staff_count) <em class="sc-warn">· needs staff</em>@endif</td>
                                    <td>{{ $parcelCounts[$branch->id] ?? 0 }}</td>
                                    <td>
                                        <span class="sc-pill {{ $branch->is_active ? 'is-open' : 'is-closed' }}">{{ $branch->is_active ? 'Open' : 'Closed' }}</span>
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.sorting-centers.toggle', $branch->id) }}"
                                            @if ($branch->is_active) data-confirm="Close {{ $branch->name }}? New parcels for {{ $branch->town }} will be delivered from {{ $branch->parent->name ?? 'the center' }}." data-confirm-ok="Close Branch" data-confirm-danger @endif>
                                            @csrf
                                            <button type="submit" class="sc-toggle">{{ $branch->is_active ? 'Close' : 'Reopen' }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
        @endif

        {{-- STAFF --}}
        <section class="sc-card">
            <h2><i class="bi bi-people-fill"></i> Logistics Staff</h2>
            <p class="sc-note">Staff only see and handle their own center's parcels and riders. "Head office" sees every center. A new account has no access until you assign it here.</p>

            @forelse ($staff as $person)
                <form method="POST" action="{{ route('admin.sorting-centers.staff', $person->id) }}" class="sc-staff">
                    @csrf
                    <div class="sc-staff-who">
                        <strong>{{ $person->name }}</strong>
                        <span>{{ $person->email }}</span>
                    </div>
                    <select name="sorting_center_id" aria-label="Sorting Center for {{ $person->name }}">
                        <option value="" @selected(!$person->sorting_center_id && !$person->is_head_office)>Not assigned yet (no access)</option>
                        <option value="{{ \App\Http\Controllers\SortingCenterController::HEAD_OFFICE }}" @selected(!$person->sorting_center_id && $person->is_head_office)>Head office (all centers)</option>
                        @foreach ($allCenters as $c)
                            <option value="{{ $c->id }}" @selected((int) $person->sorting_center_id === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit">Save</button>
                </form>
            @empty
                <p class="sc-note">No logistics accounts yet.</p>
            @endforelse
        </section>

    </div>

    <div class="footer">
        © {{ date('Y') }} BoomBuy · Sorting Centers
    </div>

    </main>

</div>

<script>
    // Live search: rows filter as you type, no Enter or reload needed.
    (function () {
        var form = document.getElementById('scFilter');
        if (!form) return;

        var input = form.querySelector('input[name="q"]');
        var rows = Array.prototype.slice.call(document.querySelectorAll('#scRows tr[data-search]'));
        var count = document.getElementById('scCount');
        var noMatch = document.getElementById('scNoMatch');

        function apply() {
            var words = input.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
            var shown = 0;

            rows.forEach(function (row) {
                var text = row.dataset.search;
                var match = words.every(function (w) { return text.indexOf(w) !== -1; });
                row.hidden = !match;
                if (match) shown++;
            });

            count.textContent = shown;
            noMatch.hidden = shown > 0;

            // Keep the search in the address bar so a reload keeps it.
            var url = new URL(window.location.href);
            if (input.value.trim()) url.searchParams.set('q', input.value.trim());
            else url.searchParams.delete('q');
            history.replaceState(null, '', url);
        }

        input.addEventListener('input', apply);
        form.addEventListener('submit', function (e) { e.preventDefault(); apply(); });
    })();
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
    // Pins for every center; the popup shows its town and parcel count.
    (function () {
        var box = document.getElementById('scMap');
        if (!box || !window.L) return;

        // The Philippines only: the map can't drift off to neighbouring countries.
        var philippines = L.latLngBounds([4.2, 115.5], [21.5, 127.5]);
        var map = L.map(box, {
            scrollWheelZoom: false,
            maxBounds: philippines.pad(0.15),
            maxBoundsViscosity: 1,
            zoomSnap: 0.25
        }).fitBounds(philippines);
        map.setMinZoom(map.getZoom());
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 12,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // The box can still change size after the page lays out (fonts,
        // sidebar, window): measure again and keep the whole country in view.
        if (window.ResizeObserver) {
            var lastWidth = 0;
            new ResizeObserver(function () {
                if (!box.clientWidth || box.clientWidth === lastWidth) return;
                lastWidth = box.clientWidth;
                map.invalidateSize();
                map.setMinZoom(0);
                map.fitBounds(philippines);
                map.setMinZoom(map.getZoom());
            }).observe(box);
        }

        // A plain scroll still scrolls the page. Ctrl + scroll, and a touchpad
        // pinch (which browsers send as Ctrl + wheel), zoom the map instead
        // of zooming the whole page.
        var pinch = 0;
        box.addEventListener('wheel', function (e) {
            if (!e.ctrlKey) return;
            e.preventDefault();

            pinch += e.deltaY;
            if (Math.abs(pinch) < 40) return;

            var step = pinch < 0 ? 1 : -1;
            pinch = 0;
            map.setZoomAround(map.mouseEventToContainerPoint(e), map.getZoom() + step);
        }, { passive: false });

        @json($mapPins).forEach(function (pin) {
            var popup = document.createElement('div');
            var name = document.createElement('strong');
            name.textContent = pin.name;
            popup.appendChild(name);
            popup.appendChild(document.createElement('br'));
            popup.appendChild(document.createTextNode(pin.town + ' · ' + pin.parcels + ' parcel(s)' + (pin.open ? '' : ' · closed')));

            L.circleMarker([pin.lat, pin.lng], {
                radius: Math.min(16, 6 + pin.parcels),
                color: pin.open ? '#5634b3' : '#857ca0',
                fillColor: pin.open ? '#6d4ad1' : '#c2bdce',
                fillOpacity: 0.85,
                weight: 2
            }).bindPopup(popup).addTo(map);
        });
    })();
</script>
<script src="{{ asset('js/data/psgc-data.js') }}"></script>
<script>
    // Center form (Sorting Centers tab): region → its provinces → that
    // province's cities/municipalities. Branch form (Branches tab): the
    // center's province → its towns. Each tab has only its own form.
    (function () {
        if (!window.PSGC_DATA) return;

        var regions = @json(collect($regions)->map(fn ($r) => $r['provinces']));
        var region = document.getElementById('scRegion');
        var province = document.getElementById('scProvince');
        var city = document.getElementById('scCity');

        function options(select, values, placeholder, selected) {
            select.innerHTML = '';
            var first = document.createElement('option');
            first.value = '';
            first.textContent = placeholder;
            select.appendChild(first);
            values.forEach(function (v) {
                var o = document.createElement('option');
                o.value = v;
                o.textContent = v;
                if (v === selected) o.selected = true;
                select.appendChild(o);
            });
        }

        function fillProvinces() {
            var list = regions[region.value] || [];
            options(province, list, list.length ? 'Select province' : 'Select a region first', province.dataset.old);
            fillCities();
        }

        function fillCities() {
            var towns = window.PSGC_DATA[province.value] || [];
            options(city, towns, towns.length ? 'Select city/municipality' : 'Select a province first', city.dataset.old);
        }

        if (region && province && city) {
            region.addEventListener('change', function () { province.dataset.old = ''; city.dataset.old = ''; fillProvinces(); });
            province.addEventListener('change', function () { city.dataset.old = ''; fillCities(); });
            fillProvinces();
        }

        // Branch form: the center's province → its towns.
        var brCenter = document.getElementById('brCenter');
        var brCity = document.getElementById('brCity');
        if (brCenter && brCity) {
            var fillBranchTowns = function () {
                var picked = brCenter.options[brCenter.selectedIndex];
                var towns = window.PSGC_DATA[picked ? picked.dataset.province : ''] || [];
                options(brCity, towns, towns.length ? 'Select city/municipality' : 'Select a center first', brCity.dataset.old);
            };
            brCenter.addEventListener('change', function () { brCity.dataset.old = ''; fillBranchTowns(); });
            fillBranchTowns();
        }
    })();
</script>

</body>
</html>
