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
            provinceSelect.appendChild(placeholder.cloneNode(true));
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

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("form[enctype=\"multipart/form-data\"]").forEach(lockSubmitOnce);
    });

    window.initAddressCascade = initAddressCascade;
    window.initRegionFilter = initRegionFilter;
    window.initAgeCalc = initAgeCalc;

})();
