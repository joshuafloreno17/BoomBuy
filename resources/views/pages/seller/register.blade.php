<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Seller Registration — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/seller-register.css') }}">
</head>
<body>

    <nav class="navbar">
        <a href="{{ route('home') }}" class="logo"><img src="{{ asset('images/icon.svg') }}" alt="" class="bb-logo-mark" width="32" height="32"><span class="bb-logo-word">BoomBuy</span></a>
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
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                </div>

                <div class="field">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                </div>

                <div class="field">
                    <label for="middle_initial">Middle Initial</label>
                    <input type="text" id="middle_initial" name="middle_initial" maxlength="5" value="{{ old('middle_initial') }}" placeholder="Optional">
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
                    <input type="date" id="birthdate" name="birthdate" value="{{ old('birthdate') }}" required>
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
                        value="{{ old('phone') }}"
                        placeholder="09XX XXX XXXX"
                        required
                    >
                </div>

                <div class="section-label">Address</div>

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

                <div class="section-label">Business Information</div>

                <div class="field">
                    <label for="business_name">Business Name</label>
                    <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" placeholder="e.g. Juan's Online Store" required>
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

                <div class="section-label">
                    Seller Verification
                    <p>We need to confirm you're a real seller before your store goes live.</p>
                </div>

                <div class="field">
                    <label for="national_id">Valid Government ID</label>
                    <input
                        type="file"
                        id="national_id"
                        name="national_id"
                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                        onchange="showFileName(this, 'national_id-name')"
                        required
                    >
                    <span class="field-hint">Any government-issued ID (UMID, driver's license, passport, etc.)</span>
                    <span class="file-name" id="national_id-name"></span>
                </div>

                <div class="field">
                    <label for="business_permit">Proof of Business</label>
                    <input
                        type="file"
                        id="business_permit"
                        name="business_permit"
                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                        onchange="showFileName(this, 'business_permit-name')"
                        required
                    >
                    <span class="field-hint">DTI registration, business permit, or any proof that you sell these goods</span>
                    <span class="file-name" id="business_permit-name"></span>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
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
                    <span class="field-hint">At least 8 characters.</span>
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
