<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BoomBuy — Rider Application</title>

    @include('partials.pwa-head')

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #fffaf8;
            color: #172033;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        .navbar {
            height: 72px;
            padding: 0 7%;
            background: white;
            border-bottom: 1px solid #f9e9e4;

            display: flex;
            align-items: center;
            justify-content: space-between;

            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-family: 'Baloo 2', sans-serif;
            font-size: 25px;
            font-weight: 800;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .back-btn {
            color: #8d6c62;
            background: #fff7f5;
            border: 1px solid #f5ddd6;
            padding: 9px 14px;
            border-radius: 9px;

            font-size: 12px;
            font-weight: 700;

            transition: .2s ease;
        }

        .back-btn:hover {
            color: #e8420f;
            border-color: #efb8a7;
            background: #fff1ed;
        }

        .page {
            width: 100%;
            max-width: 1050px;
            margin: 0 auto;
            padding: 50px 25px 70px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .page-header small {
            display: inline-block;
            color: #e8420f;
            background: #ffefea;

            padding: 7px 13px;
            border-radius: 30px;

            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.3px;
            text-transform: uppercase;

            margin-bottom: 12px;
        }

        .page-header h1 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 38px;
            line-height: 1.1;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #977970;
            font-size: 13px;
            line-height: 1.7;
            max-width: 650px;
            margin: 0 auto;
        }

        .status-box {
            background: white;
            border: 1px solid #f4e2dc;
            border-radius: 14px;
            padding: 15px 18px;
            margin-bottom: 25px;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .status-label {
            color: #8d6c62;
            font-size: 11px;
            font-weight: 700;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 7px 11px;
            border-radius: 20px;

            font-size: 10px;
            font-weight: 800;
        }

        .status-pending {
            background: #fff4d6;
            color: #a16207;
        }

        .status-approved {
            background: #e9f9ef;
            color: #15803d;
        }

        .status-rejected {
            background: #ffe8e8;
            color: #dc2626;
        }

        .application-form {
            background: white;
            border: 1px solid #f4e2dc;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(72, 45, 35, 0.06);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;

            font-family: 'Baloo 2', sans-serif;
            font-size: 21px;

            margin-bottom: 20px;
            padding-bottom: 12px;

            border-bottom: 1px solid #f7e8e3;
        }

        .section-title span {
            width: 34px;
            height: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;
            background: #fff1ed;

            font-size: 17px;
        }

        .form-section {
            margin-bottom: 35px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            color: #4e3831;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 7px;
        }

        .required {
            color: #e8420f;
        }

        input,
        textarea,
        select {
            width: 100%;

            border: 1px solid #ead7d1;
            background: #fffdfc;

            border-radius: 10px;

            padding: 12px 13px;

            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 12px;
            color: #172033;

            outline: none;

            transition: .2s ease;
        }

        textarea {
            min-height: 95px;
            resize: vertical;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: #ef704b;
            background: white;
            box-shadow: 0 0 0 3px rgba(232, 66, 15, 0.07);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 42px;
        }

        .show-password-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);

            border: none;
            background: transparent;

            display: flex;
            align-items: center;
            justify-content: center;
            color: #6a4e46;

            cursor: pointer;
            padding: 5px;

            opacity: 0.7;
            transition: opacity 0.15s ease;
        }

        .show-password-btn:hover {
            opacity: 1;
        }

        input.input-error,
        select.input-error,
        textarea.input-error {
            border-color: #dc2626 !important;
            background: #fef2f2 !important;
        }

        .field-error-msg {
            display: none;

            color: #dc2626;
            font-size: 10px;

            margin-top: 6px;
        }

        .file-box {
            border: 1px dashed #e5c8bf;
            background: #fffaf8;
            border-radius: 11px;
            padding: 13px;
        }

        .file-box input {
            border: none;
            background: transparent;
            padding: 0;
            box-shadow: none;
        }

        .file-help {
            color: #a0847b;
            font-size: 10px;
            line-height: 1.5;
            margin-top: 7px;
        }

        .field-hint {
            display: block;

            color: #a0847b;
            font-size: 10px;
            line-height: 1.5;

            margin-top: 7px;
        }

        .file-name {
            display: block;

            font-size: 10px;
            font-weight: 800;
            color: #15803d;

            margin-top: 6px;
        }

        .terms-check {
            grid-column: 1 / -1;

            display: flex;
            align-items: flex-start;
            gap: 8px;

            font-size: 12px;
            color: #4e3831;
            line-height: 1.5;
        }

        .terms-check input[type="checkbox"] {
            margin-top: 3px;
            width: auto;
            accent-color: #e8420f;
        }

        .terms-check a {
            color: #e8420f;
            font-weight: 700;
        }

        .requirements-note {
            background: #fff7f5;
            border: 1px solid #f5ddd6;
            border-radius: 12px;
            padding: 13px 15px;
            margin-bottom: 20px;

            color: #76564c;
            font-size: 10px;
            line-height: 1.6;
        }

        .requirements-note strong {
            color: #e8420f;
        }

        .submit-area {
            padding-top: 8px;
            border-top: 1px solid #f7e8e3;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .submit-note {
            color: #977970;
            font-size: 10px;
            line-height: 1.5;
            max-width: 550px;
        }

        .submit-btn {
            border: none;
            background: #e8420f;
            color: white;

            padding: 13px 22px;
            border-radius: 10px;

            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 12px;
            font-weight: 800;

            cursor: pointer;

            box-shadow: 0 9px 22px rgba(232, 66, 15, 0.17);

            transition: .2s ease;
        }

        .submit-btn:hover {
            background: #cf380b;
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 11px;
            padding: 12px 14px;
            margin-bottom: 20px;

            font-size: 11px;
            line-height: 1.5;
        }

        .alert-success {
            background: #ecfdf3;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .alert-error {
            background: #fff0f0;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .error-list {
            margin-top: 5px;
            padding-left: 18px;
        }

        footer {
            text-align: center;
            color: #a0847b;
            font-size: 10px;
            padding: 0 20px 30px;
        }

        @media (max-width: 700px) {

            .navbar {
                padding: 0 20px;
            }

            .page {
                padding: 35px 15px 50px;
            }

            .page-header h1 {
                font-size: 32px;
            }

            .application-form {
                padding: 21px 17px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .status-box {
                align-items: flex-start;
                flex-direction: column;
            }

            .submit-area {
                flex-direction: column;
                align-items: stretch;
            }

            .submit-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">

        <a href="{{ route('home') }}" class="logo">
            Boom<span>Buy</span>
        </a>

        <a href="{{ route('register') }}" class="back-btn">
            ← Back
        </a>

    </nav>


    <main class="page">

        <div class="page-header">

            <small>Rider Verification</small>

            <h1>Become a BoomBuy Rider</h1>

            <p>
                Submit your personal information, vehicle details,
                and required documents for verification.
            </p>

        </div>


        {{-- SUCCESS MESSAGE --}}
        @if(session('success'))
            <div class="alert alert-success">
                ✓ {{ session('success') }}
            </div>
        @endif


        {{-- ERROR MESSAGE --}}
        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif


        {{-- VALIDATION ERRORS --}}
        @if($errors->any())
            <div class="alert alert-error">

                <strong>Please check the following:</strong>

                <ul class="error-list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>
        @endif


        {{-- CURRENT APPLICATION STATUS --}}
        @if($application)

            <div class="status-box">

                <div>
                    <div class="status-label">
                        Current Application Status
                    </div>
                </div>

                @php
                    $statusClass = match($application->status) {
                        'Approved' => 'status-approved',
                        'Rejected' => 'status-rejected',
                        default => 'status-pending',
                    };
                @endphp

                <span class="status-badge {{ $statusClass }}">
                    {{ $application->status }}
                </span>

            </div>

            @if($application->status === 'Pending Verification')

                <div class="requirements-note">
                    <strong>Application under review.</strong>
                    Your rider application has already been submitted.
                    Please wait for the Admin to review your requirements.
                </div>

            @elseif($application->status === 'Approved')

                <div class="requirements-note">
                    <strong>Congratulations!</strong>
                    Your rider application has been approved.
                    You may now use your BoomBuy Rider Dashboard.
                </div>

            @elseif($application->status === 'Rejected')

                <div class="requirements-note">
                    <strong>Application rejected.</strong>
                    You may submit a new application after reviewing
                    the requirements below.
                </div>

            @endif

        @endif


        {{-- APPLICATION FORM --}}

        <form
            action="{{ route('rider.apply.submit') }}"
            method="POST"
            enctype="multipart/form-data"
            class="application-form"
        >

            @csrf


            <!-- =========================
                 PERSONAL INFORMATION
            ========================== -->

            <section class="form-section">

                <h2 class="section-title">
                    <span>👤</span>
                    Personal Information
                </h2>

                <div class="form-grid">

                    <div class="form-group">

                        <label for="full_name">
                            Full Name <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            value="{{ old('full_name', $user['name'] ?? '') }}"
                            placeholder="Enter your complete name"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="phone">
                            Phone Number <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="09XXXXXXXXX"
                            required
                        >

                    </div>


                    <div class="form-group full">

                        <label for="address">
                            Complete Address <span class="required">*</span>
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            placeholder="House number, street, barangay, city/municipality, province"
                            required
                        >{{ old('address') }}</textarea>

                    </div>

                </div>

            </section>


            <!-- =========================
                 VEHICLE INFORMATION
            ========================== -->

            <section class="form-section">

                <h2 class="section-title">
                    <span>🛵</span>
                    Vehicle Information
                </h2>

                <div class="form-grid">

                    <div class="form-group">

                        <label for="vehicle_type">
                            Vehicle Type <span class="required">*</span>
                        </label>

                        <select
                            id="vehicle_type"
                            name="vehicle_type"
                            required
                        >

                            <option value="">
                                Select vehicle type
                            </option>

                            <option
                                value="Motorcycle"
                                {{ old('vehicle_type') === 'Motorcycle' ? 'selected' : '' }}
                            >
                                Motorcycle
                            </option>

                            <option
                                value="Car"
                                {{ old('vehicle_type') === 'Car' ? 'selected' : '' }}
                            >
                                Car
                            </option>

                            <option
                                value="Van"
                                {{ old('vehicle_type') === 'Van' ? 'selected' : '' }}
                            >
                                Van
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="vehicle_model">
                            Vehicle Model <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="vehicle_model"
                            name="vehicle_model"
                            value="{{ old('vehicle_model') }}"
                            placeholder="Example: Honda Click 125"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="plate_number">
                            Plate Number <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="plate_number"
                            name="plate_number"
                            value="{{ old('plate_number') }}"
                            placeholder="Example: ABC 1234"
                            required
                        >

                    </div>

                </div>

            </section>


            <!-- =========================
                 DOCUMENT REQUIREMENTS
            ========================== -->

            <section class="form-section">

                <h2 class="section-title">
                    <span>📄</span>
                    Required Documents
                </h2>

                <div class="requirements-note">
                    <strong>Important:</strong>
                    Upload clear and readable copies of your documents.
                    These files will be used only for rider verification
                    and should not be publicly accessible.
                </div>


                <div class="form-grid">


                    <!-- NATIONAL ID -->

                    <div class="form-group">

                        <label for="national_id">
                            National ID <span class="required">*</span>
                        </label>

                        <div class="file-box">

                            <input
                                type="file"
                                id="national_id"
                                name="national_id"
                                accept=".jpg,.jpeg,.png,.webp,.pdf"
                                onchange="showFileName(this, 'national_id-name')"
                                required
                            >

                            <div class="file-help">
                                Upload a clear photo or PDF of your National ID.
                            </div>

                            <span class="file-name" id="national_id-name"></span>

                        </div>

                    </div>


                    <!-- DRIVER'S LICENSE -->

                    <div class="form-group">

                        <label for="drivers_license">
                            Driver's License <span class="required">*</span>
                        </label>

                        <div class="file-box">

                            <input
                                type="file"
                                id="drivers_license"
                                name="drivers_license"
                                accept=".jpg,.jpeg,.png,.webp,.pdf"
                                onchange="showFileName(this, 'drivers_license-name')"
                                required
                            >

                            <div class="file-help">
                                Upload a clear copy of your valid Driver's License.
                            </div>

                            <span class="file-name" id="drivers_license-name"></span>

                        </div>

                    </div>


                    <!-- SELFIE -->

                    <div class="form-group">

                        <label for="profile_selfie">
                            Profile / Selfie <span class="required">*</span>
                        </label>

                        <div class="file-box">

                            <input
                                type="file"
                                id="profile_selfie"
                                name="profile_selfie"
                                accept=".jpg,.jpeg,.png,.webp"
                                onchange="showFileName(this, 'profile_selfie-name')"
                                required
                            >

                            <div class="file-help">
                                Upload a clear recent selfie for verification.
                            </div>

                            <span class="file-name" id="profile_selfie-name"></span>

                        </div>

                    </div>


                    <!-- PROOF OF ADDRESS -->

                    <div class="form-group">

                        <label for="proof_of_address">
                            Proof of Address <span class="required">*</span>
                        </label>

                        <div class="file-box">

                            <input
                                type="file"
                                id="proof_of_address"
                                name="proof_of_address"
                                accept=".jpg,.jpeg,.png,.webp,.pdf"
                                onchange="showFileName(this, 'proof_of_address-name')"
                                required
                            >

                            <div class="file-help">
                                Example: utility bill or other valid proof of address.
                            </div>

                            <span class="file-name" id="proof_of_address-name"></span>

                        </div>

                    </div>


                    <!-- OR/CR -->

                    <div class="form-group full">

                        <label for="or_cr">
                            Vehicle OR/CR <span class="required">*</span>
                        </label>

                        <div class="file-box">

                            <input
                                type="file"
                                id="or_cr"
                                name="or_cr"
                                accept=".jpg,.jpeg,.png,.webp,.pdf"
                                onchange="showFileName(this, 'or_cr-name')"
                                required
                            >

                            <div class="file-help">
                                Upload a clear copy of the vehicle's Official Receipt
                                and Certificate of Registration.
                            </div>

                            <span class="file-name" id="or_cr-name"></span>

                        </div>

                    </div>

                </div>

            </section>

            {{-- EMAIL --}}
<div class="form-group">
    <label for="email">
        Email Address
    </label>

    <input
        type="email"
        id="email"
        name="email"
        value="{{ old('email') }}"
        placeholder="Enter your email address"
        autocomplete="email"
        required
    >
</div>

{{-- PASSWORD --}}
<div class="form-group">
    <label for="password">
        Password
    </label>

    <div class="password-wrapper">
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Create a password"
            autocomplete="new-password"
            minlength="8"
            required
        >

        <button
            type="button"
            class="show-password-btn"
            onclick="togglePassword('password', this)"
            aria-label="Show password"
        ><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
    </div>
    <span class="field-hint">At least 8 characters.</span>
</div>

{{-- CONFIRM PASSWORD --}}
<div class="form-group">
    <label for="password_confirmation">
        Confirm Password
    </label>

    <div class="password-wrapper">
        <input
            type="password"
            id="password_confirmation"
            name="password_confirmation"
            placeholder="Confirm your password"
            autocomplete="new-password"
            required
        >

        <button
            type="button"
            class="show-password-btn"
            onclick="togglePassword('password_confirmation', this)"
            aria-label="Show password"
        ><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
    </div>

    <span class="field-error-msg" id="password-match-msg">
        Passwords do not match.
    </span>
</div>


            <!-- =========================
                 SUBMIT
            ========================== -->

            <div class="submit-area">

                <div class="submit-note">
                    By submitting this application, you confirm that the
                    information and documents provided are accurate and
                    belong to you. Your application will be reviewed by
                    BoomBuy Admin.
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

                <button
                    type="submit"
                    class="submit-btn"
                >
                    Submit Application →
                </button>

            </div>

        </form>

    </main>


    <footer>
        © 2026 BoomBuy · Rider Verification
    </footer>

    @include('partials.terms-modal')

    <script>
        function showFileName(input, targetId) {
            var target = document.getElementById(targetId);
            if (!target) return;

            target.textContent = input.files && input.files[0]
                ? '✓ ' + input.files[0].name
                : '';
        }

        var EYE_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        var EYE_OFF_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

        function togglePassword(inputId, button) {
            var input = document.getElementById(inputId);
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                button.innerHTML = EYE_ICON;
                button.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                button.innerHTML = EYE_OFF_ICON;
                button.setAttribute('aria-label', 'Show password');
            }
        }

        (function () {
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