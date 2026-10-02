<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard — BoomBuy Logistics</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/logistics-dashboard.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="dashboard" :user="$user" />

    <main class="main-content">

        <div class="container">

            @include('partials.announcement-banner')

            <div class="page-header">
                <h1>Logistics Dashboard</h1>
                <p>Welcome back, {{ $user['name'] ?? 'Logistics Partner' }}.</p>
            </div>

            @if (session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if($application)
                <div class="status-card">
                    <div>
                        <strong>{{ $application->business_name ?? 'Your Logistics Account' }}</strong>
                        <div class="muted-sm">Sorting Center · {{ now()->format('l, M d') }}</div>
                    </div>
                    <span class="status-badge status-approved"><i class="bi bi-patch-check-fill"></i>&nbsp;{{ $application->status }}</span>
                </div>
            @endif

            {{-- TODAY --}}
            <div class="stats-row">

                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                    <span class="stat-label">Delivered Today</span>
                    <strong class="stat-value">{{ $deliveredToday }}</strong>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
                    <span class="stat-label">Failed Attempts Today</span>
                    <strong class="stat-value">{{ $failedToday }}</strong>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-arrow-return-left"></i></div>
                    <span class="stat-label">Returned (7 days)</span>
                    <strong class="stat-value">{{ $returnedThisWeek }}</strong>
                </div>

                <a class="stat-card" href="{{ route('logistics.riders') }}">
                    <div class="stat-icon"><i class="bi bi-bicycle"></i></div>
                    <span class="stat-label">Active Riders</span>
                    <strong class="stat-value">{{ $activeRiders }}</strong>
                    @if($ridersWithoutArea > 0)
                        <span class="stat-note"><i class="bi bi-geo-alt"></i> {{ $ridersWithoutArea }} without an area</span>
                    @endif
                </a>

            </div>

            {{-- TO DO --}}
            <h2 class="section-title">Needs your attention</h2>

            <div class="todo-grid">
                @foreach($todo as $task)
                    <a href="{{ $task['url'] }}" class="todo-card {{ $task['count'] > 0 ? 'has-work' : '' }}">
                        <div class="todo-top">
                            <span class="todo-icon"><i class="bi {{ $task['icon'] }}"></i></span>
                            <strong class="todo-count">{{ $task['count'] }}</strong>
                        </div>
                        <div class="todo-label">{{ $task['label'] }} <i class="bi bi-chevron-right"></i></div>
                        <div class="todo-hint">{{ $task['hint'] }}</div>
                    </a>
                @endforeach
            </div>

            <div class="dash-grid">

                {{-- PIPELINE --}}
                <section class="panel">
                    <h2 class="section-title">Parcel pipeline</h2>

                    @php $maxCount = max(1, $pipeline->max('count')); @endphp

                    <ul class="pipeline">
                        @foreach($pipeline as $status => $row)
                            <li>
                                <div class="pipeline-head">
                                    <x-status-pill :status="$status" />
                                    <strong>{{ $row['count'] }}</strong>
                                </div>
                                <div class="pipeline-bar"><span style="width: {{ round($row['count'] / $maxCount * 100) }}%"></span></div>
                                <div class="pipeline-hint">{{ $row['hint'] }}</div>
                            </li>
                        @endforeach
                    </ul>
                </section>

                {{-- RECENT --}}
                <section class="panel">
                    <h2 class="section-title">Latest parcel updates</h2>

                    @forelse($recentParcels as $parcel)
                        <div class="recent-row">
                            <div class="recent-main">
                                <strong>#{{ $parcel->id }}</strong> · {{ $parcel->shipping_name }}
                                <div class="recent-sub">{{ \Illuminate\Support\Str::limit($parcel->shipping_address, 60) }}</div>
                            </div>
                            <div class="recent-side">
                                <x-status-pill :status="$parcel->status" />
                                <div class="recent-sub">{{ \Carbon\Carbon::parse($parcel->updated_at)->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="empty-note"><i class="bi bi-inbox"></i> No parcels in the delivery pipeline yet.</p>
                    @endforelse

                    <a href="{{ route('logistics.parcels') }}" class="panel-link">Open Parcels <i class="bi bi-arrow-right"></i></a>
                </section>

            </div>

        </div>

    </main>

    @include('partials.pwa-register')

</body>
</html>
