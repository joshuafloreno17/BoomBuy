<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Seller Registration — BoomBuy', 'fonts' => true])

    <link rel="stylesheet" href="{{ vasset('css/views/buyer-register.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/views/register-shell.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/views/seller-register.css') }}">
</head>
<body class="reg-night">

@php
    // Which wizard step each field lives on, so a failed submit reopens
    // the step with the first error.
    $fieldStep = [
        'last_name' => 1, 'first_name' => 1, 'middle_initial' => 1, 'sex' => 1,
        'birthdate' => 1, 'age' => 1, 'email' => 1, 'phone' => 1,
        'business_name' => 2, 'business_category' => 2, 'seller_type' => 2, 'tin' => 2,
        'region' => 3, 'province' => 3, 'city_municipality' => 3, 'barangay' => 3,
        'street_address' => 3, 'postal_code' => 3,
        'national_id' => 4, 'dti_certificate' => 4, 'sec_certificate' => 4, 'bir_2303' => 4,
        'business_permit' => 4, 'password' => 4, 'password_confirmation' => 4, 'terms' => 4,
    ];
    $startStep = 1;
    if ($errors->any()) {
        $startStep = collect($errors->keys())->map(fn ($k) => $fieldStep[$k] ?? 1)->min();
    }

    $docIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>';
    $eyeOff = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    // Upload rows: [name, label, hint, data-seller-doc (null = always shown), required?]
    $documents = [
        ['national_id', 'Government-issued ID', 'UMID, driver’s license, passport, etc. For a corporation, the ID of the representative.', null, true],
        ['dti_certificate', 'DTI Certificate', 'DTI Certificate of Business Name Registration.', 'Sole Proprietorship', false],
        ['sec_certificate', 'SEC Certificate', 'SEC Certificate of Registration.', 'Corporation', false],
        ['bir_2303', 'BIR Form 2303', 'BIR Certificate of Registration (Form 2303).', 'Sole Proprietorship|Corporation', false],
        ['business_permit', 'Mayor’s / Business Permit (optional)', 'JPG, PNG or PDF, up to 5 MB each.', null, false],
    ];
@endphp

