<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Account Registrations — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/admin-sidebar.css') }}">

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
            .app-card-top {
                flex-direction: column;
            }

            .reject-form input[type="text"] {
                width: 140px;
            }
        }

        /* TYPE TABS, STATUS CHIPS, SEARCH */

        a.tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .tab-btn .pending-dot {
            min-width: 20px;
            padding: 1px 7px;
            border-radius: 999px;
            background: #e8420f;
            color: #fff;
            font-size: 11px;
            text-align: center;
        }

        .tab-btn.active .pending-dot {
            background: #fff;
            color: #e8420f;
        }

        .filter-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 18px;
        }

        .status-chips {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .status-chip {
            padding: 7px 13px;
            border-radius: 999px;
            border: 1px solid #f0ddd6;
            background: #fff;
            color: #6a4e46;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .status-chip.active {
            background: #fff0eb;
            border-color: #e8420f;
            color: #c43408;
        }

        /* Pending with something waiting: solid, so it can't be missed. */
        .status-chip.has-work {
            background: #e8420f;
            border-color: #e8420f;
            color: #fff;
        }

        .app-search {
            flex: 1;
            min-width: 220px;
            display: flex;
            gap: 8px;
        }

        .app-search input {
            flex: 1;
            padding: 10px 13px;
            border: 1px solid #f0ddd6;
            border-radius: 10px;
            font-size: 13px;
            font-family: inherit;
        }

        .app-search button,
        .app-search a {
            padding: 10px 14px;
            border: none;
            border-radius: 10px;
            background: #e8420f;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
        }

        .app-search a {
            background: #f7f0ee;
            color: #553b33;
        }

        @media (max-width: 640px) {
            .tabs { flex-wrap: wrap; }
            .app-search { min-width: 100%; }
            .approve-form, .reject-form { width: 100%; flex-wrap: wrap; }
            .approve-form select, .reject-form input[type="text"] { flex: 1; min-width: 0; width: auto; }
        }
    </style>
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="applications" />

    <main class="main">

    <div class="container">

        <div class="page-header">
            <small>Admin Panel</small>
            <h1>Account Registrations</h1>
            <p>Review uploaded IDs and documents, then approve or reject applicants before they can log in. Rider applications are managed by the Logistics Center — see the <a href="{{ route('admin.logistics') }}" style="color:#db5a33; font-weight:600;">Logistics overview</a> for a read-only view.</p>
        </div>

        @if (session('success'))
            <div class="success-box"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="error-box"><i class="bi bi-x-circle-fill"></i> {{ session('error') }}</div>
        @endif

        @php
            $typeIcons = ['seller' => 'bi-shop', 'buyer' => 'bi-bag', 'logistics' => 'bi-box-seam'];
            $typeNouns = ['seller' => 'seller', 'buyer' => 'buyer', 'logistics' => 'logistics'];
            $docLabels = [
                'seller' => ['national_id' => 'Valid Government ID', 'business_permit' => 'Proof of Business'],
                'buyer' => ['id_photo' => 'Valid ID'],
                'logistics' => ['id_photo' => 'Valid ID', 'business_permit' => 'Business/DTI Permit'],
            ];
        @endphp

        {{-- TYPE TABS (badge = waiting for review) --}}
        <div class="tabs" id="liveTabs">
            @foreach ($types as $key => $label)
                <a href="{{ route('admin.applications', ['type' => $key]) }}" class="tab-btn {{ $type === $key ? 'active' : '' }}">
                    <i class="bi {{ $typeIcons[$key] }}"></i> {{ $label }}
                    @if ($pendingCounts[$key] > 0)
                        <span class="pending-dot" title="Waiting for review">{{ $pendingCounts[$key] }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- STATUS + SEARCH --}}
        <div class="filter-row">
            <div class="status-chips" id="liveChips">
                @foreach ($statuses as $key => $definition)
                    @php
                        $chipCount = $definition['status'] === null
                            ? $statusCounts->sum()
                            : ($statusCounts[$definition['status']] ?? 0);
                    @endphp
                    <a
                        href="{{ route('admin.applications', array_filter(['type' => $type, 'status' => $key, 'q' => $search])) }}"
                        class="status-chip {{ $statusKey === $key ? 'active' : '' }} {{ $key === 'pending' && $chipCount > 0 ? 'has-work' : '' }}"
                    >
                        {{ $definition['label'] }} ({{ $chipCount }})
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.applications') }}" class="app-search" data-live-search data-live-target="#liveTabs, #liveChips, #liveClear, #liveResults">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="status" value="{{ $statusKey }}">
                <input type="search" name="q" value="{{ $search }}" placeholder="Search name, email, phone{{ $type !== 'buyer' ? ' or business' : '' }}" aria-label="Search applications">
                <button type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
                <span id="liveClear" style="display:contents;">
                @if ($search !== '')
                    <a href="{{ route('admin.applications', ['type' => $type, 'status' => $statusKey]) }}" title="Clear search"><i class="bi bi-x-lg"></i></a>
                @endif
                </span>
            </form>
        </div>

        <div id="liveResults">

        @forelse ($applications as $app)

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
                    @if (!empty($app->business_name))
                        <div>
                            <strong>Business Name</strong>
                            {{ $app->business_name }}
                        </div>
                    @endif
                    @if ($type === 'seller' && !empty($app->business_category))
                        <div>
                            <strong>Category</strong>
                            {{ $categories[$app->business_category] ?? $app->business_category }}
                        </div>
                    @endif
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
                        <span style="color:#a88d85;">({{ \Illuminate\Support\Carbon::parse($app->created_at)->diffForHumans() }})</span>
                    </div>
                    @if ($app->reviewed_at)
                        <div>
                            <strong>Reviewed</strong>
                            {{ \Illuminate\Support\Carbon::parse($app->reviewed_at)->format('M d, Y') }}
                        </div>
                    @endif
                </div>

                <div class="documents">
                    @foreach ($docLabels[$type] as $field => $label)
                        @if (!empty($app->$field))
                            <a class="doc-link" target="_blank" href="{{ route('admin.applications.document', ['type' => $type, 'id' => $app->id, 'field' => $field]) }}">
                                <i class="bi bi-file-earmark-text"></i> {{ $label }}
                            </a>
                        @else
                            <span class="doc-link" style="opacity:.55;"><i class="bi bi-file-earmark-x"></i> No {{ $label }}</span>
                        @endif
                    @endforeach
                </div>

                @if ($app->status === 'Rejected' && $app->admin_remarks)
                    <div class="remarks-note">
                        Rejection reason: {{ $app->admin_remarks }}
                    </div>
                @endif

                @if ($app->status === 'Pending Verification')
                    <div class="app-actions">

                        <form class="approve-form" method="POST" action="{{ route('admin.applications.approve', ['type' => $type, 'id' => $app->id]) }}">
                            @csrf
                            @if ($type === 'seller')
                                <select name="business_category" {{ empty($app->business_category) ? 'required' : '' }} style="padding:9px 11px; border:1px solid #f0ddd6; border-radius:8px; font-size:11px; font-family:inherit;">
                                    <option value="">
                                        {{ empty($app->business_category) ? 'Registered Category…' : 'Change category…' }}
                                    </option>
                                    @foreach ($categories as $value => $label)
                                        <option value="{{ $value }}" {{ $app->business_category === $value ? 'selected' : '' }}>
                                            {{ $label }}{{ $app->business_category === $value ? ' (declared by seller)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            <button type="submit" class="approve-btn"><i class="bi bi-check-circle"></i> Approve</button>
                        </form>

                        <form
                            class="reject-form"
                            method="POST"
                            action="{{ route('admin.applications.reject', ['type' => $type, 'id' => $app->id]) }}"
                            data-confirm="Reject the application of {{ $app->full_name }}? They will be notified by email."
                            data-confirm-ok="Reject"
                            data-confirm-danger
                        >
                            @csrf
                            <input type="text" name="admin_remarks" placeholder="Reason (shown to the applicant)">
                            <button type="submit" class="reject-btn"><i class="bi bi-x-circle"></i> Reject</button>
                        </form>

                    </div>
                @endif

            </div>

        @empty

            <div class="empty">
                <div class="empty-icon"><i class="bi {{ $typeIcons[$type] }}" style="font-size:40px; color:#e5c8bf;"></i></div>
                <h3>
                    No {{ $statusKey === 'all' ? '' : strtolower($statuses[$statusKey]['label']) }} {{ $typeNouns[$type] }} applications
                </h3>
                <p>
                    @if ($search !== '')
                        Nothing matches "{{ $search }}".
                    @elseif ($statusKey === 'pending')
                        All caught up — nothing is waiting for review.
                    @else
                        Registrations will appear here once submitted.
                    @endif
                </p>
            </div>

        @endforelse

        @include('partials.simple-pager', ['paginator' => $applications])

        </div>

        <div class="footer">
            © {{ date('Y') }} BoomBuy · Admin Verification
        </div>

    </div>

    </main>

</div>

    @include('partials.live-search')
    @include('partials.pwa-register')

</body>

</html>
