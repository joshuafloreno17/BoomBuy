<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Rider Management — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/logistics-riders.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="riders" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header" style="display:flex; gap:12px; align-items:flex-end; justify-content:space-between; flex-wrap:wrap;">
                <div>
                    <h1>Rider Management</h1>
                    <p>Review rider applications, add your own riders and manage active rider accounts.</p>
                </div>
                <a href="{{ route('logistics.riders.create') }}" class="approve-btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                    <i class="bi bi-person-plus-fill"></i> Add Rider
                </a>
            </div>

            @if (session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            {{-- STATUS + SEARCH --}}
            <div class="rider-filters">
                <div class="status-chips" id="liveChips">
                    @foreach ($statuses as $key => $definition)
                        @php
                            $chipCount = $definition['status'] === null
                                ? $statusCounts->sum()
                                : ($statusCounts[$definition['status']] ?? 0);
                        @endphp
                        <a
                            href="{{ route('logistics.riders', array_filter(['status' => $key, 'q' => $search])) }}"
                            class="status-chip {{ $statusKey === $key ? 'active' : '' }} {{ $key === 'pending' && $chipCount > 0 ? 'has-work' : '' }}"
                        >
                            {{ $definition['label'] }} ({{ $chipCount }})
                        </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('logistics.riders') }}" class="rider-search" data-live-search data-live-target="#liveChips, #liveClear, #liveResults">
                    <input type="hidden" name="status" value="{{ $statusKey }}">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search name, email, phone or plate" aria-label="Search riders">
                    <button type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
                    <span id="liveClear" style="display:contents;">
                    @if ($search !== '')
                        <a href="{{ route('logistics.riders', ['status' => $statusKey]) }}" title="Clear search"><i class="bi bi-x-lg"></i></a>
                    @endif
                    </span>
                </form>
            </div>

            <div id="liveResults">

            @forelse ($riderApplications as $app)

                @php
                    $statusClass = 'status-' . strtolower(str_replace(' ', '-', $app->status));
                    $accountStatusClass = ($app->account_status ?? 'Active') === 'Pending Activation'
                        ? 'status-pending-verification'
                        : 'status-' . strtolower($app->account_status ?? 'active');
                @endphp

                <div class="app-card">

                    <div class="app-card-top">
                        <div>
                            <div class="app-name">{{ $app->full_name }}</div>
                            <div class="app-email">{{ $app->user_email }}</div>
                        </div>
                        <div class="badge-group">
                            <span class="status-badge {{ $statusClass }}">{{ $app->status }}</span>
                            @if($app->status === 'Approved')
                                <span class="status-badge {{ $accountStatusClass }}">{{ $app->account_status ?? 'Active' }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="app-details">
                        <div>
                            <strong>Phone</strong>
                            {{ $app->phone }}
                        </div>
                        <div>
                            <strong>Address</strong>
                            {{ $app->address }}
                        </div>
                        <div>
                            <strong>Vehicle</strong>
                            {{ $app->vehicle_type }} — {{ $app->vehicle_model }}
                        </div>
                        <div>
                            <strong>Plate Number</strong>
                            {{ $app->plate_number }}
                        </div>
                        @if ($app->drivers_license_no)
                            <div>
                                <strong>Driver's License</strong>
                                {{ $app->drivers_license_no }}
                                @if ($app->license_expiry)
                                    <span style="color:#857ca0;">(expires {{ \Illuminate\Support\Carbon::parse($app->license_expiry)->format('M d, Y') }})</span>
                                @endif
                            </div>
                        @endif
                        @if ($app->created_by)
                            <div>
                                <strong>Added by</strong>
                                Your company
                            </div>
                        @endif
                        <div>
                            <strong>Applied</strong>
                            {{ \Illuminate\Support\Carbon::parse($app->created_at)->format('M d, Y') }}
                        </div>
                        @if ($app->status === 'Approved')
                            <div>
                                <strong>Current Load</strong>
                                {{ $deliveryLoad[$app->user_id] ?? 0 }} delivery(ies)
                            </div>
                            @php $stats = $riderStats[$app->user_id] ?? null; @endphp
                            @if ($stats)
                                <div data-rider-stats="{{ $app->user_id }}">
                                    <strong>Shift</strong>
                                    {{ $stats['online'] ? 'Online' : 'Offline' }} · {{ $stats['active_jobs'] }}/{{ \App\Support\RiderJobs::ACTIVE_LIMIT }} active jobs · {{ $stats['delivered_today'] }} delivered today
                                </div>
                            @endif
                        @endif
                        @if (!empty($app->needs_reverification))
                            <div><strong>Re-verify</strong> New vehicle details or documents uploaded</div>
                        @endif
                        @if (!empty($app->suspension_reason))
                            <div><strong>Suspended</strong> {{ $app->suspension_reason }}</div>
                        @endif
                    </div>

                    <div class="documents">
                        @if ($app->national_id)
                            <a class="doc-link" target="_blank" href="{{ route('logistics.riders.document', ['id' => $app->id, 'field' => 'national_id']) }}">National ID</a>
                        @endif
                        @if ($app->drivers_license)
                            <a class="doc-link" target="_blank" href="{{ route('logistics.riders.document', ['id' => $app->id, 'field' => 'drivers_license']) }}">Driver's License</a>
                        @endif
                        @if ($app->profile_selfie)
                            <a class="doc-link" target="_blank" href="{{ route('logistics.riders.document', ['id' => $app->id, 'field' => 'profile_selfie']) }}">Selfie</a>
                        @endif
                        @if ($app->proof_of_address)
                            <a class="doc-link" target="_blank" href="{{ route('logistics.riders.document', ['id' => $app->id, 'field' => 'proof_of_address']) }}">Proof of Address</a>
                        @endif
                        @if ($app->or_cr)
                            <a class="doc-link" target="_blank" href="{{ route('logistics.riders.document', ['id' => $app->id, 'field' => 'or_cr']) }}">OR/CR</a>
                        @endif
                    </div>

                    @if ($app->status === 'Rejected' && $app->admin_remarks)
                        <div class="remarks-note">
                            Rejection reason: {{ $app->admin_remarks }}
                        </div>
                    @endif

                    @if ($app->status === 'Pending Verification')

                        <div class="app-actions">

                            <form class="approve-form" method="POST" action="{{ route('logistics.riders.approve', $app->id) }}">
                                @csrf
                                <button type="submit" class="approve-btn"><i class="bi bi-check-circle-fill"></i> Approve</button>
                            </form>

                            <form class="reject-form" method="POST" action="{{ route('logistics.riders.reject', $app->id) }}" data-confirm="Reject this rider application? The applicant will be notified by email." data-confirm-ok="Reject" data-confirm-danger>
                                @csrf
                                <input type="text" name="admin_remarks" placeholder="Reason (shown to the applicant)">
                                <button type="submit" class="reject-btn"><i class="bi bi-x-circle-fill"></i> Reject</button>
                            </form>

                        </div>

                    @elseif ($app->status === 'Approved')

                        <div class="app-actions">

                            @if (($app->account_status ?? 'Active') === 'Pending Activation')

                                <form class="status-form" method="POST" action="{{ route('logistics.riders.resend-activation', $app->user_id) }}">
                                    @csrf
                                    <button type="submit" class="activate-btn"><i class="bi bi-envelope-fill"></i> Resend Activation</button>
                                </form>

                            @elseif (($app->account_status ?? 'Active') === 'Active')

                                <form class="status-form" method="POST" action="{{ route('logistics.riders.suspend', $app->user_id) }}">
                                    @csrf
                                    <input type="text" name="reason" minlength="10" maxlength="300" placeholder="Reason for suspending (10–300 characters)" required>
                                    <button type="submit" class="deactivate-btn">Suspend</button>
                                </form>

                                <form class="status-form" method="POST" action="{{ route('logistics.riders.toggle-status', $app->user_id) }}" data-confirm="Deactivate {{ $app->full_name }}? They will be logged out and can no longer receive parcels." data-confirm-ok="Deactivate" data-confirm-danger>
                                    @csrf
                                    <input type="hidden" name="status" value="Deactivated">
                                    <button type="submit" class="deactivate-btn">Deactivate Account</button>
                                </form>

                            @elseif (($app->account_status ?? '') === 'Suspended')

                                <form class="status-form" method="POST" action="{{ route('logistics.riders.reactivate', $app->user_id) }}">
                                    @csrf
                                    <button type="submit" class="activate-btn">Reactivate</button>
                                </form>

                            @else

                                <form class="status-form" method="POST" action="{{ route('logistics.riders.toggle-status', $app->user_id) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="Active">
                                    <button type="submit" class="activate-btn">Activate Account</button>
                                </form>

                            @endif

                        </div>

                        <!-- DELIVERY AREAS -->
                        <div class="areas-section">

                            @php
                                $areas = collect($riderAreas[$app->user_id] ?? []);
                                $outside = $app->home_province ? $areas->where('province', '!=', $app->home_province) : collect();
                            @endphp

                            <div class="areas-label">
                                Delivery Areas
                                @if ($app->home_province)
                                    <span class="area-home"><i class="bi bi-house-door"></i> Lives in {{ trim(($app->home_city ? $app->home_city . ', ' : '') . $app->home_province, ', ') }}</span>
                                @endif
                            </div>

                            {{-- ERP Flow: parcels go to "the rider assigned to that area". --}}
                            @if (!$app->home_province)
                                <div class="area-warn"><i class="bi bi-exclamation-triangle-fill"></i> No home address yet — ask the rider to set it on their profile before giving them areas.</div>
                            @elseif ($outside->isNotEmpty())
                                <div class="area-warn"><i class="bi bi-exclamation-triangle-fill"></i> {{ $outside->count() }} {{ \Illuminate\Support\Str::plural('area', $outside->count()) }} outside {{ $app->home_province }}, where this rider lives. Remove {{ $outside->count() === 1 ? 'it' : 'them' }} and add towns in {{ $app->home_province }}.</div>
                            @elseif ($areas->isEmpty() && $app->status === 'Approved')
                                <div class="area-warn"><i class="bi bi-exclamation-triangle-fill"></i> No areas yet — this rider gets no pickups or deliveries until you add the towns they cover.</div>
                            @endif

                            <div class="area-chips">
                                @forelse (($riderAreas[$app->user_id] ?? []) as $area)
                                    <span class="area-chip {{ $app->home_province && $area->province !== $app->home_province ? 'is-outside' : '' }}">
                                        {{ $area->city_municipality }}, {{ $area->province }}
                                        <form method="POST" action="{{ route('logistics.riders.areas.delete', $area->id) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="area-chip-remove" title="Remove"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    </span>
                                @empty
                                    <span class="no-areas">No areas assigned yet.</span>
                                @endforelse
                            </div>

                            <form method="POST" action="{{ route('logistics.riders.areas.store', $app->user_id) }}" class="area-add-form">
                                @csrf
                                <select id="province-{{ $app->user_id }}" name="province" required data-only="{{ $app->home_province }}">
                                    <option value="">Select Province</option>
                                </select>
                                <select id="city-{{ $app->user_id }}" name="city_municipality" required disabled>
                                    <option value="">Select City / Municipality</option>
                                </select>
                                <button type="submit" class="add-area-btn">+ Add Area</button>
                            </form>

                        </div>

                    @endif

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-bicycle"></i></div>
                    <h3>{{ $search !== '' ? 'No riders match your search' : ($statusKey === 'pending' ? 'No applications waiting' : 'No riders here yet') }}</h3>
                    <p>{{ $statusKey === 'pending' ? 'All caught up — new rider applications will appear here.' : 'Rider applications will appear here for verification.' }}</p>
                </div>

            @endforelse
            @include('partials.simple-pager', ['paginator' => $riderApplications])

            </div>

        </div>

    </main>

    <script src="{{ asset('js/data/psgc-data.js') }}"></script>
    <script src="{{ asset('js/pages/registration-fields.js') }}"></script>
    <script>
        // Fill each approved rider's Province/City pickers — on load, and
        // again whenever live search swaps in a new list of riders.
        function initRiderAreaPickers() {
            document.querySelectorAll('select[id^="province-"]').forEach(function (province) {
                if (province.dataset.ready) return;
                province.dataset.ready = '1';
                initAddressCascade(province.id, 'city-' + province.id.slice('province-'.length));

                // Only the province the rider lives in can be picked.
                var only = province.dataset.only;
                if (only) {
                    Array.prototype.slice.call(province.options).forEach(function (o) {
                        if (o.value !== only) o.remove();
                    });
                    province.value = only;
                    province.dispatchEvent(new Event('change'));
                }
            });
        }

        initRiderAreaPickers();
        document.addEventListener('bb:live-updated', initRiderAreaPickers);
    </script>

    @include('partials.live-search')
    @include('partials.pwa-register')

</body>
</html>