<div class="reg">

    @include('partials.register-side', [
        'kicker' => 'Seller account',
        'headline' => ['Let’s open', 'your shop.'],
        'blurb' => 'Tell us about you and your business. The ring fills up as you go.',
        'steps' => [
            ['Personal info', 'user'],
            ['Business details', 'brief'],
            ['Shop address', 'pin'],
            ['Documents', 'docs'],
        ],
    ])

    <main class="reg-main">
    <div class="reg-inner">

        <div class="reg-head">
            <div class="reg-card-top">
                <span class="reg-kicker" id="reg-step-label">Seller account · Step 1 of 4</span>
                <a href="{{ route('login') }}#join" class="reg-back"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>Change role</a>
            </div>

            <ol class="reg-bars" id="reg-steps">
                @foreach (['Personal info', 'Business details', 'Shop address', 'Documents'] as $i => $name)
                    <li data-step="{{ $i + 1 }}">
                        <button type="button" class="reg-bar" data-goto="{{ $i + 1 }}" aria-label="Step {{ $i + 1 }}: {{ $name }}"></button>
                    </li>
                @endforeach
            </ol>
        </div>

        @if ($errors->any())
            <div class="error-box" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="error-box" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('seller.register') }}" enctype="multipart/form-data" id="reg-form" novalidate>
            @csrf

            {{-- Step 1 --}}
            <section class="reg-panel" data-panel="1">
                <div>
                    <h3>About you</h3>
                    <p class="reg-panel-sub">Enter your name exactly as it appears on your valid ID.</p>
                </div>

                <div class="reg-grid">
                    <div class="field">
                        <label for="last_name">Last name</label>
                        <input type="text" id="last_name" name="last_name" autocapitalize="words" value="{{ old('last_name') }}" autocomplete="family-name" minlength="2" maxlength="50" pattern="[A-Za-zÀ-ÖØ-öø-ÿÑñ][A-Za-zÀ-ÖØ-öø-ÿÑñ .'\-]{1,49}" title="2 to 50 letters. Spaces, hyphens (-), apostrophes (') and periods (.) are allowed." required>
                    </div>
                    <div class="field">
                        <label for="first_name">First name</label>
                        <input type="text" id="first_name" name="first_name" autocapitalize="words" value="{{ old('first_name') }}" autocomplete="given-name" minlength="2" maxlength="50" pattern="[A-Za-zÀ-ÖØ-öø-ÿÑñ][A-Za-zÀ-ÖØ-öø-ÿÑñ .'\-]{1,49}" title="2 to 50 letters. Spaces, hyphens (-), apostrophes (') and periods (.) are allowed." required>
                    </div>
                    <div class="field">
                        <label for="middle_initial">Middle initial <span class="opt">(optional)</span></label>
                        <input type="text" id="middle_initial" name="middle_initial" autocapitalize="words" maxlength="5" value="{{ old('middle_initial') }}">
                    </div>
                </div>

                <div class="reg-grid">
                    <div class="field">
                        <fieldset>
                            <legend>Sex</legend>
                            <div class="reg-pills">
                                <label class="reg-pill"><input type="radio" name="sex" value="Male" {{ old('sex') === 'Male' ? 'checked' : '' }} required>Male</label>
                                <label class="reg-pill"><input type="radio" name="sex" value="Female" {{ old('sex') === 'Female' ? 'checked' : '' }}>Female</label>
                            </div>
                        </fieldset>
                    </div>
                    <div class="field">
                        <label for="birthdate">Birthday</label>
                        <input type="date" id="birthdate" name="birthdate" value="{{ old('birthdate') }}" max="{{ now()->subYears(18)->toDateString() }}" required>
                    </div>
                    <div class="field">
                        <label for="age">Age</label>
                        <input type="text" id="age" name="age" value="{{ old('age') }}" readonly tabindex="-1" placeholder="Auto">
                    </div>
                </div>

                <div class="reg-grid wide">
                    <div class="field">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="you@example.com" required>
                    </div>
                    <div class="field">
                        <label for="phone">Phone number</label>
                        <input type="text" id="phone" name="phone" inputmode="tel" autocomplete="tel" maxlength="16" pattern="\s*(09|\+639)(\s*\d){9}\s*" title="Enter all 11 digits of your mobile number." value="{{ old('phone') }}" placeholder="09XXXXXXXXX" required>
                    </div>
                </div>
            </section>

            {{-- Step 2 --}}
            <section class="reg-panel" data-panel="2" hidden>
                <div>
                    <h3>Your business</h3>
                    <p class="reg-panel-sub">This is the name buyers will see on your shop.</p>
                </div>

                <div class="field">
                    <label for="business_name">Business name</label>
                    <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" minlength="3" maxlength="30" placeholder="e.g. Juan's Online Store" required>
                </div>

                <div class="reg-grid wide">
                    <div class="field">
                        <label for="business_category">Line of business / category</label>
                        <select id="business_category" name="business_category" required>
                            <option value="">Select category</option>
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
                        <label for="seller_type">Seller type</label>
                        <select id="seller_type" name="seller_type" required>
                            <option value="">Select seller type</option>
                            @foreach (\App\Support\SellerTypes::TYPES as $sellerType)
                                <option value="{{ $sellerType }}" {{ old('seller_type') === $sellerType ? 'selected' : '' }}>{{ $sellerType }}</option>
                            @endforeach
                        </select>
                        <span class="field-hint">This decides which documents you upload later.</span>
                    </div>
                </div>

                <div class="field">
                    <label for="tin">TIN</label>
                    <input type="text" id="tin" name="tin" value="{{ old('tin') }}" inputmode="numeric" maxlength="15" pattern="\d{3}-?\d{3}-?\d{3}-?\d{3}" title="12 digits: 000-000-000-000" placeholder="000-000-000-000" required>
                    <span class="field-hint">9-digit TIN plus the 3-digit branch code (000 for the main office).</span>
                </div>
            </section>

            {{-- Step 3 --}}
            <section class="reg-panel" data-panel="3" hidden>
                <div>
                    <h3>Shop address</h3>
                    <p class="reg-panel-sub">Where a BoomBuy rider picks up your orders.</p>
                </div>

                <div class="reg-grid reg-addr">
                    <div class="field">
                        <label for="region">Region</label>
                        <select id="region" name="region" required>
                            <option value="">Select region</option>
                            @foreach (\App\Support\PhLocations::REGION_NAMES as $regionName)
                                <option value="{{ $regionName }}" {{ old('region') === $regionName ? 'selected' : '' }}>{{ $regionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="province">Province</label>
                        <select id="province" name="province" required data-old="{{ old('province') }}">
                            <option value="">Select province</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="city_municipality">City / Municipality</label>
                        <select id="city_municipality" name="city_municipality" required disabled>
                            <option value="">Select city / municipality</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="barangay">Barangay</label>
                        <input type="text" id="barangay" name="barangay" value="{{ old('barangay') }}" required>
                    </div>

                    <div class="field">
                        <label for="street_address">House no. / Street</label>
                        <input type="text" id="street_address" name="street_address" value="{{ old('street_address') }}" required>
                    </div>

                    <div class="field">
                        <label for="postal_code">Postal code</label>
                        <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" inputmode="numeric" minlength="4" maxlength="4" pattern="\d{4}" title="Exactly 4 digits" placeholder="e.g. 1121" required>
                    </div>
                </div>
            </section>

            {{-- Step 4 --}}
            <section class="reg-panel" data-panel="4" hidden>
                <div>
                    <h3>Documents and password</h3>
                    <p class="reg-panel-sub">We confirm you’re a real seller before your store goes live.</p>
                </div>

                <div class="doc-list">
                    @foreach ($documents as [$name, $label, $hint, $type, $required])
                        <div class="field" @if ($type) data-seller-doc="{{ $type }}" @endif>
                            <div>
                                <label class="doc-row" for="{{ $name }}">
                                    <span class="doc-icon">{!! $docIcon !!}</span>
                                    <span class="doc-text">
                                        <strong>{{ $label }}</strong>
                                        <small data-hint="{{ $hint }}">{{ $hint }}</small>
                                    </span>
                                    <span class="doc-btn">Choose file</span>
                                    <input type="file" id="{{ $name }}" name="{{ $name }}" accept=".jpg,.jpeg,.png,.pdf" @if ($required) required @endif>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="reg-grid wide">
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                autocomplete="new-password"
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
                            >{!! $eyeOff !!}</button>
                        </div>
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirm password</label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                autocomplete="new-password"
                                required
                            >
                            <button
                                type="button"
                                class="password-toggle"
                                data-target="password_confirmation"
                                aria-label="Show password"
                            >{!! $eyeOff !!}</button>
                        </div>
                        <span class="field-error-msg" id="password-match-msg">
                            Passwords do not match.
                        </span>
                    </div>
                </div>

                <span class="field-hint">At least 8 characters, with an uppercase letter, a lowercase letter, a number and a special character (e.g. @, #, !).</span>

                <div class="field">
                    <label class="terms-check">
                        <input type="checkbox" name="terms" required>
                        <span>
                            I agree to the
                            <a href="#" onclick="openTerms(event)">Terms &amp; Conditions</a>
                            and
                            <a href="#" onclick="openPrivacy(event)">Privacy Policy</a>.
                        </span>
                    </label>
                </div>
            </section>

            <div class="reg-actions">
                <button type="button" class="reg-btn reg-btn-ghost" id="reg-back" hidden>Back</button>
                <button type="button" class="reg-btn reg-btn-primary" id="reg-next">Continue <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
                <button type="submit" class="reg-btn reg-btn-primary submit-btn" id="reg-submit" hidden>Create seller account</button>
            </div>

            <p class="reg-private"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Your documents are only used to verify your shop.</p>
        </form>

        <p class="reg-signin">Already have an account? <a href="{{ route('login') }}">Log in</a></p>

    </div>
    </main>

</div>

@include('partials.terms-modal')

<script src="{{ asset('js/data/psgc-data.js') }}"></script>
<script src="{{ asset('js/pages/registration-fields.js') }}"></script>
<script src="{{ vasset('js/pages/register-wizard.js') }}"></script>
<script>
    initAddressCascade('province', 'city_municipality', @json(old('city_municipality')));
    initRegionFilter('region', 'province', 'city_municipality', 'barangay', @json(\App\Support\PhLocations::provincesByRegionName()));
    initAgeCalc('birthdate', 'age');

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

    initRegisterWizard({ kicker: 'Seller account', startStep: {{ (int) $startStep }} });
</script>

@include('partials.pwa-register')

</body>
</html>
