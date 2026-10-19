<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Zones & Rates — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/logistics-ops.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="rates" :user="$user" />

    <main class="main-content">
        <div class="container ops">

            <div class="page-header">
                <div>
                    <h1>Zones &amp; Rates</h1>
                    <p>Shipping is the first kg plus each additional whole kg, per route. Changes apply to new checkouts; placed orders keep their fee. An address outside every zone can't be delivered to.</p>
                </div>
            </div>

            @if (session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            <section class="ops-card">
                <h2>Rates</h2>
                @if($rates->isEmpty())
                    <p class="muted">No zones yet — the admin's Delivery Fee settings are used.</p>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>From</th><th>To</th><th>First kg (₱)</th><th>Each additional kg (₱)</th><th></th></tr></thead>
                            <tbody>
                                @foreach($rates as $rate)
                                    <tr data-rate="{{ $rate->id }}">
                                        <td>{{ $zones[$rate->from_zone_id]['name'] ?? '—' }}</td>
                                        <td>{{ $zones[$rate->to_zone_id]['name'] ?? '—' }}</td>
                                        <td colspan="3">
                                            <form method="POST" action="{{ route('logistics.rates.update', $rate->id) }}" class="ops-inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="number" name="first_kg" step="0.01" min="0.01" value="{{ $rate->first_kg }}" required aria-label="First kg">
                                                <input type="number" name="per_kg" step="0.01" min="0.01" value="{{ $rate->per_kg }}" required aria-label="Each additional kg">
                                                <button type="submit">Save</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="ops-card">
                <h2>Add a zone</h2>
                <form method="POST" action="{{ route('logistics.zones.store') }}" class="ops-stack">
                    @csrf
                    <label>Zone name <input type="text" name="name" maxlength="60" required></label>
                    <label>Provinces
                        <select name="provinces[]" multiple size="8" required>
                            @foreach($provinces as $region => $list)
                                <optgroup label="{{ $region }}">
                                    @foreach($list as $province)
                                        <option value="{{ $province }}">{{ \App\Support\PhLocations::provinceLabel($province) }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </label>
                    <label>First kg (₱) <input type="number" name="first_kg" step="0.01" min="0.01" required></label>
                    <label>Each additional kg (₱) <input type="number" name="per_kg" step="0.01" min="0.01" required></label>
                    <button type="submit" class="btn-main">Add zone</button>
                </form>
                <p class="muted">The new zone gets this rate to and from every zone.</p>
            </section>

            <section class="ops-card">
                <h2>Zones</h2>
                @foreach($zones as $zone)
                    <p><strong>{{ $zone['name'] }}</strong>: {{ collect($zone['provinces'])->map(fn ($p) => \App\Support\PhLocations::provinceLabel($p))->implode(', ') }}</p>
                @endforeach
            </section>

        </div>
    </main>

</body>
</html>
