{{--
    Address-book fields in parts: recipient, phone, region → province →
    city / municipality → barangay, street. Each list only shows places
    under the one above; changing one clears the ones below.

    @include('partials.address-book-fields', ['address' => $address ?? null, 'prefix' => 'ab'])

    Two forms on one page: give each its own error 'bag', and 'useOld' => false
    on the form that was not the one sent (so it doesn't pick up the other's input).
    JS can refill a box later: window.bbAddressBook.fill(box, {region, province, ...}).
--}}
@php
    $a = $address ?? null;
    $prefix = $prefix ?? 'ab';
    $bag = $bag ?? 'default';
    $useOld = $useOld ?? true;
    $v = fn (string $field, $default = '') => $useOld ? old($field, $a->{$field} ?? $default) : ($a->{$field} ?? $default);
@endphp

<div class="ab-fields" data-address-book="{{ $prefix }}">
    <div class="ab-locate">
        <button type="button" class="ab-locate-btn" data-ab-locate><i class="bi bi-crosshair"></i> Use my location</button>
        <span class="ab-locate-note" data-ab-locate-note role="status"></span>
    </div>
    <div class="form-grid">
        <div class="field">
            <label for="{{ $prefix }}_recipient">Recipient name</label>
            <input type="text" id="{{ $prefix }}_recipient" name="recipient_name" maxlength="100" value="{{ $v('recipient_name', session('user.name')) }}" required>
            @error('recipient_name', $bag)<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="{{ $prefix }}_phone">Phone number</label>
            <input type="text" id="{{ $prefix }}_phone" name="phone" inputmode="tel" autocomplete="tel" maxlength="13" pattern="(09|\+639)[0-9]{9}" placeholder="09XXXXXXXXX" value="{{ $v('phone') }}" required>
            @error('phone', $bag)<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="{{ $prefix }}_region">Region</label>
            <select id="{{ $prefix }}_region" name="region" data-ab-region required>
                <option value="">Select region</option>
                @foreach(array_keys(\App\Support\PhLocations::provincesByRegionName()) as $region)
                    <option value="{{ $region }}" @selected($v('region') === $region)>{{ $region }}</option>
                @endforeach
            </select>
            @error('region', $bag)<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="{{ $prefix }}_province">Province</label>
            <select id="{{ $prefix }}_province" name="province" data-ab-province data-old="{{ $v('province') }}" required>
                <option value="">Select a region first</option>
            </select>
            @error('province', $bag)<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="{{ $prefix }}_city">City / Municipality</label>
            <select id="{{ $prefix }}_city" name="city_municipality" data-ab-city data-old="{{ $v('city_municipality') }}" required>
                <option value="">Select a province first</option>
            </select>
            @error('city_municipality', $bag)<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="{{ $prefix }}_barangay">Barangay</label>
            <select id="{{ $prefix }}_barangay" name="barangay" data-ab-barangay data-old="{{ $v('barangay') }}" required>
                <option value="">Select a city first</option>
            </select>
            @error('barangay', $bag)<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field" style="grid-column: 1 / -1;">
            <label for="{{ $prefix }}_street">House no., building, street</label>
            <input type="text" id="{{ $prefix }}_street" name="street" maxlength="180" value="{{ $v('street') }}" placeholder="e.g. 45 Kalayaan Ave." required>
            @error('street', $bag)<span class="field-error">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

