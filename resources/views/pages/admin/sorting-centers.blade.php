<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Sorting Centers — BoomBuy Admin'])

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-logistics.css') }}">
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
            <p>One Sorting Center per city/municipality. Sellers drop parcels at their own town's center; it is sent on to the center in the buyer's town, which delivers it. A town without a center uses another center in its province.</p>
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
                <span>Open Sorting Centers</span>
                <strong>{{ $stats['centers'] }}</strong>
            </div>
            <div class="stat-card">
                <span>Provinces Covered</span>
                <strong>{{ $stats['provinces'] }} <small>of {{ count($provinces) }}</small></strong>
            </div>
            <div class="stat-card">
                <span>Staff Assigned to a Center</span>
                <strong>{{ $stats['staff'] }}</strong>
            </div>
        </div>

        {{-- OPEN A NEW CENTER --}}
        <section class="sc-card">
            <h2><i class="bi bi-plus-circle-fill"></i> Open a Sorting Center</h2>

            <form method="POST" action="{{ route('admin.sorting-centers.store') }}" class="sc-add">
                @csrf

                <label>
                    <span>Province</span>
                    <select name="province" id="scProvince" required>
                        <option value="">Select province</option>
                        @foreach ($provinces as $p)
                            <option value="{{ $p }}" @selected(old('province') === $p)>{{ $p }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>City / Municipality</span>
                    <select name="city_municipality" id="scCity" required data-old="{{ old('city_municipality') }}">
                        <option value="">Select a province first</option>
                    </select>
                </label>

                <label class="sc-add-wide">
                    <span>Address <em>(optional)</em></span>
                    <input type="text" name="address" maxlength="255" value="{{ old('address') }}" placeholder="e.g. 12 National Hwy, Poblacion">
                </label>

                <button type="submit"><i class="bi bi-building-add"></i> Open Center</button>
            </form>
        </section>

        {{-- STAFF --}}
        <section class="sc-card">
            <h2><i class="bi bi-people-fill"></i> Logistics Staff</h2>
            <p class="sc-note">Staff only see and handle their own center's parcels. "Head office" sees every center.</p>

            @forelse ($staff as $person)
                <form method="POST" action="{{ route('admin.sorting-centers.staff', $person->id) }}" class="sc-staff">
                    @csrf
                    <div class="sc-staff-who">
                        <strong>{{ $person->name }}</strong>
                        <span>{{ $person->email }}</span>
                    </div>
                    <select name="sorting_center_id" aria-label="Sorting Center for {{ $person->name }}">
                        <option value="">Head office (all centers)</option>
                        @foreach ($allCenters->groupBy('province') as $prov => $group)
                            <optgroup label="{{ $prov }}">
                                @foreach ($group as $c)
                                    <option value="{{ $c->id }}" @selected((int) $person->sorting_center_id === $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <button type="submit">Save</button>
                </form>
            @empty
                <p class="sc-note">No logistics accounts yet.</p>
            @endforelse
        </section>

        {{-- LIST --}}
        <section class="sc-card">
            <div class="sc-list-head">
                <h2><i class="bi bi-building"></i> Centers ({{ count($centers) }})</h2>

                <form method="GET" action="{{ route('admin.sorting-centers') }}" class="sc-filter">
                    <select name="province" onchange="this.form.submit()" aria-label="Filter by province">
                        <option value="">All provinces</option>
                        @foreach ($usedProvinces as $p)
                            <option value="{{ $p }}" @selected($province === $p)>{{ $p }}</option>
                        @endforeach
                    </select>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search town" aria-label="Search town">
                </form>
            </div>

            <div class="sc-table-wrap">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th>Center</th>
                            <th>Province</th>
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
                                    <span>{{ $center->address }}</span>
                                </td>
                                <td>{{ $center->province }}</td>
                                <td>{{ $center->staff_count }}</td>
                                <td>{{ $parcelCounts[$center->id] ?? 0 }}</td>
                                <td>
                                    <span class="sc-pill {{ $center->is_active ? 'is-open' : 'is-closed' }}">{{ $center->is_active ? 'Open' : 'Closed' }}</span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.sorting-centers.toggle', $center->id) }}"
                                        @if ($center->is_active) data-confirm="Close {{ $center->name }}? New orders in {{ $center->city_municipality }} will go to another center in {{ $center->province }}." data-confirm-ok="Close Center" data-confirm-danger @endif>
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

    </div>

    <div class="footer">
        © {{ date('Y') }} BoomBuy · Sorting Centers
    </div>

    </main>

</div>

<script src="{{ asset('js/data/psgc-data.js') }}"></script>
<script>
    // Province → its cities/municipalities.
    (function () {
        var province = document.getElementById('scProvince');
        var city = document.getElementById('scCity');
        if (!province || !city || !window.PSGC_DATA) return;

        function fill() {
            var towns = window.PSGC_DATA[province.value] || [];
            city.innerHTML = '';
            var first = document.createElement('option');
            first.value = '';
            first.textContent = towns.length ? 'Select city/municipality' : 'Select a province first';
            city.appendChild(first);
            towns.forEach(function (t) {
                var o = document.createElement('option');
                o.value = t;
                o.textContent = t;
                if (t === city.dataset.old) o.selected = true;
                city.appendChild(o);
            });
        }

        province.addEventListener('change', function () { city.dataset.old = ''; fill(); });
        fill();
    })();
</script>

</body>
</html>
