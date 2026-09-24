<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard — BoomBuy Logistics</title>

    @include('partials.pwa-head')

    <link rel="stylesheet" href="{{ asset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/logistics-dashboard.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="dashboard" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header">
                <h1>Logistics Dashboard</h1>
                <p>Welcome back, {{ $user['name'] ?? 'Logistics Partner' }}.</p>
            </div>

            @if (session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if($application)
                @php
                    $statusClass = match($application->status) {
                        'Approved' => 'status-approved',
                        'Rejected' => 'status-rejected',
                        default => 'status-pending',
                    };
                @endphp

                <div class="status-card">
                    <div>
                        <strong>{{ $application->business_name ?? 'Your Logistics Account' }}</strong>
                        <div style="color:#8d6c62; font-size:12px; margin-top:4px;">
                            Verification Status
                        </div>
                    </div>
                    <span class="status-badge {{ $statusClass }}">{{ $application->status }}</span>
                </div>
            @endif

            <div class="stats-row">

                <div class="stat-card">
                    <span class="stat-label">Parcels for Sorting</span>
                    <strong class="stat-value">{{ $parcelsForSorting }}</strong>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Active Riders</span>
                    <strong class="stat-value">{{ $activeRiders }}</strong>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Delivered Today</span>
                    <strong class="stat-value">{{ $deliveredToday }}</strong>
                </div>

            </div>

            <div class="coming-soon-card">
                <h3>🚧 More Tools Coming Soon</h3>
                <ul>
                    <li>Delivery monitoring dashboard &amp; reports</li>
                </ul>
            </div>

        </div>

    </main>

    @include('partials.pwa-register')

</body>
</html>
