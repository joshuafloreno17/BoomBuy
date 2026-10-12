// Shared behavior for the buyer/seller/rider registration forms:
// - Province -> City/Municipality cascading dropdown (data from psgc-data.js)
// - Birthdate -> auto-computed Age display
// - Register button sends the form once (a quick double click is ignored)
(function () {

    function initAddressCascade(provinceId, cityId, selectedCity) {
        var provinceSelect = document.getElementById(provinceId);
        var citySelect = document.getElementById(cityId);

        if (!provinceSelect || !citySelect || !window.PSGC_DATA) return;

        Object.keys(window.PSGC_DATA).sort().forEach(function (province) {
            var opt = document.createElement('option');
            opt.value = province;
            opt.textContent = province;
            provinceSelect.appendChild(opt);
        });

        function fillCities(selected) {
            citySelect.innerHTML = '<option value="">Select City / Municipality</option>';

            var cities = window.PSGC_DATA[provinceSelect.value] || [];

            cities.forEach(function (city) {
                var opt = document.createElement('option');
                opt.value = city;
                opt.textContent = city;
                if (selected && selected === city) opt.selected = true;
                citySelect.appendChild(opt);
            });

            citySelect.disabled = cities.length === 0;
        }

        provinceSelect.addEventListener('change', function () {
            fillCities(null);
        });

        var oldProvince = provinceSelect.getAttribute('data-old');
        if (oldProvince) {
            provinceSelect.value = oldProvince;
            fillCities(selectedCity || null);
        }
    }

    // Region -> Province: the Province list only shows the chosen region's
    // provinces; changing the region clears Province, City and Barangay.
    // Call after initAddressCascade (it filled every province).
    function initRegionFilter(regionId, provinceId, cityId, barangayId, provincesByRegion) {
        var regionSelect = document.getElementById(regionId);
        var provinceSelect = document.getElementById(provinceId);
        var citySelect = document.getElementById(cityId);
        var barangayInput = document.getElementById(barangayId);

        if (!regionSelect || !provinceSelect) return;

        var placeholder = provinceSelect.options[0].cloneNode(true);

        function fillProvinces(keep) {
            var provinces = provincesByRegion[regionSelect.value] || [];
            provinceSelect.innerHTML = '';
            var first = placeholder.cloneNode(true);
            if (provinces.length && /first/i.test(first.textContent)) first.textContent = 'Select province';
            provinceSelect.appendChild(first);
            provinces.slice().sort().forEach(function (province) {
                var opt = document.createElement('option');
                opt.value = province;
                opt.textContent = province;
                provinceSelect.appendChild(opt);
            });
            provinceSelect.value = keep && provinces.indexOf(keep) !== -1 ? keep : '';
            provinceSelect.disabled = provinces.length === 0;
        }

        regionSelect.addEventListener('change', function () {
            fillProvinces(null);
            // Let the Province -> City cascade empty the city list too.
            provinceSelect.dispatchEvent(new Event('change'));
            if (barangayInput) barangayInput.value = '';
        });

        var kept = provinceSelect.value;
        var keptCity = citySelect ? citySelect.value : '';
        fillProvinces(kept);
        if (kept && provinceSelect.value !== kept && citySelect) {
            provinceSelect.dispatchEvent(new Event('change'));
        } else if (citySelect && keptCity) {
            citySelect.value = keptCity;
        }
    }

    // Barangay dropdown and postal code for the chosen town, from
    // js/data/barangays.json (PSGC) and js/data/postal-codes.json (GeoNames).
    // A town with no barangay list gets a text box instead; a town with no
    // known postal code lets the user type it.
    function initBarangayAndPostal(o) {
        var province = document.getElementById(o.province);
        var city = document.getElementById(o.city);
        var select = document.getElementById(o.barangay);
        var text = document.getElementById(o.barangayText);
        var postal = document.getElementById(o.postal);
        var hint = o.hint ? document.getElementById(o.hint) : null;
        if (!province || !city || !select || !text || !postal) return;

        var barangays = null;
        var zips = null;
        var keepBarangay = select.getAttribute('data-old') || '';
        var typedPostal = postal.value;

        function load(url) {
            return fetch(url).then(function (r) { return r.ok ? r.json() : null; }).catch(function () { return null; });
        }

        // Exactly one of the two carries name="barangay" and gets checked.
        function useText(on) {
            text.hidden = !on;
            select.hidden = on;
            text.disabled = !on;
            text.required = on;
            select.required = !on;
            if (on) {
                text.name = 'barangay';
                select.removeAttribute('name');
            } else {
                select.name = 'barangay';
                text.removeAttribute('name');
            }
        }

        function currentBarangay() {
            return (text.hidden ? select.value : text.value).trim();
        }

        function fillBarangays(keep) {
            var list = barangays && barangays[province.value] ? barangays[province.value][city.value] : null;
            select.innerHTML = '';
            var first = document.createElement('option');
            first.value = '';
            select.appendChild(first);

            if (!city.value) {
                useText(false);
                first.textContent = 'Select city / municipality first';
                select.disabled = true;
                return;
            }
            if (barangays && (!list || !list.length)) {
                useText(true);
                text.value = keep || '';
                return;
            }

            useText(false);
            first.textContent = list ? 'Select barangay' : 'Loading barangays…';
            (list || []).forEach(function (name) {
                var opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                select.appendChild(opt);
            });
            select.disabled = !list;
            if (keep && list && list.indexOf(keep) !== -1) select.value = keep;
        }

        function fillPostal() {
            var code = '';
            if (zips && city.value) {
                var places = zips.places[province.value] ? zips.places[province.value][city.value] : null;
                var brgy = currentBarangay();
                code = (places && brgy && places[brgy]) || (zips.towns[province.value] || {})[city.value] || '';
            }

            if (code) {
                postal.value = code;
                postal.readOnly = true;
                postal.placeholder = 'Select municipality';
                if (hint) hint.textContent = 'Automatically filled from your municipality';
            } else if (city.value && zips) {
                postal.readOnly = false;
                postal.value = typedPostal;
                postal.placeholder = 'e.g. 4009';
                if (hint) hint.textContent = "We don't have this town's postal code yet. Please type it (4 digits).";
            } else {
                postal.value = '';
                postal.readOnly = true;
                postal.placeholder = 'Select municipality';
                if (hint) hint.textContent = 'Automatically filled when available';
            }
        }

        function townChanged() {
            fillBarangays('');
            typedPostal = '';
            fillPostal();
        }

        province.addEventListener('change', townChanged);
        city.addEventListener('change', townChanged);
        select.addEventListener('change', fillPostal);
        text.addEventListener('input', fillPostal);
        postal.addEventListener('input', function () {
            if (!postal.readOnly) {
                postal.value = postal.value.replace(/\D/g, '').slice(0, 4);
                typedPostal = postal.value;
            }
        });

        fillBarangays(keepBarangay);
        Promise.all([load(o.data + '/barangays.json'), load(o.data + '/postal-codes.json')]).then(function (r) {
            barangays = r[0] || {};
            zips = r[1] || { towns: {}, places: {} };
            fillBarangays(keepBarangay);
            fillPostal();
            // Let the page's address preview pick up the restored values.
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function initAgeCalc(birthdateId, ageId) {
        var birthdateInput = document.getElementById(birthdateId);
        var ageInput = document.getElementById(ageId);

        if (!birthdateInput || !ageInput) return;

        function compute() {
            if (!birthdateInput.value) {
                ageInput.value = '';
                return;
            }

            var dob = new Date(birthdateInput.value);
            var today = new Date();

            var age = today.getFullYear() - dob.getFullYear();
            var monthDiff = today.getMonth() - dob.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }

            ageInput.value = age >= 0 ? age : '';
        }

        birthdateInput.addEventListener('change', compute);
        compute();
    }

    // One submit per click burst: the button is locked once the form is on
    // its way, so a double click cannot send a second registration/OTP.
    function lockSubmitOnce(form) {
        var button = form.querySelector(".submit-btn");
        if (!button) return;

        form.addEventListener("submit", function (event) {
            if (form.dataset.submitting === "1") {
                event.preventDefault();
                return;
            }
            form.dataset.submitting = "1";
            button.disabled = true;
            button.dataset.label = button.innerHTML;
            button.textContent = "Please wait…";
        });

        // Coming back with the browser Back button: unlock again.
        window.addEventListener("pageshow", function () {
            form.dataset.submitting = "";
            button.disabled = false;
            if (button.dataset.label) button.innerHTML = button.dataset.label;
        });
    }

    // Mobile number: only a valid 09XXXXXXXXX can be typed. Letters and
    // symbols never appear, the first digits are always 09, and it stops at
    // 11 digits. A pasted +63 917 123 4567 becomes 09171234567.
    function onlyPhilippineMobile(input) {
        input.setAttribute("inputmode", "numeric");
        input.setAttribute("pattern", "09\\d{9}");
        input.setAttribute("title", "Enter all 11 digits of your mobile number.");

        function clean(value) {
            var digits = String(value).replace(/\D/g, "");
            if (digits.indexOf("63") === 0) digits = "0" + digits.slice(2);
            if (digits.charAt(0) === "9") digits = "0" + digits;
            if (digits !== "" && digits.charAt(0) !== "0") return "";
            if (digits.length > 1 && digits.charAt(1) !== "9") return "0";
            return digits.slice(0, 11);
        }

        input.addEventListener("input", function () {
            var cleaned = clean(input.value);
            if (cleaned !== input.value) input.value = cleaned;
        });
        input.value = clean(input.value);
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("form[enctype=\"multipart/form-data\"]").forEach(lockSubmitOnce);
        document.querySelectorAll("form input[name=\"phone\"]").forEach(onlyPhilippineMobile);
    });

    window.initAddressCascade = initAddressCascade;
    window.initRegionFilter = initRegionFilter;
    window.initBarangayAndPostal = initBarangayAndPostal;
    window.initAgeCalc = initAgeCalc;

})();
