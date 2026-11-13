<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Zones & Rates — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/logistics-ops.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="rates" :user="$user" />

    @php
        $zoneList = collect($zones);
        $provinceCount = $zoneList->sum(fn ($z) => count($z['provinces'] ?? []));
    @endphp

    <main class="main-content">
        <div class="container ops">

            <div class="page-header">
                <h1>Zones &amp; Rates</h1>
                <p>Shipping is the first kg plus each additional whole kg, per route. Changes apply to new checkouts; placed orders keep their fee. An address outside every zone can't be delivered to.</p>
            </div>

            @if (session('success'))
                <div class="success-box"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="error-box"><i class="bi bi-x-circle-fill"></i> {{ session('error') }}</div>
            @endif

            <div class="ops-counts">
                <div class="ops-count">
                    <span class="ops-count-ic"><i class="bi bi-map"></i></span>
                    <span class="ops-count-text"><span>Zones</span><strong>{{ $zoneList->count() }}</strong></span>
                </div>
                <div class="ops-count is-in_transit">
                    <span class="ops-count-ic"><i class="bi bi-signpost-split"></i></span>
                    <span class="ops-count-text"><span>Routes with a rate</span><strong>{{ $rates->count() }}</strong></span>
                </div>
                <div class="ops-count is-delivered">
                    <span class="ops-count-ic"><i class="bi bi-geo-alt"></i></span>
                    <span class="ops-count-text"><span>Provinces covered</span><strong>{{ $provinceCount }}</strong></span>
                </div>
            </div>

            {{-- Zones --}}
            <section class="ops-card">
                <div class="ops-card-head">
                    <div>
                        <h2><i class="bi bi-map"></i> Zones <small>{{ $zoneList->count() }}</small></h2>
                        <p class="muted">Groups of provinces that share the same shipping rates.</p>
                    </div>
                </div>

                @if($zoneList->isEmpty())
                    <div class="ops-empty"><i class="bi bi-map"></i><p>No zones yet — the admin's Delivery Fee settings are used.</p></div>
                @else
                    <div class="ops-zones">
                        @foreach($zones as $zone)
                            <div class="ops-zone-card">
                                <strong><i class="bi bi-pin-map-fill"></i> {{ $zone['name'] }} <small>{{ count($zone['provinces'] ?? []) }} provinces</small></strong>
                                <div class="ops-chips">
                                    @foreach($zone['provinces'] as $p)
                                        <span class="wb-chip is-light is-text">{{ \App\Support\PhLocations::provinceLabel($p) }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Rates --}}
            <section class="ops-card">
                <div class="ops-card-head">
                    <div>
                        <h2><i class="bi bi-cash-coin"></i> Rates</h2>
                        <p class="muted">From one zone to another: the price of the first kg and of each additional kg. Edit and save a row.</p>
                    </div>
                </div>

                @if($rates->isEmpty())
                    <div class="ops-empty"><i class="bi bi-cash-coin"></i><p>No zones yet — the admin's Delivery Fee settings are used.</p></div>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Route</th><th>First kg (₱)</th><th>Each additional kg (₱)</th><th></th></tr></thead>
                            <tbody>
                                @foreach($rates as $rate)
                                    <tr data-rate="{{ $rate->id }}">
                                        <td class="strong">
                                            <span class="ops-route">
                                                {{ $zones[$rate->from_zone_id]['name'] ?? '—' }}
                                                <i class="bi bi-arrow-right"></i>
                                                {{ $zones[$rate->to_zone_id]['name'] ?? '—' }}
                                            </span>
                                        </td>
                                        <td colspan="3">
                                            <form method="POST" action="{{ route('logistics.rates.update', $rate->id) }}" class="ops-inline ops-rate-form">
                                                @csrf
                                                @method('PUT')
                                                <label class="ops-money"><span>₱</span><input type="number" name="first_kg" step="0.01" min="0.01" value="{{ $rate->first_kg }}" required aria-label="First kg"></label>
                                                <label class="ops-money"><span>₱</span><input type="number" name="per_kg" step="0.01" min="0.01" value="{{ $rate->per_kg }}" required aria-label="Each additional kg"></label>
                                                <button type="submit" class="is-ghost"><i class="bi bi-check2"></i> Save</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- Add a zone --}}
            <section class="ops-card">
                <div class="ops-card-head">
                    <div>
                        <h2><i class="bi bi-plus-circle"></i> Add a zone</h2>
                        <p class="muted">The new zone gets this rate to and from every zone.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logistics.zones.store') }}" class="ops-zone-form">
                    @csrf
                    <div class="ops-stack">
                        <label>Zone name <input type="text" name="name" maxlength="60" placeholder="e.g. North Luzon" required></label>
                        <label>First kg (₱) <input type="number" name="first_kg" step="0.01" min="0.01" placeholder="0.00" required></label>
                        <label>Each additional kg (₱) <input type="number" name="per_kg" step="0.01" min="0.01" placeholder="0.00" required></label>
                        <button type="submit" class="btn-main"><i class="bi bi-plus-lg"></i> Add zone</button>
                    </div>
                    <label class="ops-provinces">Provinces <small class="muted">Hold Ctrl (⌘ on Mac) to pick several.</small>
                        <select name="provinces[]" multiple size="12" required>
                            @foreach($provinces as $region => $list)
                                <optgroup label="{{ $region }}">
                                    @foreach($list as $province)
                                        <option value="{{ $province }}">{{ \App\Support\PhLocations::provinceLabel($province) }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </label>
                </form>
            </section>

        </div>
    </main>

</body>
</html>
