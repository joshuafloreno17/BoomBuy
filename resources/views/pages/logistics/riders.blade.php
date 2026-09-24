<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rider Management — BoomBuy Logistics</title>

    @include('partials.pwa-head')

    <link rel="stylesheet" href="{{ asset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/logistics-riders.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="riders" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header">
                <h1>Rider Management</h1>
                <p>Review rider applications and manage active rider accounts.</p>
            </div>

            @if (session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            @forelse ($riderApplications as $app)

                @php
                    $statusClass = 'status-' . strtolower(str_replace(' ', '-', $app->status));
                    $accountStatusClass = 'status-' . strtolower($app->account_status ?? 'active');
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
                        <div>
                            <strong>Applied</strong>
                            {{ \Illuminate\Support\Carbon::parse($app->created_at)->format('M d, Y') }}
                        </div>
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
                                <button type="submit" class="approve-btn">✓ Approve</button>
                            </form>

                            <form class="reject-form" method="POST" action="{{ route('logistics.riders.reject', $app->id) }}">
                                @csrf
                                <input type="text" name="admin_remarks" placeholder="Reason (optional)">
                                <button type="submit" class="reject-btn">✕ Reject</button>
                            </form>

                        </div>

                    @elseif ($app->status === 'Approved')

                        <div class="app-actions">

                            @if (($app->account_status ?? 'Active') === 'Active')

                                <form class="status-form" method="POST" action="{{ route('logistics.riders.toggle-status', $app->user_id) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="Deactivated">
                                    <button type="submit" class="deactivate-btn">Deactivate Account</button>
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

                            <div class="areas-label">Delivery Areas</div>

                            <div class="area-chips">
                                @forelse (($riderAreas[$app->user_id] ?? []) as $area)
                                    <span class="area-chip">
                                        {{ $area->city_municipality }}, {{ $area->province }}
                                        <form method="POST" action="{{ route('logistics.riders.areas.delete', $area->id) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="area-chip-remove" title="Remove">✕</button>
                                        </form>
                                    </span>
                                @empty
                                    <span class="no-areas">No areas assigned yet.</span>
                                @endforelse
                            </div>

                            <form method="POST" action="{{ route('logistics.riders.areas.store', $app->user_id) }}" class="area-add-form">
                                @csrf
                                <select id="province-{{ $app->user_id }}" name="province" required>
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
                    <div class="empty-icon">🛵</div>
                    <h3>No Rider Applications</h3>
                    <p>Rider applications will appear here for verification.</p>
                </div>

            @endforelse

        </div>

    </main>

    <script src="{{ asset('js/data/psgc-data.js') }}"></script>
    <script src="{{ asset('js/pages/registration-fields.js') }}"></script>
    <script>
        @foreach ($riderApplications as $app)
            @if($app->status === 'Approved')
                initAddressCascade('province-{{ $app->user_id }}', 'city-{{ $app->user_id }}');
            @endif
        @endforeach
    </script>

    @include('partials.pwa-register')

</body>
</html>
