<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Register a Sorting Center — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/views/logistics-register.css') }}">
    <style>
        .field-label { display: block; font-size: 12px; font-weight: 700; color: #463c5e; margin-bottom: 8px; }
        .service-areas { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 6px 14px; }
        .service-area { display: flex; align-items: flex-start; gap: 8px; font-size: 12px; font-weight: 500; color: var(--theme-ink); cursor: pointer; margin: 0; }
        .service-area input { width: auto; margin-top: 2px; accent-color: var(--theme-primary); }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="{{ route('logistics.home') }}" class="logo"><img src="{{ vasset('images/logo-wordmark.svg') }}" alt="BoomBuy" class="bb-logo-wordmark" width="131" height="46"><span class="portal-tag">Logistics</span></a>
        <a href="{{ route('logistics.home') }}" class="back-btn">← Back</a>
    </nav>

    <div class="wrapper">

    <div class="card">

        <div class="icon"><i class="bi bi-truck"></i></div>

        <div class="eyebrow">Partner with BoomBuy</div>
        <h1>Create your Logistics account</h1>
        <p class="subtitle">Sign up to manage parcel sorting and deliveries for your area.</p>

        @if ($errors->any())
            <div class="error-box">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="error-box">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('logistics.register') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-grid">

                <div class="section-label">Contact Person</div>

                <div class="field">
                    <label for="last_name">Contact Person Last Name</label>
                    <input type="text" id="last_name" name="last_name" autocapitalize="words" value="{{ old('last_name') }}" minlength="2" maxlength="50" pattern="[A-Za-zÀ-ÖØ-öø-ÿÑñ][A-Za-zÀ-ÖØ-öø-ÿÑñ .'\-]{1,49}" title="2 to 50 letters. Spaces, hyphens (-), apostrophes (') and periods (.) are allowed." required>
                </div>

                <div class="field">
                    <label for="first_name">Contact Person First Name</label>
                    <input type="text" id="first_name" name="first_name" autocapitalize="words" value="{{ old('first_name') }}" minlength="2" maxlength="50" pattern="[A-Za-zÀ-ÖØ-öø-ÿÑñ][A-Za-zÀ-ÖØ-öø-ÿÑñ .'\-]{1,49}" title="2 to 50 letters. Spaces, hyphens (-), apostrophes (') and periods (.) are allowed." required>
                </div>

                <div class="field">
                    <label for="middle_initial">Middle Initial</label>
                    <input type="text" id="middle_initial" name="middle_initial" autocapitalize="words" maxlength="5" value="{{ old('middle_initial') }}" placeholder="Optional">
                </div>

                <div class="field">
                    <label for="sex">Sex</label>
                    <select id="sex" name="sex" required>
                        <option value="">Select Sex</option>
                        <option value="Male" {{ old('sex') === 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('sex') === 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>

                <div class="field">
                    <label for="birthdate">Birthday</label>
                    <input type="date" id="birthdate" name="birthdate" value="{{ old('birthdate') }}" max="{{ now()->subYears(18)->toDateString() }}" required>
                </div>

                <div class="field">
                    <label for="age">Age</label>
                    <input type="text" id="age" name="age" value="{{ old('age') }}" readonly placeholder="Auto-computed">
                </div>

                <div class="field">
                    <label for="position">Position</label>
                    <input type="text" id="position" name="position" value="{{ old('position') }}" maxlength="100" placeholder="e.g. Operations Manager" required>
                </div>

                <div class="field">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                    >
                </div>

                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        inputmode="tel"
                        maxlength="16"
                        pattern="\s*(09|\+639)(\s*\d){9}\s*"
                        title="Enter all 11 digits of your mobile number."
                        value="{{ old('phone') }}"
                        placeholder="09XXXXXXXXX"
                        required
                    >
                </div>

                <div class="section-label">Company Address</div>

                <div class="field">
                    <label for="region">Region</label>
                    <select id="region" name="region" required>
                        <option value="">Select Region</option>
                        @foreach (\App\Support\PhLocations::REGION_NAMES as $regionName)
                            <option value="{{ $regionName }}" {{ old('region') === $regionName ? 'selected' : '' }}>{{ $regionName }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="province">Province</label>
                    <select id="province" name="province" required data-old="{{ old('province') }}">
                        <option value="">Select Province</option>
                    </select>
                </div>

                <div class="field">
                    <label for="city_municipality">City / Municipality</label>
                    <select id="city_municipality" name="city_municipality" required disabled>
                        <option value="">Select City / Municipality</option>
                    </select>
                </div>

                <div class="field">
                    <label for="barangay">Barangay</label>
                    <input type="text" id="barangay" name="barangay" value="{{ old('barangay') }}" required>
                </div>

                <div class="field">
                    <label for="street_address">House No. / Street</label>
                    <input type="text" id="street_address" name="street_address" value="{{ old('street_address') }}" required>
                </div>

                <div class="field">
                    <label for="postal_code">Postal Code</label>
                    <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" inputmode="numeric" minlength="4" maxlength="4" pattern="\d{4}" title="Exactly 4 digits" placeholder="e.g. 1603" required>
                </div>

                <div class="section-label">Business Information</div>

                <div class="field field-full">
                    <label for="business_name">Company Name</label>
                    <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" maxlength="100" placeholder="e.g. Bilis Hatid Express Inc." required>
                </div>

                <div class="field">
                    <label for="sec_registration_no">SEC Registration No.</label>
                    <input type="text" id="sec_registration_no" name="sec_registration_no" value="{{ old('sec_registration_no') }}" maxlength="11" pattern="[A-Za-z]{2}\d{9}" title="2 letters followed by 9 digits, e.g. CS202012345" placeholder="CS202012345" required>
                </div>

                <div class="field">
                    <label for="tin">TIN</label>
                    <input type="text" id="tin" name="tin" value="{{ old('tin') }}" inputmode="numeric" maxlength="15" pattern="\d{3}-?\d{3}-?\d{3}-?\d{3}" title="12 digits: 000-000-000-000" placeholder="000-000-000-000" required>
                    <span class="field-hint">9-digit TIN plus the 3-digit branch code (000 for the main office).</span>
                </div>

                <div class="field field-full">
                    <span class="field-label" id="service-areas-label">Service Areas <span style="font-weight:500; color:#857ca0;">(choose at least one region)</span></span>
                    <div class="service-areas" role="group" aria-labelledby="service-areas-label">
                        @foreach (\App\Support\PhLocations::REGION_NAMES as $regionName)
                            <label class="service-area">
                                <input type="checkbox" name="service_areas[]" value="{{ $regionName }}" {{ in_array($regionName, (array) old('service_areas', []), true) ? 'checked' : '' }}>
                                <span>{{ $regionName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="section-label">
                    Verification
                    <p>We need to confirm your business before your logistics account goes live.</p>
                </div>

                <div class="field">
                    <label for="sec_certificate">SEC Certificate</label>
                    <input
                        type="file"
                        id="sec_certificate"
                        name="sec_certificate"
                        accept=".jpg,.jpeg,.png,.pdf"
                        onchange="showFileName(this, 'sec_certificate-name')"
                        required
                    >
                    <span class="field-hint">SEC Certificate of Registration.</span>
                    <span class="file-name" id="sec_certificate-name"></span>
                </div>

                <div class="field">
                    <label for="bir_2303">BIR Form 2303</label>
                    <input
                        type="file"
                        id="bir_2303"
                        name="bir_2303"
                        accept=".jpg,.jpeg,.png,.pdf"
                        onchange="showFileName(this, 'bir_2303-name')"
                        required
                    >
                    <span class="field-hint">BIR Certificate of Registration (Form 2303).</span>
                    <span class="file-name" id="bir_2303-name"></span>
                </div>

                <div class="field">
                    <label for="id_photo">Valid ID of Authorized Representative</label>
                    <input
                        type="file"
                        id="id_photo"
                        name="id_photo"
                        accept=".jpg,.jpeg,.png,.pdf"
                        onchange="showFileName(this, 'id_photo-name')"
                        required
                    >
                    <span class="field-hint">Any government-issued ID (UMID, driver's license, passport, etc.)</span>
                    <span class="file-name" id="id_photo-name"></span>
                </div>

                <div class="field">
                    <label for="business_permit">Mayor's / Business Permit</label>
                    <input
                        type="file"
                        id="business_permit"
                        name="business_permit"
                        accept=".jpg,.jpeg,.png,.pdf"
                        onchange="showFileName(this, 'business_permit-name')"
                        required
                    >
                    <span class="field-hint">JPG, PNG or PDF, up to 5 MB each.</span>
                    <span class="file-name" id="business_permit-name"></span>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}"
                            title="At least 8 characters with an uppercase letter, a lowercase letter, a number and a special character."
                            minlength="8"
                            required
                        >
                        <button
                            type="button"
                            class="password-toggle"
                            data-target="password"
                            aria-label="Show password"
                        ><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
                    </div>
                    <span class="field-hint">At least 8 characters, with an uppercase letter, a lowercase letter, a number and a special character (e.g. @, #, !).</span>
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirm Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            required
                        >
                        <button
                            type="button"
                            class="password-toggle"
                            data-target="password_confirmation"
                            aria-label="Show password"
                        ><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
                    </div>
                    <span class="field-error-msg" id="password-match-msg">
                        Passwords do not match.
                    </span>
                </div>

                <label class="terms-check">
                    <input type="checkbox" name="terms" required>
                    <span>
                        I agree to the
                        <a href="#" onclick="openTerms(event)">Terms &amp; Conditions</a>
                        and
                        <a href="#" onclick="openPrivacy(event)">Privacy Policy</a>.
                    </span>
                </label>

                <button type="submit" class="submit-btn">
                    Create Logistics Account
                </button>

            </div>

        </form>

        <div class="footer-text">
            Already have an account?
            <a href="{{ route('logistics.login') }}">Login</a>
        </div>

    </div>

    </div>

    @include('partials.terms-modal')

    <script src="{{ asset('js/data/psgc-data.js') }}"></script>
    <script src="{{ asset('js/pages/registration-fields.js') }}"></script>
    <script>
        initAddressCascade('province', 'city_municipality', @json(old('city_municipality')));
        initRegionFilter('region', 'province', 'city_municipality', 'barangay', @json(\App\Support\PhLocations::provincesByRegionName()));

        // At least one service area: the first checkbox carries the rule.
        (function () {
            var boxes = document.querySelectorAll('input[name="service_areas[]"]');
            function check() {
                var any = Array.prototype.some.call(boxes, function (box) { return box.checked; });
                boxes[0].setCustomValidity(any ? '' : 'Choose at least one region you deliver in.');
            }
            boxes.forEach(function (box) { box.addEventListener('change', check); });
            check();
        })();
        initAgeCalc('birthdate', 'age');

        function showFileName(input, targetId) {
            var target = document.getElementById(targetId);
            if (!target) return;

            target.textContent = '';
            if (input.files && input.files[0]) {
                var icon = document.createElement('i');
                icon.className = 'bi bi-check-circle-fill';
                target.appendChild(icon);
                target.appendChild(document.createTextNode(' ' + input.files[0].name));
            }
        }

        (function () {
            var EYE_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
            var EYE_OFF_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

            document.querySelectorAll('.password-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var input = document.getElementById(btn.dataset.target);
                    if (!input) return;

                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.innerHTML = show ? EYE_ICON : EYE_OFF_ICON;
                    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                });
            });

            var pwd = document.getElementById('password');
            var confirmPwd = document.getElementById('password_confirmation');
            var msg = document.getElementById('password-match-msg');

            if (pwd && confirmPwd) {
                function checkMatch() {
                    if (confirmPwd.value === '') {
                        confirmPwd.classList.remove('input-error');
                        if (msg) msg.style.display = 'none';
                        return;
                    }

                    if (confirmPwd.value !== pwd.value) {
                        confirmPwd.classList.add('input-error');
                        if (msg) msg.style.display = 'block';
                    } else {
                        confirmPwd.classList.remove('input-error');
                        if (msg) msg.style.display = 'none';
                    }
                }

                pwd.addEventListener('input', checkMatch);
                confirmPwd.addEventListener('input', checkMatch);
            }

            var form = document.querySelector('form');

            if (form) {
                form.addEventListener('submit', function () {
                    form.querySelectorAll('[required]').forEach(function (field) {
                        if (!field.checkValidity()) {
                            field.classList.add('input-error');
                        }
                    });
                });

                form.querySelectorAll('[required]').forEach(function (field) {
                    ['input', 'change'].forEach(function (evt) {
                        field.addEventListener(evt, function () {
                            if (field.checkValidity()) {
                                field.classList.remove('input-error');
                            }
                        });
                    });
                });
            }
        })();
    </script>

    @include('partials.pwa-register')

</body>
</html>
