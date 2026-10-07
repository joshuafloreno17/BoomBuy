<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Sorting Centers — BoomBuy Admin'])

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-logistics.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <link rel="stylesheet" href="{{ vasset('css/views/admin-sorting-centers.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="sorting-centers" />

    <main class="main">

    <div class="container">

        <div class="header">
            <small>Admin Panel</small>
            <h1>Sorting Centers</h1>
            <p>One BoomBuy Sorting Center per province, covering the whole country. Sellers drop parcels at their province's center; a parcel for a buyer in another province is sent on to that province's center, whose riders deliver it.</p>
        </div>

        @if (session('success'))
            <div class="sc-flash is-ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="sc-flash is-error"><i class="bi bi-x-circle-fill"></i> {{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="sc-flash is-error"><i class="bi bi-x-circle-fill"></i> {{ $errors->first() }}</div>
        @endif

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
            <p class="sc-note">One pin per province. A bigger pin holds more parcels right now; grey pins are closed centers.</p>
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
                <h2><i class="bi bi-building"></i> Centers ({{ count($centers) }})</h2>

                <form method="GET" action="{{ route('admin.sorting-centers') }}" class="sc-filter">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search province, town or region" aria-label="Search centers">
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
                    <tbody>
                        @forelse ($centers as $center)
                            <tr class="{{ $center->is_active ? '' : 'is-closed' }}">
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
                        @empty
                            <tr><td colspan="6" class="sc-empty">No Sorting Centers match.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

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

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
    // Pins for every center; the popup shows its town and parcel count.
    (function () {
        var box = document.getElementById('scMap');
        if (!box || !window.L) return;

        var map = L.map(box, { scrollWheelZoom: false }).setView([12.3, 122.5], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 12,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        @json($mapPins).forEach(function (pin) {
            var popup = document.createElement('div');
            var name = document.createElement('strong');
            name.textContent = pin.name;
            popup.appendChild(name);
            popup.appendChild(document.createElement('br'));
            popup.appendChild(document.createTextNode(pin.town + ' · ' + pin.parcels + ' parcel(s)' + (pin.open ? '' : ' · closed')));

            L.circleMarker([pin.lat, pin.lng], {
                radius: Math.min(16, 6 + pin.parcels),
                color: pin.open ? '#c2410c' : '#8a7f86',
                fillColor: pin.open ? '#e8420f' : '#c9c2c6',
                fillOpacity: 0.85,
                weight: 2
            }).bindPopup(popup).addTo(map);
        });
    })();
</script>
<script src="{{ asset('js/data/psgc-data.js') }}"></script>
<script>
    // Region → its provinces → that province's cities/municipalities.
    (function () {
        var regions = @json(collect($regions)->map(fn ($r) => $r['provinces']));
        var region = document.getElementById('scRegion');
        var province = document.getElementById('scProvince');
        var city = document.getElementById('scCity');
        if (!region || !province || !city || !window.PSGC_DATA) return;

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

        region.addEventListener('change', function () { province.dataset.old = ''; city.dataset.old = ''; fillProvinces(); });
        province.addEventListener('change', function () { city.dataset.old = ''; fillCities(); });
        fillProvinces();
    })();
</script>

</body>
</html>
