/*
   Province → City/Municipality → street, joined into the hidden "address"
   input (see resources/views/partials/address-fields.blade.php).

   Each [data-address-fields] block also gets block.bbSetAddress({province,
   city, street}) so a page can fill it (checkout's saved-address picker).
   Every change fires an "input" event on the hidden address input.
*/
(function () {
    function provinceLabel(province) {
        return province === 'Metro Manila (NCR)' ? 'Metro Manila' : province;
    }

    function setup(block) {
        var data = window.PSGC_DATA || {};
        var province = block.querySelector('[data-af-province]');
        var city = block.querySelector('[data-af-city]');
        var street = block.querySelector('[data-af-street]');
        var hidden = block.querySelector('[data-af-address]');

        Object.keys(data).forEach(function (name) {
            var o = document.createElement('option');
            o.value = name;
            o.textContent = name;
            province.appendChild(o);
        });

        function fillCities(selected) {
            var towns = data[province.value] || [];
            city.innerHTML = '';
            var first = document.createElement('option');
            first.value = '';
            first.textContent = towns.length ? 'Select city/municipality' : 'Select a province first';
            city.appendChild(first);
            towns.forEach(function (t) {
                var o = document.createElement('option');
                o.value = t;
                o.textContent = t;
                city.appendChild(o);
            });
            city.disabled = towns.length === 0;
            if (selected && towns.indexOf(selected) !== -1) city.value = selected;
        }

        function compose() {
            var parts = [street.value.trim(), city.value, province.value ? provinceLabel(province.value) : '']
                .filter(function (p) { return p; });
            hidden.value = parts.join(', ');
            hidden.dispatchEvent(new Event('input', { bubbles: true }));
        }

        province.addEventListener('change', function () { fillCities(''); compose(); });
        city.addEventListener('change', compose);
        street.addEventListener('input', compose);

        block.bbSetAddress = function (a) {
            province.value = a.province && data[a.province] ? a.province : '';
            fillCities(a.city || '');
            street.value = a.street || '';
            compose();
        };

        // Start from what the page had (an edited or previously saved address).
        if (block.dataset.province && data[block.dataset.province]) {
            province.value = block.dataset.province;
        }
        fillCities(block.dataset.city || '');
    }

    function init() {
        document.querySelectorAll('[data-address-fields]').forEach(setup);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
