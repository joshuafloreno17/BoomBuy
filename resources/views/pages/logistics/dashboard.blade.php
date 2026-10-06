<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Dashboard — BoomBuy Logistics'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/portal-dash.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/logistics-dashboard.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="dashboard" :user="$user" />

    <main class="main-content">
        <div class="pd">

            @include('partials.announcement-banner')

            @php
                $steps = [
                    ['label' => 'Dropped off', 'count' => $pipeline['Dropped Off']['count'] ?? 0, 'note' => 'Confirm when they arrive', 'url' => route('logistics.parcels') . '#awaiting-confirmation'],
                    ['label' => 'Between centers', 'count' => $pipeline['In Transit']['count'] ?? 0, 'note' => 'Confirm when they arrive', 'url' => route('logistics.parcels') . '#incoming'],
                    ['label' => 'At a center', 'count' => $pipeline['At Sorting Center']['count'] ?? 0, 'note' => 'Dispatch or assign a rider', 'url' => route('logistics.parcels') . '#awaiting-assignment', 'hot' => true],
                    ['label' => 'Out for delivery', 'count' => ($pipeline['Assigned for Delivery']['count'] ?? 0) + ($pipeline['Out for Delivery']['count'] ?? 0), 'note' => 'On the road now', 'url' => route('logistics.parcels')],
                    ['label' => 'Delivered today', 'count' => $deliveredToday, 'note' => $failedToday > 0 ? $failedToday . ' failed today' : 'No failed attempts', 'url' => route('logistics.parcels')],
                ];
                $hubName = $application->business_name ?? null;
            @endphp

            <header class="pd-head">
                <div class="pd-head-text">
                    <p class="pd-eyebrow">{{ $hubName ? $hubName . ' · ' : '' }}{{ now()->format('l, F j') }}</p>
                    <h1 class="pd-title">Today's parcels</h1>
                </div>
                <form class="pd-search" action="{{ route('logistics.parcels') }}" method="GET" role="search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" name="q" placeholder="Find order # or buyer" aria-label="Find a parcel by order number or buyer">
                </form>
            </header>

            @if(session('success'))
                <div class="pd-alert is-ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="pd-alert is-error"><i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}</div>
            @endif

            <section class="pd-pipeline" aria-label="Delivery pipeline">
                @foreach($steps as $step)
                    <a href="{{ $step['url'] }}" class="pd-step {{ !empty($step['hot']) ? 'is-hot' : '' }}">
                        <span class="pd-step-label">{{ $step['label'] }}</span>
                        <span class="pd-step-count">{{ $step['count'] }}</span>
                        <span class="pd-step-note">{{ $step['note'] }}</span>
                    </a>
                @endforeach
            </section>

            <div class="pd-row">
                <section class="pd-card pd-grow-2" aria-labelledby="needs-title">
                    <div class="pd-card-head">
                        <h2 class="pd-card-title" id="needs-title">Needs a rider</h2>
                        <a href="{{ route('logistics.parcels') }}#awaiting-assignment" class="pd-link">All parcels →</a>
                    </div>

                    @if($needsRider->isEmpty())
                        <div class="pd-empty"><i class="bi bi-inbox"></i>Every parcel at the Sorting Center has a rider.</div>
                    @else
                        <div class="pd-table-wrap">
                            <table class="pd-table pd-stack">
                                <thead>
                                    <tr><th>Order</th><th>Deliver to</th><th>Waiting</th><th><span style="position:absolute; width:1px; height:1px; overflow:hidden;">Action</span></th></tr>
                                </thead>
                                <tbody>
                                    @foreach($needsRider as $parcel)
                                        @php
                                            $since = \Carbon\Carbon::parse($parcel->sorting_center_received_at ?? $parcel->updated_at);
                                            $old = $since->lt(now()->subHours(2));
                                        @endphp
                                        <tr>
                                            <td><span class="pd-strong">#{{ $parcel->id }}</span><div class="pd-sub" style="font-size:12.5px;">{{ $parcel->shipping_name }}</div></td>
                                            <td class="pd-sub">{{ \Illuminate\Support\Str::limit($parcel->shipping_address, 48) }}</td>
                                            <td class="pd-hide-sm"><span class="pd-tag" @if($old) style="background:#ffe6db; color:#a3300c;" @endif>{{ $since->diffForHumans(null, true) }}</span></td>
                                            <td style="text-align:right;"><a href="{{ route('logistics.parcels') }}#awaiting-assignment" class="pd-btn pd-btn-primary pd-btn-sm">Assign rider</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="pd-card pd-grow-1" aria-labelledby="todo-title">
                    <div class="pd-card-head">
                        <h2 class="pd-card-title" id="todo-title">Needs your attention</h2>
                    </div>
                    <div class="pd-todo">
                        @foreach($todo as $item)
                            <a href="{{ $item['url'] }}" class="pd-todo-item {{ $item['count'] > 0 ? 'is-hot' : 'is-done' }}">
                                <span class="pd-todo-icon"><i class="bi {{ str_replace('-fill', '', $item['icon']) }}"></i></span>
                                <span class="pd-todo-text"><strong>{{ $item['label'] }}</strong><span>{{ \Illuminate\Support\Str::before($item['hint'], ' —') }}</span></span>
                                <span class="pd-todo-count">{{ $item['count'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="pd-row">
                <section class="pd-card pd-grow-1" aria-labelledby="riders-title">
                    <div class="pd-card-head">
                        <h2 class="pd-card-title" id="riders-title">Riders on duty</h2>
                        <a href="{{ route('logistics.riders') }}" class="pd-link">Manage →</a>
                    </div>
                    @if($ridersOnDuty->isEmpty())
                        <div class="pd-empty"><i class="bi bi-bicycle"></i>No active riders yet.</div>
                    @else
                        <div class="pd-list">
                            @foreach($ridersOnDuty as $rider)
                                <div class="pd-list-row">
                                    <span class="pd-avatar">{{ mb_strtoupper(mb_substr($rider['name'], 0, 1)) }}</span>
                                    <span class="pd-list-main">
                                        <strong>{{ $rider['name'] }}</strong>
                                        <span>{{ $rider['area'] ?: 'No area set' }}</span>
                                    </span>
                                    <span style="text-align:right;">
                                        <strong style="display:block;">{{ $rider['load'] }}</strong>
                                        <span class="pd-sub" style="font-size:12px;">{{ $rider['load'] === 1 ? 'parcel' : 'parcels' }}</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if($ridersWithoutArea > 0)
                        <p class="pd-muted" style="margin:12px 0 0;"><i class="bi bi-info-circle"></i> {{ $ridersWithoutArea }} {{ $ridersWithoutArea === 1 ? 'rider has' : 'riders have' }} no delivery area yet.</p>
                    @endif
                </section>

                <section class="pd-card pd-grow-2" aria-labelledby="latest-title">
                    <div class="pd-card-head">
                        <h2 class="pd-card-title" id="latest-title">Latest parcel updates</h2>
                        <span class="pd-muted">{{ $returnedThisWeek }} returned to sellers this week</span>
                    </div>
                    @if($recentParcels->isEmpty())
                        <div class="pd-empty"><i class="bi bi-box-seam"></i>No parcels yet.</div>
                    @else
                        <div class="pd-table-wrap">
                            <table class="pd-table pd-stack">
                                <thead>
                                    <tr><th>Order</th><th>Buyer</th><th>Updated</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($recentParcels as $parcel)
                                        <tr>
                                            <td><span class="pd-strong">#{{ $parcel->id }}</span><div class="pd-sub" style="font-size:12.5px;">{{ \Illuminate\Support\Str::limit($parcel->shipping_address, 30) }}</div></td>
                                            <td class="pd-hide-sm">{{ $parcel->shipping_name }}</td>
                                            <td class="pd-sub">{{ \Carbon\Carbon::parse($parcel->updated_at)->diffForHumans() }}</td>
                                            <td><x-status-pill :status="$parcel->status" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>

        </div>
    </main>

    @include('partials.pwa-register')
</body>
</html>
