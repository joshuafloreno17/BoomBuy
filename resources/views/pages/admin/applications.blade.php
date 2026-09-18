<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Seller & Rider Applications — BoomBuy</title>

    @include('partials.pwa-head')

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: #fff7f4;
            color: #172033;
        }

        a {
            text-decoration: none;
        }

        /* NAVBAR */

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #ffe9e2;
            padding: 18px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-family: 'Baloo 2', sans-serif;
            font-size: 23px;
            font-weight: 800;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .back {
            color: #8d6c62;
            font-size: 13px;
        }

        .back:hover {
            color: #e8420f;
        }

        /* PAGE */

        .container {
            width: 86%;
            max-width: 1100px;
            margin: 45px auto;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header small {
            color: #db5a33;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 10px;
            font-weight: 700;
        }

        .page-header h1 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 30px;
            margin-top: 7px;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #977970;
            font-size: 13px;
        }

        .success-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 12px 15px;
            border-radius: 9px;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .error-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 12px 15px;
            border-radius: 9px;
            font-size: 12px;
            margin-bottom: 20px;
        }

        /* TABS */

        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }

        .tab-btn {
            background: white;
            border: 1px solid #f7e5e0;
            color: #6a4e46;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .tab-btn.active {
            background: #e8420f;
            border-color: #e8420f;
            color: white;
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        /* APPLICATION CARD */

        .app-card {
            background: white;
            border: 1px solid #f7e5e0;
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 16px;
            box-shadow: 0 12px 30px rgba(39, 84, 150, 0.05);
        }

        .app-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .app-name {
            font-weight: 800;
            font-size: 15px;
        }

        .app-email {
            color: #8d6c62;
            font-size: 12px;
            margin-top: 2px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-pending-verification {
            background: #fffaed;
            color: #c2910c;
        }

        .status-approved {
            background: #f0fdf4;
            color: #15803d;
        }

        .status-rejected {
            background: #fff1f2;
            color: #be123c;
        }

        .app-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px 20px;

            font-size: 12px;
            color: #563a32;

            margin-bottom: 14px;
        }

        .app-details strong {
            display: block;
            color: #b99c93;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .documents {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }

        .doc-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fff6f3;
            border: 1px solid #f4ded6;
            color: #e8420f;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
        }

        .doc-link:hover {
            background: #ffe4dc;
        }

        .remarks-note {
            background: #fff1f2;
            color: #be123c;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 11px;
            margin-bottom: 14px;
        }

        .app-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            padding-top: 14px;
            border-top: 1px solid #f7efed;
        }

        .approve-form,
        .reject-form {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .reject-form input[type="text"] {
            padding: 9px 11px;
            border: 1px solid #f0ddd6;
            border-radius: 8px;
            font-size: 11px;
            font-family: inherit;
            width: 190px;
        }

        .approve-btn {
            background: #15803d;
            color: white;
            border: none;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .approve-btn:hover {
            background: #116530;
        }

        .reject-btn {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .reject-btn:hover {
            background: #be123c;
            color: white;
        }

        .empty {
            text-align: center;
            padding: 55px 20px;
            background: white;
            border: 1px solid #f7e5e0;
            border-radius: 16px;
        }

        .empty-icon {
            font-size: 45px;
            margin-bottom: 12px;
        }

        .empty h3 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 17px;
            margin-bottom: 7px;
        }

        .empty p {
            color: #b99c93;
            font-size: 12px;
        }

        .footer {
            text-align: center;
            color: #b99c93;
            font-size: 11px;
            margin: 30px 0;
        }

        @media (max-width: 500px) {
            .navbar {
                padding: 16px 5%;
            }

            .back {
                display: none;
            }

            .app-card-top {
                flex-direction: column;
            }

            .reject-form input[type="text"] {
                width: 140px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">

        <a href="{{ route('admin.dashboard') }}" class="logo">
            Boom<span>Buy</span>
        </a>

        <a href="{{ route('admin.dashboard') }}" class="back">
            ← Admin Dashboard
        </a>

    </nav>


    <div class="container">

        <div class="page-header">
            <small>Admin Panel</small>
            <h1>Seller &amp; Rider Applications</h1>
            <p>Review uploaded IDs and documents, then approve or reject applicants before they can log in.</p>
        </div>

        @if (session('success'))
            <div class="success-box"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><polyline points="16 9 11 14 8 11"/></svg> {{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="error-box"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> {{ session('error') }}</div>
        @endif

        <div class="tabs">
            <button type="button" class="tab-btn active" data-tab="sellers">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M3 9l1-5h16l1 5"/><path d="M3 9a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0"/><path d="M4 9v9a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/></svg>
                Sellers ({{ count($sellerApplications) }})
            </button>
            <button type="button" class="tab-btn" data-tab="riders">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="5" cy="18" r="3"/><circle cx="19" cy="18" r="3"/><path d="M5 18h6l3-6h4"/><path d="M10 12h3l2-4h3"/><circle cx="17" cy="7" r="1.3"/></svg>
                Riders ({{ count($riderApplications) }})
            </button>
        </div>

        <!-- SELLERS -->
        <div class="tab-panel active" id="tab-sellers">

            @forelse ($sellerApplications as $app)

                @php
                    $statusClass = 'status-' . strtolower(str_replace(' ', '-', $app->status));
                @endphp

                <div class="app-card">

                    <div class="app-card-top">
                        <div>
                            <div class="app-name">{{ $app->full_name }}</div>
                            <div class="app-email">{{ $app->user_email }}</div>
                        </div>
                        <span class="status-badge {{ $statusClass }}">{{ $app->status }}</span>
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
                            <strong>Applied</strong>
                            {{ \Illuminate\Support\Carbon::parse($app->created_at)->format('M d, Y') }}
                        </div>
                    </div>

                    <div class="documents">
                        @if ($app->national_id)
                            <a class="doc-link" target="_blank" href="{{ route('admin.applications.document', ['type' => 'seller', 'id' => $app->id, 'field' => 'national_id']) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                Valid Government ID
                            </a>
                        @endif
                        @if ($app->business_permit)
                            <a class="doc-link" target="_blank" href="{{ route('admin.applications.document', ['type' => 'seller', 'id' => $app->id, 'field' => 'business_permit']) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Proof of Business
                            </a>
                        @endif
                    </div>

                    @if ($app->status === 'Rejected' && $app->admin_remarks)
                        <div class="remarks-note">
                            Rejection reason: {{ $app->admin_remarks }}
                        </div>
                    @endif

                    @if ($app->status === 'Pending Verification')
                        <div class="app-actions">

                            <form class="approve-form" method="POST" action="{{ route('admin.applications.approve', ['type' => 'seller', 'id' => $app->id]) }}">
                                @csrf
                                <button type="submit" class="approve-btn"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><polyline points="16 9 11 14 8 11"/></svg> Approve</button>
                            </form>

                            <form class="reject-form" method="POST" action="{{ route('admin.applications.reject', ['type' => 'seller', 'id' => $app->id]) }}">
                                @csrf
                                <input type="text" name="admin_remarks" placeholder="Reason (optional)">
                                <button type="submit" class="reject-btn"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Reject</button>
                            </form>

                        </div>
                    @endif

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#e5c8bf" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l1-5h16l1 5"/><path d="M3 9a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0"/><path d="M4 9v9a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/></svg></div>
                    <h3>No Seller Applications</h3>
                    <p>Seller registrations will appear here for verification.</p>
                </div>

            @endforelse

        </div>

        <!-- RIDERS -->
        <div class="tab-panel" id="tab-riders">

            @forelse ($riderApplications as $app)

                @php
                    $statusClass = 'status-' . strtolower(str_replace(' ', '-', $app->status));
                @endphp

                <div class="app-card">

                    <div class="app-card-top">
                        <div>
                            <div class="app-name">{{ $app->full_name }}</div>
                            <div class="app-email">{{ $app->user_email }}</div>
                        </div>
                        <span class="status-badge {{ $statusClass }}">{{ $app->status }}</span>
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
                            <a class="doc-link" target="_blank" href="{{ route('admin.applications.document', ['type' => 'rider', 'id' => $app->id, 'field' => 'national_id']) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>National ID
                            </a>
                        @endif
                        @if ($app->drivers_license)
                            <a class="doc-link" target="_blank" href="{{ route('admin.applications.document', ['type' => 'rider', 'id' => $app->id, 'field' => 'drivers_license']) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Driver's License
                            </a>
                        @endif
                        @if ($app->profile_selfie)
                            <a class="doc-link" target="_blank" href="{{ route('admin.applications.document', ['type' => 'rider', 'id' => $app->id, 'field' => 'profile_selfie']) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Selfie
                            </a>
                        @endif
                        @if ($app->proof_of_address)
                            <a class="doc-link" target="_blank" href="{{ route('admin.applications.document', ['type' => 'rider', 'id' => $app->id, 'field' => 'proof_of_address']) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Proof of Address
                            </a>
                        @endif
                        @if ($app->or_cr)
                            <a class="doc-link" target="_blank" href="{{ route('admin.applications.document', ['type' => 'rider', 'id' => $app->id, 'field' => 'or_cr']) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>OR/CR
                            </a>
                        @endif
                    </div>

                    @if ($app->status === 'Rejected' && $app->admin_remarks)
                        <div class="remarks-note">
                            Rejection reason: {{ $app->admin_remarks }}
                        </div>
                    @endif

                    @if ($app->status === 'Pending Verification')
                        <div class="app-actions">

                            <form class="approve-form" method="POST" action="{{ route('admin.applications.approve', ['type' => 'rider', 'id' => $app->id]) }}">
                                @csrf
                                <button type="submit" class="approve-btn"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><polyline points="16 9 11 14 8 11"/></svg> Approve</button>
                            </form>

                            <form class="reject-form" method="POST" action="{{ route('admin.applications.reject', ['type' => 'rider', 'id' => $app->id]) }}">
                                @csrf
                                <input type="text" name="admin_remarks" placeholder="Reason (optional)">
                                <button type="submit" class="reject-btn"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Reject</button>
                            </form>

                        </div>
                    @endif

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#e5c8bf" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="5" cy="18" r="3"/><circle cx="19" cy="18" r="3"/><path d="M5 18h6l3-6h4"/><path d="M10 12h3l2-4h3"/><circle cx="17" cy="7" r="1.3"/></svg></div>
                    <h3>No Rider Applications</h3>
                    <p>Rider applications will appear here for verification.</p>
                </div>

            @endforelse

        </div>

        <div class="footer">
            © 2026 BoomBuy · Admin Verification
        </div>

    </div>

    <script>
        document.querySelectorAll('.tab-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.tab-btn').forEach(function (b) {
                    b.classList.remove('active');
                });
                document.querySelectorAll('.tab-panel').forEach(function (p) {
                    p.classList.remove('active');
                });

                btn.classList.add('active');
                document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
            });
        });
    </script>

    @include('partials.pwa-register')

</body>

</html>