@once
<style>
    .ab-fields .field-error { display: block; margin-top: 6px; font-size: 12px; font-weight: 700; color: #be123c; }
    .ab-fields select { width: 100%; }
    .ab-locate { display: flex; align-items: center; flex-wrap: wrap; gap: 8px 12px; margin-bottom: 12px; }
    .ab-locate-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 999px; border: 1px solid var(--theme-primary); background: #fff; color: var(--theme-primary); font: inherit; font-weight: 700; cursor: pointer; }
    .ab-locate-btn[disabled] { opacity: .6; cursor: progress; }
    .ab-locate-note { font-size: 13px; color: var(--theme-muted); }
    .ab-locate-note.is-error { color: #be123c; }
    .ab-locate-note button { margin-left: 6px; border: 0; background: none; color: var(--theme-primary); font: inherit; font-weight: 700; text-decoration: underline; cursor: pointer; }
</style>
<script>
    window.BB_REGIONS = @json(\App\Support\PhLocations::provincesByRegionName());
    window.BB_TOWNS = @json(\App\Support\PhLocations::all());
    (function () {
        var barangays = null;

        function fill(select, items, placeholder, keep) {
            select.innerHTML = '';
            var first = document.createElement('option');
            first.value = '';
            first.textContent = placeholder;
            select.appendChild(first);
            (items || []).forEach(function (name) {
                var opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                select.appendChild(opt);
            });
            select.disabled = !items || !items.length;
            if (keep && items && items.indexOf(keep) !== -1) select.value = keep;
        }

        var loading = null;

        function loadBarangays() {
            loading = loading || fetch(@json(asset('js/data/barangays.json'))).then(function (r) { return r.json(); }).then(function (data) {
                barangays = data;
            });
            return loading;
        }

        function setup(box) {
            if (box.__abFill) return;

            var region = box.querySelector('[data-ab-region]');
            var province = box.querySelector('[data-ab-province]');
            var city = box.querySelector('[data-ab-city]');
            var brgy = box.querySelector('[data-ab-barangay]');

            function fillProvinces(keep) { fill(province, window.BB_REGIONS[region.value], region.value ? 'Select province' : 'Select a region first', keep); }
            function fillCities(keep) { fill(city, window.BB_TOWNS[province.value], province.value ? 'Select city / municipality' : 'Select a province first', keep); }
            function fillBarangays(keep) {
                var list = barangays && barangays[province.value] ? barangays[province.value][city.value] : null;
                fill(brgy, list, city.value ? (barangays ? 'Select barangay' : 'Loading barangays…') : 'Select a city first', keep);
            }

            // Changing a level clears the levels under it.
            region.addEventListener('change', function () { fillProvinces(''); fillCities(''); fillBarangays(''); });
            province.addEventListener('change', function () { fillCities(''); fillBarangays(''); });
            city.addEventListener('change', function () { fillBarangays(''); });

            // Puts a saved address back into the lists (also used on page load).
            box.__abFill = function (place) {
                region.value = place.region || '';
                province.dataset.old = place.province || '';
                city.dataset.old = place.city_municipality || '';
                brgy.dataset.old = place.barangay || '';
                fillProvinces(province.dataset.old);
                fillCities(city.dataset.old);
                fillBarangays(brgy.dataset.old);
            };

            fillProvinces(province.dataset.old);
            fillCities(city.dataset.old);
            fillBarangays(brgy.dataset.old);

            loadBarangays().then(function () {
                fillBarangays(brgy.dataset.old);
            }).catch(function () {});

            setupLocate(box);
        }

        /*
         * "Use my location" (mobile sheet BUY-ADDR-010): the phone's position
         * → OpenStreetMap's address for it → the matching region, province,
         * city and barangay in the lists. Everything stays editable; with
         * location off the address is simply typed in.
         */
        function fold(text) {
            return String(text || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
                .replace(/\(.*?\)/g, '').replace(/^(city|municipality) of /, '').replace(/ city$/, '')
                .replace(/[^a-z0-9]+/g, ' ').trim();
        }

        function pick(options, names) {
            var wanted = names.filter(Boolean).map(fold);
            return (options || []).find(function (o) { return wanted.indexOf(fold(o)) !== -1; })
                || (options || []).find(function (o) { return wanted.indexOf(fold(o).replace(/^barangay /, '')) !== -1; })
                || '';
        }

        function placeFrom(addr) {
            var all = [addr.province, addr.state, addr.county, addr.region, addr.state_district];
            var isNcr = all.some(function (v) { return /metro manila|national capital/i.test(v || ''); });
            var place = { region: '', province: '', city_municipality: '', barangay: '' };

            Object.keys(window.BB_REGIONS).some(function (region) {
                var found = isNcr ? window.BB_REGIONS[region].find(function (p) { return /NCR/.test(p); }) : pick(window.BB_REGIONS[region], all);
                if (found) { place.region = region; place.province = found; }
                return !!found;
            });

            if (place.province) {
                place.city_municipality = pick(window.BB_TOWNS[place.province], [addr.city, addr.town, addr.municipality, addr.village, addr.city_district]);
            }

            if (place.city_municipality && barangays && barangays[place.province]) {
                place.barangay = pick(barangays[place.province][place.city_municipality], [addr.quarter, addr.suburb, addr.village, addr.neighbourhood, addr.hamlet, addr.city_district]);
            }

            place.street = [addr.house_number, addr.road].filter(Boolean).join(' ');

            return place;
        }

        function setupLocate(box) {
            var btn = box.querySelector('[data-ab-locate]');
            var note = box.querySelector('[data-ab-locate-note]');
            if (!btn || btn.__wired) return;
            btn.__wired = true;

            function say(text, isError, withSettings) {
                note.textContent = text;
                note.classList.toggle('is-error', !!isError);
                if (withSettings && window.BoomBuyApp) {
                    var open = document.createElement('button');
                    open.type = 'button';
                    open.textContent = 'Open Settings';
                    open.addEventListener('click', function () { window.BoomBuyApp.postMessage(JSON.stringify({ action: 'settings' })); });
                    note.appendChild(open);
                }
            }

            if (!navigator.geolocation) { btn.hidden = true; return; }

            btn.addEventListener('click', function () {
                btn.disabled = true;
                say('Finding your location…');

                navigator.geolocation.getCurrentPosition(function (p) {
                    var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&accept-language=en'
                        + '&lat=' + p.coords.latitude + '&lon=' + p.coords.longitude;

                    Promise.all([fetch(url).then(function (r) { return r.json(); }), loadBarangays()]).then(function (res) {
                        var place = placeFrom((res[0] && res[0].address) || {});
                        btn.disabled = false;

                        if (!place.province) {
                            say('We couldn\'t match your location to a province. Please pick your address below.', true);
                            return;
                        }

                        var street = box.querySelector('input[name=street]');
                        box.__abFill(place);
                        if (street && !street.value.trim() && place.street) street.value = place.street;

                        say(place.barangay
                            ? 'Filled in from your location. Check it, and add your house number if it is missing.'
                            : 'Filled in what we could from your location. Please pick the rest below.');
                    }).catch(function () {
                        btn.disabled = false;
                        say('We couldn\'t look up your address right now. Please pick it below.', true);
                    });
                }, function (err) {
                    btn.disabled = false;
                    say(err && err.code === 1
                        ? 'Location is off for BoomBuy. You can still type your address below.'
                        : 'We couldn\'t get your location. You can still type your address below.', true, err && err.code === 1);
                }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
            });
        }

        function setupAll() {
            document.querySelectorAll('[data-address-book]').forEach(setup);
        }

        window.bbAddressBook = {
            fill: function (box, place) {
                setup(box);
                box.__abFill(place || {});
            }
        };

        // Wait for the whole page, so a second set of fields further down is wired up too.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupAll);
        } else {
            setupAll();
        }
    })();
</script>
@endonce
