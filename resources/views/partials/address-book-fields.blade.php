{{--
    Address-book fields in parts: recipient, phone, region → province →
    city / municipality → barangay, street. Each list only shows places
    under the one above; changing one clears the ones below.

    @include('partials.address-book-fields', ['address' => $address ?? null, 'prefix' => 'ab'])
--}}
@php
    $a = $address ?? null;
    $prefix = $prefix ?? 'ab';
    $v = fn (string $field, $default = '') => old($field, $a->{$field} ?? $default);
@endphp

<div class="ab-fields" data-address-book="{{ $prefix }}">
    <div class="form-grid">
        <div class="field">
            <label for="{{ $prefix }}_recipient">Recipient name</label>
            <input type="text" id="{{ $prefix }}_recipient" name="recipient_name" maxlength="100" value="{{ $v('recipient_name', session('user.name')) }}" required>
            @error('recipient_name')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="{{ $prefix }}_phone">Phone number</label>
            <input type="text" id="{{ $prefix }}_phone" name="phone" maxlength="13" pattern="(09|\+639)[0-9]{9}" placeholder="09XXXXXXXXX" value="{{ $v('phone') }}" required>
            @error('phone')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="{{ $prefix }}_region">Region</label>
            <select id="{{ $prefix }}_region" name="region" data-ab-region required>
                <option value="">Select region</option>
                @foreach(array_keys(\App\Support\PhLocations::provincesByRegionName()) as $region)
                    <option value="{{ $region }}" @selected($v('region') === $region)>{{ $region }}</option>
                @endforeach
            </select>
            @error('region')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="{{ $prefix }}_province">Province</label>
            <select id="{{ $prefix }}_province" name="province" data-ab-province data-old="{{ $v('province') }}" required>
                <option value="">Select a region first</option>
            </select>
            @error('province')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="{{ $prefix }}_city">City / Municipality</label>
            <select id="{{ $prefix }}_city" name="city_municipality" data-ab-city data-old="{{ $v('city_municipality') }}" required>
                <option value="">Select a province first</option>
            </select>
            @error('city_municipality')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="{{ $prefix }}_barangay">Barangay</label>
            <select id="{{ $prefix }}_barangay" name="barangay" data-ab-barangay data-old="{{ $v('barangay') }}" required>
                <option value="">Select a city first</option>
            </select>
            @error('barangay')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field" style="grid-column: 1 / -1;">
            <label for="{{ $prefix }}_street">House no., building, street</label>
            <input type="text" id="{{ $prefix }}_street" name="street" maxlength="180" value="{{ $v('street') }}" placeholder="e.g. 45 Kalayaan Ave." required>
            @error('street')<span class="field-error">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

@once
<style>
    .ab-fields .field-error { display: block; margin-top: 6px; font-size: 12px; font-weight: 700; color: #be123c; }
    .ab-fields select { width: 100%; }
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

        document.querySelectorAll('[data-address-book]').forEach(function (box) {
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

            fillProvinces(province.dataset.old);
            fillCities(city.dataset.old);
            fillBarangays(brgy.dataset.old);

            fetch(@json(asset('js/data/barangays.json'))).then(function (r) { return r.json(); }).then(function (data) {
                barangays = data;
                fillBarangays(brgy.dataset.old);
            }).catch(function () {});
        });
    })();
</script>
@endonce
