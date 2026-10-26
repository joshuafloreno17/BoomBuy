<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Add Rider — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/logistics-riders.css') }}">
    <style>
        .add-rider { background: #fff; border: 1px solid var(--theme-border); border-radius: 16px; padding: 24px; }
        .add-rider h2 { font-family: 'Bricolage Grotesque', sans-serif; font-size: 17px; margin: 22px 0 12px; }
        .add-rider h2:first-of-type { margin-top: 0; }
        .add-rider .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 16px; }
        .add-rider .field { display: flex; flex-direction: column; gap: 6px; }
        .add-rider label { color: #463c5e; font-size: 12px; font-weight: 800; }
        .add-rider label .opt { color: #857ca0; font-weight: 500; }
        .add-rider input, .add-rider select { width: 100%; box-sizing: border-box; padding: 11px 12px; border: 1px solid var(--theme-border); border-radius: 10px; font: inherit; font-size: 13px; background: #fff; }
        .add-rider input[type=file] { border-style: dashed; background: var(--theme-bg); }
        .add-rider .hint { color: #857ca0; font-size: 11px; }
        .add-rider .actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 22px; flex-wrap: wrap; }
        .add-rider .cancel { padding: 11px 18px; border-radius: 10px; border: 1px solid var(--theme-border); color: #463c5e; text-decoration: none; font-weight: 700; font-size: 13px; }
        .add-rider .error-list { background: #fff1f2; border: 1px solid #fecdd3; color: #be123c; border-radius: 10px; padding: 12px 14px; font-size: 13px; margin-bottom: 16px; }
        @media (max-width: 640px) { .add-rider .grid { grid-template-columns: minmax(0, 1fr); } .add-rider { padding: 18px; } }
    </style>
</head>

<body>

    <x-layout.logistics-sidebar active="riders" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header">
                <h1>Add Rider</h1>
                <p>The rider gets an email to set their own password. Until then the account is Pending Activation.</p>
            </div>

            @if (session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('logistics.riders.store') }}" enctype="multipart/form-data" class="add-rider">
                @csrf

                @if ($errors->any())
                    <div class="error-list">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <h2>Personal Information</h2>
                <div class="grid">
                    <div class="field">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" autocapitalize="words" value="{{ old('first_name') }}" minlength="2" maxlength="50" required>
                    </div>
                    <div class="field">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" autocapitalize="words" value="{{ old('last_name') }}" minlength="2" maxlength="50" required>
                    </div>
                    <div class="field">
                        <label for="middle_initial">Middle Initial <span class="opt">(optional)</span></label>
                        <input type="text" id="middle_initial" name="middle_initial" autocapitalize="words" value="{{ old('middle_initial') }}" maxlength="5">
                    </div>
                    <div class="field">
                        <label for="sex">Sex</label>
                        <select id="sex" name="sex" required>
                            <option value="">Select Sex</option>
                            @foreach (['Male', 'Female'] as $sex)
                                <option value="{{ $sex }}" {{ old('sex') === $sex ? 'selected' : '' }}>{{ $sex }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="birthdate">Date of Birth</label>
                        <input type="date" id="birthdate" name="birthdate" value="{{ old('birthdate') }}" max="{{ now()->subYears(18)->toDateString() }}" required>
                        <span class="hint">Riders must be at least 18 years old.</span>
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                        <span class="hint">The activation link is sent here.</span>
                    </div>
                    <div class="field">
                        <label for="phone">Mobile Number</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}" inputmode="tel" maxlength="16" pattern="\s*(09|\+639)(\s*\d){9}\s*" title="Enter all 11 digits of your mobile number." placeholder="09XXXXXXXXX" required>
                    </div>
                </div>

                <h2>Home Address</h2>
                <div class="grid">
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
                </div>

                <h2>License &amp; Vehicle</h2>
                <div class="grid">
                    <div class="field">
                        <label for="drivers_license_no">Driver's License No.</label>
                        <input type="text" id="drivers_license_no" name="drivers_license_no" value="{{ old('drivers_license_no') }}" maxlength="13" pattern="[A-Za-z]\d{2}-?\d{2}-?\d{6}" title="1 letter and 11 digits: A00-00-000000" placeholder="A00-00-000000" required>
                    </div>
                    <div class="field">
                        <label for="license_expiry">License Expiry Date</label>
                        <input type="date" id="license_expiry" name="license_expiry" value="{{ old('license_expiry') }}" min="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="field">
                        <label for="vehicle_type">Vehicle Type</label>
                        <select id="vehicle_type" name="vehicle_type" required>
                            <option value="">Select Vehicle</option>
                            @foreach (['Motorcycle', 'Car', 'Van'] as $vehicle)
                                <option value="{{ $vehicle }}" {{ old('vehicle_type') === $vehicle ? 'selected' : '' }}>{{ $vehicle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="vehicle_model">Vehicle Model <span class="opt">(optional)</span></label>
                        <input type="text" id="vehicle_model" name="vehicle_model" value="{{ old('vehicle_model') }}" placeholder="e.g. Honda Click 125">
                    </div>
                    <div class="field">
                        <label for="plate_number">Plate Number</label>
                        <input type="text" id="plate_number" name="plate_number" value="{{ old('plate_number') }}" maxlength="50" placeholder="e.g. 123ABC" required>
                    </div>
                </div>

                <h2>Documents</h2>
                <div class="grid">
                    <div class="field">
                        <label for="drivers_license">Driver's License</label>
                        <input type="file" id="drivers_license" name="drivers_license" accept=".jpg,.jpeg,.png,.pdf" required>
                        <span class="hint">JPG, PNG or PDF, up to 5 MB.</span>
                    </div>
                    <div class="field">
                        <label for="or_cr">Vehicle OR/CR</label>
                        <input type="file" id="or_cr" name="or_cr" accept=".jpg,.jpeg,.png,.pdf" required>
                        <span class="hint">JPG, PNG or PDF, up to 5 MB.</span>
                    </div>
                </div>

                <div class="actions">
                    <a href="{{ route('logistics.riders') }}" class="cancel">Cancel</a>
                    <button type="submit" class="approve-btn submit-btn"><i class="bi bi-person-plus-fill"></i> Save</button>
                </div>
            </form>

        </div>

    </main>

    <script src="{{ asset('js/data/psgc-data.js') }}"></script>
    <script src="{{ asset('js/pages/registration-fields.js') }}"></script>
    <script>
        initAddressCascade('province', 'city_municipality', @json(old('city_municipality')));
    </script>

</body>
</html>
