{{--
    Address entry as Province → City/Municipality → street, so every address
    has a town and province we can route to a Sorting Center. The three
    fields are joined into one hidden "address" input
    ("15 Rizal St, Poblacion, Santa Cruz, Laguna") — what the forms post.

    @include('partials.address-fields', ['id' => 'addr', 'value' => old('address', $current)])
--}}
@once
<link rel="stylesheet" href="{{ vasset('css/partials/address-fields.css') }}">
<script src="{{ asset('js/data/psgc-data.js') }}" defer></script>
<script src="{{ vasset('js/address-fields.js') }}" defer></script>
@endonce

@php
    $id = $id ?? 'address';
    $parts = \App\Support\PhLocations::split($value ?? '');
@endphp

<div class="addr-fields" data-address-fields data-province="{{ $parts['province'] }}" data-city="{{ $parts['city'] }}">
    <div class="addr-fields-row">
        <label class="addr-fields-cell">
            <span>Province</span>
            <select id="{{ $id }}_province" data-af-province required>
                <option value="">Select province</option>
            </select>
        </label>
        <label class="addr-fields-cell">
            <span>City / Municipality</span>
            <select id="{{ $id }}_city" data-af-city required disabled>
                <option value="">Select a province first</option>
            </select>
        </label>
    </div>

    <label class="addr-fields-cell">
        <span>House No., Street, Barangay</span>
        <input type="text" id="{{ $id }}_street" data-af-street maxlength="180" value="{{ $parts['street'] }}" placeholder="e.g. 15 Rizal St, Poblacion" required>
    </label>

    <input type="hidden" name="address" value="{{ $value ?? '' }}" data-af-address>
</div>
