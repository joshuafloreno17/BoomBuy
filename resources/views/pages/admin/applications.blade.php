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

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/admin-applications.css') }}">
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
                        <span style="color:#8a7f86;">({{ \Illuminate\Support\Carbon::parse($app->created_at)->diffForHumans() }})</span>
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
                                <select name="business_category" {{ empty($app->business_category) ? 'required' : '' }} style="padding:9px 11px; border:1px solid #f0e2da; border-radius:8px; font-size:11px; font-family:inherit;">
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
