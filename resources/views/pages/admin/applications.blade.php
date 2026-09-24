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

<div class="layout">

    <x-layout.admin-sidebar active="applications" />

    <main class="main">

    <div class="container">

        <div class="page-header">
            <small>Admin Panel</small>
            <h1>Account Registrations</h1>
            <p>Review uploaded IDs and documents, then approve or reject applicants before they can log in. Rider applications are now managed by the Logistics Center.</p>
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
            <button type="button" class="tab-btn" data-tab="buyers">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Buyers ({{ count($buyerApplications) }})
            </button>
            <button type="button" class="tab-btn" data-tab="logistics">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                Logistics ({{ count($logisticsApplications) }})
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
                        @if (!empty($app->business_name))
                            <div>
                                <strong>Business Name</strong>
                                {{ $app->business_name }}
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
                                @php
                                    $categoryOptions = [
                                        'electronics' => '📱 Electronics',
                                        'womens-fashion' => "👗 Women's Fashion",
                                        'mens-fashion' => "👕 Men's Fashion",
                                        'kids-baby' => '👶 Kids & Baby',
                                        'home-living' => '🏠 Home & Living',
                                        'sports-outdoors' => '⚽ Sports & Outdoors',
                                        'beauty-personal-care' => '💄 Beauty & Personal Care',
                                        'food-beverages' => '🍔 Food & Beverages',
                                        'automotive' => '🚗 Automotive',
                                        'office-school' => '📚 Office & School',
                                        'pet-supplies' => '🐶 Pet Supplies',
                                        'toys-games-hobbies' => '🎮 Toys, Games & Hobbies',
                                        'jewelry-accessories' => '💍 Jewelry & Accessories',
                                        'shoes' => '👟 Shoes',
                                        'tools-home-improvement' => '🧰 Tools & Home Improvement',
                                        'garden-outdoor' => '🌱 Garden & Outdoor',
                                    ];
                                @endphp
                                <select name="business_category" {{ empty($app->business_category) ? 'required' : '' }} style="padding:9px 11px; border:1px solid #f0ddd6; border-radius:8px; font-size:11px; font-family:inherit;">
                                    <option value="">
                                        {{ empty($app->business_category) ? 'Registered Category…' : 'Change category…' }}
                                    </option>
                                    @foreach ($categoryOptions as $value => $label)
                                        <option value="{{ $value }}" {{ $app->business_category === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                            {{ $app->business_category === $value ? ' (declared by seller)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
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

        <!-- BUYERS -->
        <div class="tab-panel" id="tab-buyers">

            @forelse ($buyerApplications as $app)

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
                        @if ($app->id_photo)
                            <a class="doc-link" target="_blank" href="{{ route('admin.applications.document', ['type' => 'buyer', 'id' => $app->id, 'field' => 'id_photo']) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Valid ID
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

                            <form class="approve-form" method="POST" action="{{ route('admin.applications.approve', ['type' => 'buyer', 'id' => $app->id]) }}">
                                @csrf
                                <button type="submit" class="approve-btn"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><polyline points="16 9 11 14 8 11"/></svg> Approve</button>
                            </form>

                            <form class="reject-form" method="POST" action="{{ route('admin.applications.reject', ['type' => 'buyer', 'id' => $app->id]) }}">
                                @csrf
                                <input type="text" name="admin_remarks" placeholder="Reason (optional)">
                                <button type="submit" class="reject-btn"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Reject</button>
                            </form>

                        </div>
                    @endif

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#e5c8bf" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></div>
                    <h3>No Buyer Applications</h3>
                    <p>Buyer registrations will appear here for verification.</p>
                </div>

            @endforelse

        </div>

        <!-- LOGISTICS -->
        <div class="tab-panel" id="tab-logistics">

            @forelse ($logisticsApplications as $app)

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
                        @if ($app->id_photo)
                            <a class="doc-link" target="_blank" href="{{ route('admin.applications.document', ['type' => 'logistics', 'id' => $app->id, 'field' => 'id_photo']) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Valid ID
                            </a>
                        @endif
                        @if ($app->business_permit)
                            <a class="doc-link" target="_blank" href="{{ route('admin.applications.document', ['type' => 'logistics', 'id' => $app->id, 'field' => 'business_permit']) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Business/DTI Permit
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

                            <form class="approve-form" method="POST" action="{{ route('admin.applications.approve', ['type' => 'logistics', 'id' => $app->id]) }}">
                                @csrf
                                <button type="submit" class="approve-btn"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><polyline points="16 9 11 14 8 11"/></svg> Approve</button>
                            </form>

                            <form class="reject-form" method="POST" action="{{ route('admin.applications.reject', ['type' => 'logistics', 'id' => $app->id]) }}">
                                @csrf
                                <input type="text" name="admin_remarks" placeholder="Reason (optional)">
                                <button type="submit" class="reject-btn"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Reject</button>
                            </form>

                        </div>
                    @endif

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#e5c8bf" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg></div>
                    <h3>No Logistics Applications</h3>
                    <p>Logistics/sorting center registrations will appear here for verification.</p>
                </div>

            @endforelse

        </div>

        <div class="footer">
            © 2026 BoomBuy · Admin Verification
        </div>

    </div>

    </main>

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
