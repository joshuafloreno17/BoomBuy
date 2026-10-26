<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Seller Registration — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/seller-register.css') }}">
</head>
<body>

    <nav class="navbar">
        <a href="{{ route('home') }}" class="logo"><img src="{{ vasset('images/logo-wordmark.svg') }}" alt="BoomBuy" class="bb-logo-wordmark" width="131" height="46"></a>
        <a href="{{ route('register') }}" class="back-btn">← Back</a>
    </nav>

    <div class="wrapper">

    <div class="card">

        <div class="icon"><i class="bi bi-shop"></i></div>

        <div class="eyebrow">Sell on BoomBuy</div>
        <h1>Create your seller account</h1>
        <p class="subtitle">Sign up to open your own store on BoomBuy.</p>

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

        <form method="POST" action="{{ route('seller.register') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-grid">

                <div class="section-label">Personal Information</div>

                <div class="field">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" autocapitalize="words" value="{{ old('last_name') }}" minlength="2" maxlength="50" pattern="[A-Za-zÀ-ÖØ-öø-ÿÑñ][A-Za-zÀ-ÖØ-öø-ÿÑñ .'\-]{1,49}" title="2 to 50 letters. Spaces, hyphens (-), apostrophes (') and periods (.) are allowed." required>
                </div>

                <div class="field">
                    <label for="first_name">First Name</label>
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

                <div class="section-label">Address</div>

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
                    <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" inputmode="numeric" minlength="4" maxlength="4" pattern="\d{4}" title="Exactly 4 digits" placeholder="e.g. 1121" required>
                </div>

                <div class="section-label">Business Information</div>

                <div class="field">
                    <label for="business_name">Business Name</label>
                    <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" minlength="3" maxlength="30" placeholder="e.g. Juan's Online Store" required>
                </div>

                <div class="field">
                    <label for="business_category">Line of Business / Category</label>
                    <select id="business_category" name="business_category" required>
                        <option value="">Select Category</option>
                        <option value="electronics" {{ old('business_category') === 'electronics' ? 'selected' : '' }}>Electronics</option>
                        <option value="womens-fashion" {{ old('business_category') === 'womens-fashion' ? 'selected' : '' }}>Women's Fashion</option>
                        <option value="mens-fashion" {{ old('business_category') === 'mens-fashion' ? 'selected' : '' }}>Men's Fashion</option>
                        <option value="kids-baby" {{ old('business_category') === 'kids-baby' ? 'selected' : '' }}>Kids &amp; Baby</option>
                        <option value="home-living" {{ old('business_category') === 'home-living' ? 'selected' : '' }}>Home &amp; Living</option>
                        <option value="sports-outdoors" {{ old('business_category') === 'sports-outdoors' ? 'selected' : '' }}>Sports &amp; Outdoors</option>
                        <option value="beauty-personal-care" {{ old('business_category') === 'beauty-personal-care' ? 'selected' : '' }}>Beauty &amp; Personal Care</option>
                        <option value="food-beverages" {{ old('business_category') === 'food-beverages' ? 'selected' : '' }}>Food &amp; Beverages</option>
                        <option value="automotive" {{ old('business_category') === 'automotive' ? 'selected' : '' }}>Automotive</option>
                        <option value="office-school" {{ old('business_category') === 'office-school' ? 'selected' : '' }}>Office &amp; School</option>
                        <option value="pet-supplies" {{ old('business_category') === 'pet-supplies' ? 'selected' : '' }}>Pet Supplies</option>
                        <option value="toys-games-hobbies" {{ old('business_category') === 'toys-games-hobbies' ? 'selected' : '' }}>Toys, Games &amp; Hobbies</option>
                        <option value="jewelry-accessories" {{ old('business_category') === 'jewelry-accessories' ? 'selected' : '' }}>Jewelry &amp; Accessories</option>
                        <option value="shoes" {{ old('business_category') === 'shoes' ? 'selected' : '' }}>Shoes</option>
                        <option value="tools-home-improvement" {{ old('business_category') === 'tools-home-improvement' ? 'selected' : '' }}>Tools &amp; Home Improvement</option>
                        <option value="garden-outdoor" {{ old('business_category') === 'garden-outdoor' ? 'selected' : '' }}>Garden &amp; Outdoor</option>
                    </select>
                </div>

                <div class="field">
                    <label for="seller_type">Seller Type</label>
                    <select id="seller_type" name="seller_type" required>
                        <option value="">Select Seller Type</option>
                        @foreach (\App\Support\SellerTypes::TYPES as $sellerType)
                            <option value="{{ $sellerType }}" {{ old('seller_type') === $sellerType ? 'selected' : '' }}>{{ $sellerType }}</option>
                        @endforeach
                    </select>
                    <span class="field-hint">This decides which documents you upload below.</span>
                </div>

                <div class="field">
                    <label for="tin">TIN</label>
                    <input type="text" id="tin" name="tin" value="{{ old('tin') }}" inputmode="numeric" maxlength="15" pattern="\d{3}-?\d{3}-?\d{3}-?\d{3}" title="12 digits: 000-000-000-000" placeholder="000-000-000-000" required>
                    <span class="field-hint">9-digit TIN plus the 3-digit branch code (000 for the main office).</span>
                </div>

                <div class="section-label">
                    Seller Verification
                    <p>We need to confirm you're a real seller before your store goes live.</p>
                </div>

                <div class="field">
                    <label for="national_id">Government-issued ID</label>
                    <input
                        type="file"
                        id="national_id"
                        name="national_id"
                        accept=".jpg,.jpeg,.png,.pdf"
                        onchange="showFileName(this, 'national_id-name')"
                        required
                    >
                    <span class="field-hint">Any government-issued ID (UMID, driver's license, passport, etc.). For a corporation, the ID of the representative.</span>
                    <span class="file-name" id="national_id-name"></span>
                </div>
                <div class="field" data-seller-doc="Sole Proprietorship">
                    <label for="dti_certificate">DTI Certificate</label>
                    <input
                        type="file"
                        id="dti_certificate"
                        name="dti_certificate"
                        accept=".jpg,.jpeg,.png,.pdf"
                        onchange="showFileName(this, 'dti_certificate-name')"
                    >
                    <span class="field-hint">DTI Certificate of Business Name Registration.</span>
                    <span class="file-name" id="dti_certificate-name"></span>
                </div>

                <div class="field" data-seller-doc="Corporation">
                    <label for="sec_certificate">SEC Certificate</label>
                    <input
                        type="file"
                        id="sec_certificate"
                        name="sec_certificate"
                        accept=".jpg,.jpeg,.png,.pdf"
                        onchange="showFileName(this, 'sec_certificate-name')"
                    >
                    <span class="field-hint">SEC Certificate of Registration.</span>
                    <span class="file-name" id="sec_certificate-name"></span>
                </div>

                <div class="field" data-seller-doc="Sole Proprietorship|Corporation">
                    <label for="bir_2303">BIR Form 2303</label>
                    <input
                        type="file"
                        id="bir_2303"
                        name="bir_2303"
                        accept=".jpg,.jpeg,.png,.pdf"
                        onchange="showFileName(this, 'bir_2303-name')"
                    >
                    <span class="field-hint">BIR Certificate of Registration (Form 2303).</span>
                    <span class="file-name" id="bir_2303-name"></span>
                </div>


                <div class="field">
                    <label for="business_permit">Mayor's / Business Permit <span style="font-weight:500; color:#857ca0;">(optional)</span></label>
                    <input
                        type="file"
                        id="business_permit"
                        name="business_permit"
                        accept=".jpg,.jpeg,.png,.pdf"
                        onchange="showFileName(this, 'business_permit-name')"
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
                    Create Seller Account
                </button>

            </div>

        </form>

        <div class="footer-text">
            Already have an account?
            <a href="{{ route('login') }}">Login</a>
        </div>

    </div>

    </div>

    @include('partials.terms-modal')

    <script src="{{ asset('js/data/psgc-data.js') }}"></script>
    <script src="{{ asset('js/pages/registration-fields.js') }}"></script>
    <script>
        initAddressCascade('province', 'city_municipality', @json(old('city_municipality')));
        initRegionFilter('region', 'province', 'city_municipality', 'barangay', @json(\App\Support\PhLocations::provincesByRegionName()));

        // Only the documents of the chosen seller type are shown and required.
        (function () {
            var typeSelect = document.getElementById('seller_type');
            var docs = document.querySelectorAll('[data-seller-doc]');

            function showDocs() {
                docs.forEach(function (field) {
                    var on = field.getAttribute('data-seller-doc').split('|').indexOf(typeSelect.value) !== -1;
                    var input = field.querySelector('input[type=file]');
                    field.style.display = on ? '' : 'none';
                    input.required = on;
                    input.disabled = !on;
                });
            }

            typeSelect.addEventListener('change', showDocs);
            showDocs();
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
