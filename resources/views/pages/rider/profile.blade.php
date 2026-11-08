<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'My Profile — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/partials/rider-a.css') }}">
</head>

<body>

    <x-layout.rider-sidebar active="profile" :user="$user" />

    @php
        $rider = $riderRecord ?? null;
        $riderStatus = $rider
            ? (($dbUser->status ?? '') === 'Active' && $rider->status === 'Approved' ? 'Active' : ($dbUser->status ?? $rider->status))
            : ($dbUser->status ?? 'Active');
        $initials = collect(preg_split('/\s+/', trim($user['name'] ?? 'Rider')))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

        // What the rider still has to give (R1).
        $missing = [];
        if ($rider) {
            if (!$rider->drivers_license) $missing[] = "driver's license photo";
            if (!$rider->license_expiry) $missing[] = 'license expiry date';
            if (!$rider->or_cr) $missing[] = 'OR/CR';
        }
        $docs = ['drivers_license' => "Driver's license", 'or_cr' => 'OR/CR'];
    @endphp

    <main class="main-content">

        <div class="ra">

            <div class="ra-head">
                <span class="ra-kicker">RIDER CENTER</span>
                <h1>My profile</h1>
            </div>

            @if(session('success'))
                <div class="ra-note is-success" role="status"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
            @endif
            @if(session('error'))
                <div class="ra-note is-error" role="alert"><i class="bi bi-x-circle-fill"></i><span>{{ session('error') }}</span></div>
            @endif

            {{-- WHO: photo (tap the camera to change it), name, company, status --}}
            <div class="ra-me">
                <form class="ra-avatar" action="{{ route('rider.profile.photo') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @if(!empty($user['profile_photo']))
                        <img src="{{ asset('storage/profile-photos/' . $user['profile_photo']) }}" alt="Profile photo">
                    @else
                        <span aria-hidden="true">{{ $initials ?: 'R' }}</span>
                    @endif
                    <label for="profile_photo" aria-label="Change profile photo"><i class="bi bi-camera-fill"></i></label>
                    <input type="file" name="profile_photo" id="profile_photo" accept="image/png,image/jpeg,image/webp" required onchange="this.form.submit()">
                </form>
                <div>
                    <strong data-rider-name>{{ $rider->full_name ?? ($user['name'] ?? 'Rider') }}</strong>
                    <small>Rider · <span data-company>{{ $companyName ?? 'BoomBuy' }}</span></small>
                </div>
                <span @class(['ra-badge', 'is-wait' => $riderStatus !== 'Active']) data-rider-status>{{ $riderStatus }}{{ $rider && $rider->needs_reverification ? ' (re-verification pending)' : '' }}</span>
            </div>

            @if($missing)
                <div class="ra-note" role="status"><i class="bi bi-exclamation-circle"></i><span><b>Still needed:</b> {{ implode(', ', $missing) }}. Add {{ count($missing) === 1 ? 'it' : 'them' }} below.</span></div>
            @endif

            {{-- LICENSE, VEHICLE AND DOCUMENTS (R1) --}}
            @if($rider)
                <form method="POST" action="{{ route('rider.vehicle.update') }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:14px">
                    @csrf

                    <section class="ra-card rider-vehicle">
                        <h2><i class="bi bi-bicycle"></i> Vehicle</h2>
                        <div class="ra-line"><span>Type &amp; model</span><span data-vehicle>{{ $rider->vehicle_type }}{{ $rider->vehicle_model ? ' · ' . $rider->vehicle_model : '' }}</span></div>
                        <div class="ra-line"><span>Plate on file</span><span data-plate>{{ $rider->plate_number }}</span></div>
                        <label class="ra-field" for="plate_number">Plate number<input type="text" id="plate_number" name="plate_number" value="{{ $rider->plate_number }}" maxlength="10"></label>
                    </section>

                    <section class="ra-card">
                        <h2><i class="bi bi-person-vcard"></i> License &amp; documents</h2>
                        <div class="ra-line"><span>Driver's license no.</span><span data-license>{{ $rider->drivers_license_no ?? '—' }}</span></div>
                        <div class="ra-line"><span>License expiry</span><span data-license-expiry>{{ $rider->license_expiry ? \Illuminate\Support\Carbon::parse($rider->license_expiry)->format('m/d/Y') : 'Not set' }}</span></div>
                        <label class="ra-field" for="license_expiry">New expiry date<input type="date" id="license_expiry" name="license_expiry" min="{{ today()->toDateString() }}" value="{{ $rider->license_expiry }}"></label>

                        <div class="ra-ups">
                            @foreach($docs as $docField => $docLabel)
                                <label class="ra-up">
                                    <input type="file" name="{{ $docField }}" accept=".jpg,.jpeg,.png,.pdf" data-file-name>
                                    <i class="bi bi-cloud-arrow-up"></i>
                                    <strong>{{ $docLabel }}</strong>
                                    <small @class(['is-ok' => $rider->{$docField}]) data-file-status>{{ $rider->{$docField} ? 'On file · tap to replace' : 'Not uploaded' }}</small>
                                </label>
                            @endforeach
                        </div>
                        @if($rider->drivers_license || $rider->or_cr)
                            <div style="display:flex;gap:14px;flex-wrap:wrap">
                                @foreach($docs as $docField => $docLabel)
                                    @if($rider->{$docField})
                                        <a href="{{ route('rider.document', $docField) }}" class="ra-up-view" data-viewer="my-docs" data-viewer-badge="My documents" data-viewer-title="{{ $docLabel }}"><i class="bi bi-eye"></i> View {{ $docLabel }}</a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        <small style="font-size:11.5px;color:var(--theme-muted)">JPG, PNG or PDF, up to 5 MB each. Your logistics company checks changes again.</small>
                    </section>

                    <button type="submit" class="ra-btn">Save vehicle &amp; documents</button>
                </form>
            @endif

            {{-- ACCOUNT AND DELIVERY COUNTS (from RiderController::profile()) --}}
            <section class="ra-card">
                <h2><i class="bi bi-person"></i> Account</h2>
                <div class="ra-line"><span>Email</span><span style="overflow-wrap:anywhere">{{ $user['email'] ?? 'No email' }}</span></div>
                <div class="ra-line"><span>Rider ID</span><span>{{ $user['id'] ?? 'N/A' }}</span></div>
                <div class="ra-tiles is-3">
                    <div class="ra-tile"><span>Total</span><strong>{{ $totalDeliveries }}</strong></div>
                    <div class="ra-tile"><span>Active</span><strong>{{ $activeCount }}</strong></div>
                    <div class="ra-tile"><span>Delivered</span><strong>{{ $deliveredCount }}</strong></div>
                </div>
            </section>

            {{-- PHONE AND ADDRESS --}}
            <section class="ra-card" id="details">
                <h2><i class="bi bi-geo-alt"></i> Phone &amp; Address</h2>
                <form method="POST" action="{{ route('rider.profile.update') }}" style="display:flex;flex-direction:column;gap:12px">
                    @csrf
                    <label class="ra-field" for="phone">Mobile number<input type="text" id="phone" name="phone" maxlength="20" required value="{{ old('phone', $dbUser->phone ?? '') }}" placeholder="09XXXXXXXXX"></label>
                    <div class="ra-field">
                        Home address
                        @include('partials.address-fields', ['id' => 'rider', 'value' => old('address', $dbUser->address ?? '')])
                        <small>Your town decides which Sorting Center you work with.</small>
                    </div>
                    <button type="submit" class="ra-btn">Save details</button>
                </form>
            </section>

            {{-- CHANGE PASSWORD --}}
            <section class="ra-card">
                <h2><i class="bi bi-shield-lock"></i> Change Password</h2>
                <form method="POST" action="{{ route('rider.profile.password') }}" style="display:flex;flex-direction:column;gap:12px">
                    @csrf
                    <label class="ra-field" for="current_password">Current password<input type="password" id="current_password" name="current_password" autocomplete="current-password" required></label>
                    <div class="ra-pair">
                        <label class="ra-field" for="new_password">New password<input type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password" required><small>At least 8 characters.</small></label>
                        <label class="ra-field" for="new_password_confirmation">Confirm new password<input type="password" id="new_password_confirmation" name="new_password_confirmation" minlength="8" autocomplete="new-password" required></label>
                    </div>
                    <button type="submit" class="ra-btn is-ghost">Update password</button>
                </form>
            </section>

        </div>

    </main>

    @include('partials.viewer-modal')
    @include('partials.pwa-register')

    <script>
        // An upload tile shows the file the rider picked.
        document.querySelectorAll('[data-file-name]').forEach(function (input) {
            input.addEventListener('change', function () {
                var status = input.parentElement.querySelector('[data-file-status]');
                if (!status || !input.files[0]) return;
                status.textContent = input.files[0].name;
                status.classList.add('is-ok');
            });
        });
    </script>

</body>
</html>
