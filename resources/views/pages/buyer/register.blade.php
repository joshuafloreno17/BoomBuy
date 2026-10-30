<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Register — BoomBuy', 'fonts' => true])

    <link rel="stylesheet" href="{{ vasset('css/views/buyer-register.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/views/register-shell.css') }}">
</head>
<body class="reg-night">

@php
    // Which wizard step each field lives on, so a failed submit reopens
    // the step with the first error.
    $fieldStep = [
        'last_name' => 1, 'first_name' => 1, 'middle_initial' => 1, 'sex' => 1,
        'birthdate' => 1, 'email' => 1, 'phone' => 1,
        'region' => 2, 'province' => 2, 'city_municipality' => 2, 'barangay' => 2, 'street_address' => 2,
        'street' => 2, 'building' => 2, 'postal_code' => 2,
        'id_photo' => 3, 'password' => 3, 'password_confirmation' => 3,
        'terms' => 4,
    ];
    $startStep = 1;
    if ($errors->any()) {
        $startStep = collect($errors->keys())->map(fn ($k) => $fieldStep[$k] ?? 1)->min();
    }
    // The chosen ID file is lost after a failed submit; ask for it again.
    $reupload = $errors->any() || session('error');
@endphp

<div class="reg">

    @include('partials.register-side', [
        'kicker' => 'Buyer account',
        'headline' => ['Let’s set up', 'your account.'],
        'blurb' => 'A few details at a time. The ring fills up as you go.',
        // One per wizard step, then the email code that follows sign-up.
        'steps' => [
            ['Personal info', 'user'],
            ['Delivery address', 'pin'],
            ['ID and password', 'shield'],
            ['Review', 'review'],
        ],
    ])

    <main class="reg-main">
    <div class="reg-inner">

        <div class="reg-head">
            <div class="reg-card-top">
                <span class="reg-kicker" id="reg-step-label">Buyer account · Step 1 of 4</span>
                <a href="{{ route('login') }}#join" class="reg-back"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>Change role</a>
            </div>

            <ol class="reg-bars" id="reg-steps">
                @foreach (['Personal info', 'Address', 'Verify', 'Review'] as $i => $name)
                    <li data-step="{{ $i + 1 }}">
                        <button type="button" class="reg-bar" data-goto="{{ $i + 1 }}" aria-label="Step {{ $i + 1 }}: {{ $name }}"></button>
                    </li>
                @endforeach
            </ol>
        </div>

        @if ($errors->any() || session('error'))
            <div class="error-box" role="alert">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 7v6M12 17h.01"/></svg>
                <div>
                    @if (session('error'))
                        {{ session('error') }}
                    @else
                        Please fix the highlighted fields.
                    @endif
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('buyer.register') }}" enctype="multipart/form-data" id="reg-form" novalidate>
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
                    <h3>Where should we deliver?</h3>
                    <p class="reg-panel-sub">This becomes your default delivery address.</p>
                </div>

                <div class="reg-grid reg-addr">
                    <div class="field">
                        <label for="region">Region</label>
                        <select id="region" name="region" autocomplete="off">
                            <option value="">Select region</option>
                            @foreach (\App\Support\PhLocations::REGION_NAMES as $regionName)
                                <option value="{{ $regionName }}" @selected(old('region') === $regionName)>{{ $regionName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="province">Province <span class="req">*</span></label>
                        <select id="province" name="province" required data-old="{{ old('province') }}">
                            <option value="">Select region first</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="city_municipality">Municipality <span class="req">*</span></label>
                        <select id="city_municipality" name="city_municipality" required disabled>
                            <option value="">Select province first</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="barangay">Barangay <span class="req">*</span></label>
                        <select id="barangay" name="barangay" required disabled data-old="{{ old('barangay') }}">
                            <option value="">Select city / municipality first</option>
                        </select>
                        {{-- For the few towns with no barangay list: typed instead. --}}
                        <input type="text" id="barangay_text" maxlength="60" placeholder="Type your barangay" hidden disabled>
                    </div>
                    <div class="field">
                        <label for="street">Street <span class="req">*</span></label>
                        <input type="text" id="street" name="street" value="{{ old('street') }}" maxlength="100" autocomplete="address-line1" placeholder="e.g. 123 Rizal Street" required>
                    </div>
                    <div class="field">
                        <label for="building">Building / Subdivision</label>
                        <input type="text" id="building" name="building" value="{{ old('building') }}" maxlength="100" placeholder="Optional">
                    </div>
                    <div class="field reg-addr-full">
                        <label for="postal_code">Postal Code <span class="opt">Auto-generated</span></label>
                        <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" inputmode="numeric" maxlength="4" pattern="\d{4}" title="Exactly 4 digits." placeholder="Select municipality" readonly>
                        <span class="field-hint" id="postal_hint">Automatically filled when available</span>
                    </div>
                </div>

                {{-- What the server stores as the street part: street, building. --}}
                <input type="hidden" id="street_address" name="street_address" value="{{ old('street_address') }}">

                <div class="reg-note">
                    <span class="reg-note-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0a6f66" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
                    <div>
                        <small>Deliver to</small>
                        <strong id="reg-addr-preview">Fill in your address above</strong>
                        <span>Served by the nearest BoomBuy Sorting Center in your province.</span>
                    </div>
                </div>
            </section>

            {{-- Step 3 --}}
            <section class="reg-panel" data-panel="3" hidden>
                <div>
                    <h3>Verify and secure</h3>
                    <p class="reg-panel-sub">Upload a valid ID and choose a password.</p>
                </div>

                <div class="field" id="id-field">
                    <span class="field-label" style="display:block;font-size:14px;font-weight:600;color:var(--theme-ink);margin-bottom:6px">Valid ID</span>
                    <input type="file" id="id_photo" name="id_photo" class="reg-file-input" accept=".jpg,.jpeg,.png,.pdf" required>
                    <label for="id_photo" class="reg-drop" id="id-drop">
                        <span class="reg-drop-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#6d4ad1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4"/><path d="M7 9l5-5 5 5"/><path d="M4 20h16"/></svg></span>
                        <strong>Click or drop your ID here</strong>
                        <small>Any government-issued ID · JPG, PNG or PDF, up to 5 MB</small>
                    </label>
                    <div class="reg-file" id="id-chosen" hidden>
                        <span class="reg-file-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0a6f66" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M14 10h4M14 14h4M6 16h6"/></svg></span>
                        <div class="reg-file-info"><strong id="id-name"></strong><small>Ready for verification</small></div>
                        <label for="id_photo">Replace</label>
                    </div>
                    @if ($reupload)
                        <span class="field-hint">For your security, please choose your ID file again.</span>
                    @endif
                    <div class="reg-chips">
                        <span>Accepted:</span>
                        <span class="reg-chip">PhilSys ID</span>
                        <span class="reg-chip">Driver's License</span>
                        <span class="reg-chip">Passport</span>
                        <span class="reg-chip">UMID</span>
                        <span class="reg-chip">Postal ID</span>
                    </div>
                </div>

                <div class="reg-grid wide">
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}" title="At least 8 characters with an uppercase letter, a lowercase letter, a number and a special character." minlength="8" required>
                            <button type="button" class="password-toggle" data-target="password" aria-label="Show password"></button>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirm password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" data-target="password_confirmation" aria-label="Show password"></button>
                        </div>
                    </div>
                </div>

                <div class="reg-strength">
                    <div class="reg-strength-head"><span>Password strength</span><b id="pw-label">Not set</b></div>
                    <div class="reg-strength-bars" id="pw-bars"><span></span><span></span><span></span><span></span></div>
                    <ul class="reg-rules" id="pw-rules">
                        <li data-rule="len">At least 8 characters</li>
                        <li data-rule="upper">An uppercase letter</li>
                        <li data-rule="lower">A lowercase letter</li>
                        <li data-rule="num">A number</li>
                        <li data-rule="sym">A special character (e.g. @ # !)</li>
                    </ul>
                </div>
            </section>

            {{-- Step 4 --}}
            <section class="reg-panel" data-panel="4" hidden>
                <div>
                    <h3>Review your details</h3>
                    <p class="reg-panel-sub">Make sure everything is correct before creating your account.</p>
                </div>

                <div class="reg-review">
                    <div class="reg-review-head">Personal info <button type="button" data-goto="1">Edit</button></div>
                    <dl>
                        <div><dt>Name</dt><dd data-review="name"></dd></div>
                        <div><dt>Sex · Age</dt><dd data-review="sexage"></dd></div>
                        <div><dt>Email</dt><dd data-review="email"></dd></div>
                        <div><dt>Phone</dt><dd data-review="phone"></dd></div>
                    </dl>
                </div>

                <div class="reg-review">
                    <div class="reg-review-head">Delivery address <button type="button" data-goto="2">Edit</button></div>
                    <dl><div><dt>Address</dt><dd data-review="address"></dd></div></dl>
                </div>

                <div class="reg-review">
                    <div class="reg-review-head">Verification <button type="button" data-goto="3">Edit</button></div>
                    <dl><div><dt>Valid ID</dt><dd><span class="reg-badge">Uploaded</span><span data-review="id"></span></dd></div></dl>
                </div>

                <div class="field">
                    <label class="terms-check">
                        <input type="checkbox" name="terms" required>
                        <span>
                            I agree to the BoomBuy
                            <a href="#" onclick="openTerms(event)">Terms &amp; Conditions</a>
                            and
                            <a href="#" onclick="openPrivacy(event)">Privacy Policy</a>,
                            and confirm my details are true.
                        </span>
                    </label>
                </div>
            </section>

            <div class="reg-actions">
                <button type="button" class="reg-btn reg-btn-ghost" id="reg-back" hidden>Back</button>
                <button type="button" class="reg-btn reg-btn-primary" id="reg-next">Continue <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
                <button type="submit" class="reg-btn reg-btn-primary submit-btn" id="reg-submit" hidden>Create account</button>
            </div>

            <p class="reg-private"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Your information is kept private and only used for your BoomBuy account.</p>
        </form>

        <p class="reg-signin">Already have an account? <a href="{{ route('login') }}">Log in</a></p>

    </div>
    </main>

</div>

@include('partials.terms-modal')

<script src="{{ asset('js/data/psgc-data.js') }}"></script>
<script src="{{ vasset('js/pages/registration-fields.js') }}"></script>

<script>
    initAddressCascade('province', 'city_municipality', @json(old('city_municipality')));
    initRegionFilter('region', 'province', 'city_municipality', 'barangay', @json(\App\Support\PhLocations::provincesByRegionName()));
    initBarangayAndPostal({ province: 'province', city: 'city_municipality', barangay: 'barangay', barangayText: 'barangay_text', postal: 'postal_code', hint: 'postal_hint', data: @json(asset('js/data')) });
    initAgeCalc('birthdate', 'age');

    (function () {
        var SERVER_ERRORS = @json($errors->toArray());
        var MAX_ID_BYTES = 5 * 1024 * 1024;

        var form = document.getElementById('reg-form');
        var panels = form.querySelectorAll('[data-panel]');
        var stepItems = document.querySelectorAll('#reg-steps li');
        var stepLabel = document.getElementById('reg-step-label');
        var backBtn = document.getElementById('reg-back');
        var nextBtn = document.getElementById('reg-next');
        var submitBtn = document.getElementById('reg-submit');
        var step = {{ (int) $startStep }};
        var reached = step;

        var ERR_ICON = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 7v6M12 17h.01"/></svg>';

        // ---------- Inline errors ----------

        function fieldBox(el) {
            return el.closest('.field');
        }

        function errorTarget(el) {
            if (el.type === 'radio') return el.closest('.reg-pills');
            if (el.type === 'file') return document.getElementById('id-drop');
            return el;
        }

        function setError(el, message) {
            var box = fieldBox(el);
            if (!box) return;
            var msg = box.querySelector('.reg-err');
            var target = errorTarget(el);

            if (message) {
                if (!msg) {
                    msg = document.createElement('p');
                    msg.className = 'reg-err';
                    box.appendChild(msg);
                }
                msg.innerHTML = ERR_ICON;
                msg.appendChild(document.createTextNode(message));
                if (target) target.classList.add('input-error');
            } else {
                if (msg) msg.remove();
                if (target) target.classList.remove('input-error');
            }
        }

        function messageFor(el) {
            var v = el.validity;
            if (v.valid) return '';
            if (el.type === 'radio') return 'Please choose one.';
            if (el.type === 'checkbox') return 'Please agree to the Terms & Conditions and Privacy Policy.';
            if (el.type === 'file') return v.customError ? el.validationMessage : 'Please upload a valid ID.';
            if (v.valueMissing) return 'This field is required.';
            if (v.customError) return el.validationMessage;
            if (el.id === 'birthdate' && v.rangeOverflow) return 'You must be at least 18 years old.';
            if (v.typeMismatch && el.type === 'email') return 'Enter a valid email address.';
            if ((v.patternMismatch || v.tooShort) && el.title) return el.title;
            return el.validationMessage;
        }

        function check(el) {
            var msg = messageFor(el);
            setError(el, msg);
            return !msg;
        }

        // ---------- Custom rules ----------

        var pwd = document.getElementById('password');
        var confirmPwd = document.getElementById('password_confirmation');

        function syncConfirm() {
            var typed = confirmPwd.value !== '';
            var same = typed && confirmPwd.value === pwd.value;
            confirmPwd.setCustomValidity(typed && !same ? 'Passwords do not match.' : '');
            // Red border while the two differ, green once they match.
            confirmPwd.classList.toggle('pw-mismatch', typed && !same);
            confirmPwd.classList.toggle('pw-match', same);
        }

        // ---------- Steps ----------

        function fieldsOf(n) {
            return panels[n - 1].querySelectorAll('input[name]:not([type="hidden"]):not([readonly]), select[name]');
        }

        function validateStep(n) {
            if (n === 3) syncConfirm();
            var first = null;
            var seenRadio = {};
            fieldsOf(n).forEach(function (el) {
                if (el.type === 'radio') {
                    if (seenRadio[el.name]) return;
                    seenRadio[el.name] = true;
                }
                if (!check(el) && !first) first = el;
            });
            if (first) {
                (first.type === 'file' ? document.getElementById('id-drop') : first).scrollIntoView({ block: 'center', behavior: 'smooth' });
                if (first.type !== 'file') first.focus({ preventScroll: true });
            }
            return !first;
        }

        function show(n) {
            step = n;
            reached = Math.max(reached, n);

            panels.forEach(function (p, i) { p.hidden = i + 1 !== n; });
            stepItems.forEach(function (li, i) {
                li.classList.toggle('is-current', i + 1 === n);
                li.classList.toggle('is-done', i + 1 < n || (i + 1 < reached && i + 1 !== n));
            });

            stepLabel.textContent = 'Buyer account · Step ' + n + ' of 4';
            backBtn.hidden = n === 1;
            nextBtn.hidden = n === 4;
            submitBtn.hidden = n !== 4;

            if (n === 2) updateAddress();
            if (n === 4) fillReview();
            if (window.bbRegSide) window.bbRegSide.show(n);
        }

        function goTo(n) {
            // Moving forward only after every step before it checks out.
            for (var s = step; s < n; s++) {
                if (!validateStep(s)) { show(s); return; }
            }
            show(n);
            document.querySelector('.reg-head').scrollIntoView({ behavior: 'smooth' });
        }

        nextBtn.addEventListener('click', function () { goTo(step + 1); });
        backBtn.addEventListener('click', function () { show(step - 1); });

        document.querySelectorAll('[data-goto]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var n = +btn.dataset.goto;
                if (n < step || (n <= reached)) goTo(n);
            });
        });

        // Enter on an early step moves on instead of submitting.
        form.addEventListener('submit', function (e) {
            if (step < 4) {
                e.preventDefault();
                e.stopImmediatePropagation();
                goTo(step + 1);
                return;
            }
            for (var s = 1; s <= 4; s++) {
                if (!validateStep(s)) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    show(s);
                    return;
                }
            }
        }, true);

        // Clear an error as soon as the field is fixed.
        form.querySelectorAll('input[name], select[name]').forEach(function (el) {
            ['input', 'change'].forEach(function (evt) {
                el.addEventListener(evt, function () {
                    if (el === pwd || el === confirmPwd) syncConfirm();
                    var box = fieldBox(el);
                    if (box && box.querySelector('.reg-err')) check(el);
                    if (el === pwd && confirmPwd.value) check(confirmPwd);
                });
            });
        });

        // ---------- Address preview ----------

        // "123 Rizal Street, Camella Homes": what the server stores as the street part.
        function streetLine() {
            var v = function (id) { return document.getElementById(id).value.trim(); };
            var line = [v('street'), v('building')].filter(Boolean).join(', ');
            document.getElementById('street_address').value = line;
            return line;
        }

        function updateAddress() {
            streetLine();
            // The barangay is the dropdown, or the text box for towns without a list.
            var brgy = form.querySelector('[name="barangay"]');
            var parts = [
                document.getElementById('street_address').value.trim(),
                brgy ? brgy.value.trim() : '',
                document.getElementById('city_municipality').value.trim(),
                document.getElementById('province').value.trim(),
            ].filter(Boolean);
            var postal = document.getElementById('postal_code').value.trim();
            var line = parts.join(', ') + (parts.length && postal ? ' ' + postal : '');
            document.getElementById('reg-addr-preview').textContent = line || 'Fill in your address above';
            return line;
        }

        ['street', 'building', 'barangay', 'barangay_text', 'city_municipality', 'province', 'region', 'postal_code'].forEach(function (id) {
            document.getElementById(id).addEventListener('input', updateAddress);
            document.getElementById(id).addEventListener('change', updateAddress);
        });

        // ---------- ID upload ----------

        var idInput = document.getElementById('id_photo');
        var drop = document.getElementById('id-drop');
        var chosen = document.getElementById('id-chosen');

        function onIdChange() {
            var file = idInput.files && idInput.files[0];
            idInput.setCustomValidity('');
            if (file && !/\.(jpe?g|png|pdf)$/i.test(file.name)) {
                idInput.setCustomValidity('Use a JPG, PNG or PDF file.');
            } else if (file && file.size > MAX_ID_BYTES) {
                idInput.setCustomValidity('The file is larger than 5 MB.');
            }
            var ok = file && idInput.validity.valid;
            drop.hidden = !!ok;
            chosen.hidden = !ok;
            document.getElementById('id-name').textContent = ok ? file.name : '';
            if (file) check(idInput);
        }

        idInput.addEventListener('change', onIdChange);

        ['dragenter', 'dragover'].forEach(function (evt) {
            drop.addEventListener(evt, function (e) { e.preventDefault(); drop.classList.add('is-over'); });
        });
        ['dragleave', 'drop'].forEach(function (evt) {
            drop.addEventListener(evt, function () { drop.classList.remove('is-over'); });
        });
        drop.addEventListener('drop', function (e) {
            e.preventDefault();
            if (e.dataTransfer.files.length) {
                idInput.files = e.dataTransfer.files;
                onIdChange();
            }
        });

        // ---------- Password ----------

        var EYE = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        var EYE_OFF = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

        document.querySelectorAll('.password-toggle').forEach(function (btn) {
            btn.innerHTML = EYE_OFF;
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.dataset.target);
                var showPw = input.type === 'password';
                input.type = showPw ? 'text' : 'password';
                btn.innerHTML = showPw ? EYE : EYE_OFF;
                btn.setAttribute('aria-label', showPw ? 'Hide password' : 'Show password');
            });
        });

        var RULES = {
            len: function (p) { return p.length >= 8; },
            upper: function (p) { return /[A-Z]/.test(p); },
            lower: function (p) { return /[a-z]/.test(p); },
            num: function (p) { return /\d/.test(p); },
            sym: function (p) { return /[^A-Za-z0-9]/.test(p); }
        };
        var LEVELS = [
            ['Not set', '#645a80', '#e3e1ea'],
            ['Weak', '#b42318', '#d92d20'],
            ['Fair', '#8a6200', '#f5b70b'],
            ['Good', '#5536b5', '#6d4ad1'],
            ['Strong', '#0a6f66', '#0d9488']
        ];

        function updateStrength() {
            var p = pwd.value;
            var passed = 0;
            document.querySelectorAll('#pw-rules li').forEach(function (li) {
                var ok = RULES[li.dataset.rule](p);
                li.classList.toggle('ok', ok);
                if (ok) passed++;
            });
            // 5 rules onto 4 bars; all 5 needed for "Strong".
            var level = !p ? 0 : passed === 5 ? 4 : passed >= 4 ? 3 : passed >= 3 ? 2 : 1;
            var label = document.getElementById('pw-label');
            label.textContent = LEVELS[level][0];
            label.style.color = LEVELS[level][1];
            document.querySelectorAll('#pw-bars span').forEach(function (bar, i) {
                bar.style.background = i < level ? LEVELS[level][2] : '#e3e1ea';
            });
        }

        pwd.addEventListener('input', updateStrength);

        // ---------- Review ----------

        function fillReview() {
            var v = function (id) { return document.getElementById(id).value.trim(); };
            var mi = v('middle_initial');
            var sex = form.querySelector('input[name="sex"]:checked');
            var put = function (key, text) {
                form.querySelector('[data-review="' + key + '"]').textContent = text || '—';
            };
            put('name', [v('first_name'), mi ? mi.replace(/\.?$/, '.') : '', v('last_name')].filter(Boolean).join(' '));
            put('sexage', (sex ? sex.value : '—') + ' · ' + (v('age') ? v('age') + ' years old' : '—'));
            put('email', v('email'));
            put('phone', v('phone'));
            put('address', updateAddress());
            put('id', idInput.files && idInput.files[0] ? idInput.files[0].name : '');
        }

        // ---------- Start ----------

        Object.keys(SERVER_ERRORS).forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (el) setError(el, SERVER_ERRORS[name][0]);
        });

        updateStrength();
        show(step);
    })();
</script>

@include('partials.pwa-register')

</body>
</html>
