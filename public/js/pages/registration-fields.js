// Shared behavior for the buyer/seller/rider registration forms:
// - Province -> City/Municipality cascading dropdown (data from psgc-data.js)
// - Birthdate -> auto-computed Age display
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

    window.initAddressCascade = initAddressCascade;
    window.initAgeCalc = initAgeCalc;

})();
