{{--
    Specifications for a phone, tablet or laptop (Electronics only).
    $specs: the saved specs (or null). $show: start visible (the category is Electronics).
    On a page with a category <select id="category">, it shows and hides with the choice.
--}}
@php
    $specs = (array) ($specs ?? []);
    $value = fn (string $key) => old('specs.' . $key, $specs[$key] ?? '');
@endphp

<div class="form-group dev-specs" id="deviceSpecs" @unless($show ?? false) hidden @endunless>
    <label>Specifications <span style="font-weight:500; color:#857ca0;">(optional · for phones, tablets and laptops)</span></label>
    <p class="dev-specs-intro">Buyers see these in a Specifications table on the product page. Fill in what applies; leave the rest blank. Storage is set as an option (with its own price) under Options.</p>

    <div class="dev-specs-grid">
        @foreach(\App\Support\DeviceSpecs::FIELDS as $key => [$label, $placeholder])
            <label class="dev-spec">
                <span>{{ $label }}</span>
                @if($key === 'device_type')
                    <select name="specs[device_type]" id="specDeviceType">
                        <option value="">Not a device</option>
                        @foreach(\App\Support\DeviceSpecs::DEVICE_TYPES as $type)
                            <option value="{{ $type }}" @selected($value('device_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                @elseif($key === 'network')
                    <select name="specs[network]">
                        <option value="">—</option>
                        @foreach(\App\Support\DeviceSpecs::NETWORKS as $network)
                            <option value="{{ $network }}" @selected($value('network') === $network)>{{ $network }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="specs[{{ $key }}]" value="{{ $value($key) }}" placeholder="{{ $placeholder }}" maxlength="100">
                @endif
            </label>
        @endforeach
    </div>
</div>

@once
    <style>
        .dev-specs-intro { font-size: 12.5px; color: var(--theme-muted); margin: 2px 0 12px; line-height: 1.5; }
        .dev-specs-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 14px; }
        .form-group .dev-spec { display: flex; flex-direction: column; gap: 5px; margin: 0; font-weight: 600; }
        .form-group .dev-spec span { font-size: 12px; font-weight: 700; color: var(--theme-ink); }
        .dev-spec input, .dev-spec select {
            width: 100%; min-height: 42px; padding: 9px 12px; border: 1px solid #ddd5f2; border-radius: 10px;
            font: inherit; font-size: 13.5px; background: #fff; color: var(--theme-ink);
        }
        .dev-spec input:focus, .dev-spec select:focus { outline: none; border-color: #7a55e6; box-shadow: 0 0 0 3px rgba(122, 85, 230, .1); }
        @media (max-width: 640px) { .dev-specs-grid { grid-template-columns: 1fr; } }
    </style>
    <script>
        // Shown only for Electronics: follows the category dropdown when there is one.
        document.addEventListener('DOMContentLoaded', function () {
            var box = document.getElementById('deviceSpecs');
            var category = document.querySelector('select#category');
            if (!box || !category) return;
            function sync() {
                var on = category.value.toLowerCase() === 'electronics';
                box.hidden = !on;
                box.querySelectorAll('input, select').forEach(function (f) { f.disabled = !on; });
                document.dispatchEvent(new CustomEvent('bb:device-category', { detail: on }));
            }
            category.addEventListener('change', sync);
            sync();
        });
    </script>
@endonce
