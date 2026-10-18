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
        <div class="lc">

            @include('partials.announcement-banner')

            @php
                $steps = [
                    ['label' => 'Arriving from sellers', 'count' => ($pipeline['Dropped Off']['count'] ?? 0) + ($pipeline['Picked Up']['count'] ?? 0), 'note' => 'Confirm arrival', 'url' => route('logistics.parcels') . '#awaiting-confirmation', 'tone' => 'is-plain'],
                    ['label' => 'Between centers', 'count' => ($pipeline['In Transit']['count'] ?? 0) + ($pipeline['To Branch']['count'] ?? 0), 'note' => 'Confirm arrival', 'url' => route('logistics.parcels') . '#incoming', 'tone' => 'is-plain'],
                    ['label' => 'At a center', 'count' => ($pipeline['At Sorting Center']['count'] ?? 0) + ($pipeline['Sorted']['count'] ?? 0) + ($pipeline['At Branch']['count'] ?? 0), 'note' => 'Sort, dispatch or assign', 'url' => route('logistics.parcels') . '#awaiting-assignment', 'tone' => 'is-violet'],
                    ['label' => 'Out for delivery', 'count' => ($pipeline['Assigned for Delivery']['count'] ?? 0) + ($pipeline['Out for Delivery']['count'] ?? 0), 'note' => 'On the road now', 'url' => route('logistics.parcels') . '#on-the-road', 'tone' => 'is-blue'],
                    ['label' => 'Delivered today', 'count' => $deliveredToday, 'note' => $failedToday > 0 ? $failedToday . ' failed today' : 'No failed attempts', 'url' => route('logistics.parcels'), 'tone' => 'is-green'],
                ];

                // The busiest step gets the "Most waiting" tag (not the delivered one).
                $waitingCounts = array_map(fn ($s) => $s['count'], array_slice($steps, 0, 4));
                $busiest = max($waitingCounts) > 0 ? array_search(max($waitingCounts), $waitingCounts, true) : null;

                $onTheRoad = $steps[3]['count'];
                $dayTotal = $deliveredToday + $onTheRoad + $failedToday;
                $donePct = $dayTotal > 0 ? (int) round($deliveredToday / $dayTotal * 100) : 0;
                $ring = 2 * M_PI * 50;

                $waiting = collect($todo)->sum('count');
                $nextTask = collect($todo)->first(fn ($t) => $t['count'] > 0);

                $hour = (int) now()->format('G');
                $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
                $firstName = \Illuminate\Support\Str::before(trim($user['name'] ?? ''), ' ') ?: 'there';
                $hubName = $centerName ?? ($application->business_name ?? null);
            @endphp

            <div class="lc-top">
                <span class="lc-center"><span class="lc-center-dot"></span>{{ $hubName ?? 'BoomBuy Logistics' }}</span>
                <form class="lc-search" action="{{ route('logistics.parcels') }}" method="GET" role="search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" name="q" placeholder="Find order # or buyer" aria-label="Find a parcel by order number or buyer">
                </form>
                <a href="{{ route('logistics.notifications') }}" class="lc-bell" aria-label="Notifications{{ $unreadNotifications > 0 ? ' (' . $unreadNotifications . ' unread)' : '' }}">
                    <i class="bi bi-bell"></i>
                    @if($unreadNotifications > 0)<span class="lc-bell-dot"></span>@endif
                </a>
            </div>

            @if(session('success'))
                <div class="pd-alert is-ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="pd-alert is-error"><i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}</div>
            @endif

            @if(!empty($unassigned))
                <div class="pd-alert is-error"><i class="bi bi-hourglass-split"></i> You are not assigned to a Sorting Center yet, so there are no parcels or riders to show. The BoomBuy admin will assign you.</div>
            @endif

            <div class="lc-row">
                <section class="lc-hero">
                    <span class="lc-hero-orb is-a" aria-hidden="true"></span>
                    <span class="lc-hero-orb is-b" aria-hidden="true"></span>
                    <div class="lc-hero-text">
                        <p class="lc-hero-eyebrow">{{ now()->format('l, F j') }}</p>
                        <h1>{{ $greeting }}, {{ $firstName }}</h1>
                        <p>
                            @if($waiting > 0)
                                <b>{{ $waiting }} {{ $waiting === 1 ? 'parcel is' : 'parcels are' }}</b> waiting on you.
                                {{ $nextTask ? 'Start with ' . mb_strtolower($nextTask['label']) . '.' : '' }}
                            @else
                                Nothing is waiting on you right now. Nice work.
                            @endif
                        </p>
                    </div>
                    <div class="lc-hero-actions">
                        @if($nextTask)
                            <a href="{{ $nextTask['url'] }}" class="lc-btn lc-btn-white"><i class="bi {{ str_replace('-fill', '', $nextTask['icon']) }}"></i> {{ $nextTask['label'] }}</a>
                        @endif
                        <a href="{{ route('logistics.parcels') }}" class="lc-btn lc-btn-glass">All parcels</a>
                        <a href="{{ route('logistics.riders') }}" class="lc-btn lc-btn-glass">Riders</a>
                    </div>
                </section>

                <section class="lc-card lc-progress" aria-labelledby="progress-title">
                    <svg class="lc-ring" role="img" aria-label="{{ $deliveredToday }} of {{ $dayTotal }} parcels delivered today" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#eee8ff" stroke-width="14"/>
                        @if($donePct > 0)
                            <circle cx="60" cy="60" r="50" fill="none" stroke="#6d4ad1" stroke-width="14" stroke-linecap="round" stroke-dasharray="{{ round($ring * $donePct / 100, 1) }} {{ round($ring, 1) }}" transform="rotate(-90 60 60)"/>
                        @endif
                        <text x="60" y="60" text-anchor="middle" class="lc-ring-num">{{ $donePct }}%</text>
                        <text x="60" y="79" text-anchor="middle" class="lc-ring-label">delivered</text>
                    </svg>
                    <div class="lc-progress-text">
                        <h2 id="progress-title">Today so far</h2>
                        <span><b class="is-green">{{ $deliveredToday }}</b> delivered</span>
                        <span><b class="is-blue">{{ $onTheRoad }}</b> on the road</span>
                        <span><b class="is-red">{{ $failedToday }}</b> failed</span>
                    </div>
                </section>
            </div>

            <section class="lc-card lc-flow" aria-label="Parcel flow">
                @foreach($steps as $i => $step)
                    <a href="{{ $step['url'] }}" class="lc-step {{ $step['tone'] }} {{ $busiest === $i ? 'is-hot' : '' }}">
                        @if($busiest === $i)<span class="lc-step-tag">Most waiting</span>@endif
                        <span class="lc-step-label">{{ $step['label'] }}</span>
                        <strong>{{ $step['count'] }}</strong>
                        <span class="lc-step-note">{{ $step['note'] }}</span>
                        @unless($loop->last)<span class="lc-step-arrow" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>@endunless
                    </a>
                @endforeach
            </section>

            <div class="lc-row lc-row-top">
                <div class="lc-col-main">
                    <section class="lc-card lc-list-card" aria-labelledby="needs-title">
                        <div class="lc-card-head">
                            <h2 id="needs-title">Needs a rider</h2>
                            <a href="{{ route('logistics.parcels') }}#awaiting-assignment" class="lc-link">All parcels →</a>
                        </div>

                        @if($needsRider->isEmpty())
                            <div class="lc-empty"><i class="bi bi-inbox"></i>Every parcel at the Sorting Center has a rider.</div>
                        @else
                            @foreach($needsRider as $parcel)
                                @php
                                    $since = \Carbon\Carbon::parse($parcel->sorting_center_received_at ?? $parcel->updated_at);
                                    $old = $since->lt(now()->subHours(2));
                                @endphp
                                <div class="lc-item">
                                    <div class="lc-item-main">
                                        <span class="lc-code">#{{ $parcel->id }} · {{ $parcel->shipping_name }}</span>
                                        <span class="lc-item-sub">{{ \Illuminate\Support\Str::limit($parcel->shipping_address, 60) }}</span>
                                    </div>
                                    <span class="lc-wait {{ $old ? 'is-old' : '' }}"><i class="bi bi-clock"></i> {{ $since->diffForHumans(null, true) }}</span>
                                    <a href="{{ route('logistics.parcels') }}#awaiting-assignment" class="lc-btn lc-btn-primary lc-btn-sm">Assign rider</a>
                                </div>
                            @endforeach
                        @endif
                    </section>

                    <section class="lc-card" aria-labelledby="todo-title">
                        <div class="lc-card-head">
                            <h2 id="todo-title">Needs your attention</h2>
                        </div>
                        <div class="lc-todo">
                            @foreach($todo as $item)
                                <a href="{{ $item['url'] }}" class="lc-todo-item {{ $item['count'] > 0 ? 'is-hot' : 'is-done' }}">
                                    <span class="lc-todo-icon"><i class="bi {{ str_replace('-fill', '', $item['icon']) }}"></i></span>
                                    <span class="lc-todo-text"><strong>{{ $item['label'] }}</strong><span>{{ \Illuminate\Support\Str::before($item['hint'], ' —') }}</span></span>
                                    <span class="lc-todo-count">{{ $item['count'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </section>

                    <section class="lc-card lc-list-card" aria-labelledby="latest-title">
                        <div class="lc-card-head">
                            <h2 id="latest-title">Latest parcel updates</h2>
                            <span class="lc-muted">{{ $returnedThisWeek }} returned to sellers this week</span>
                        </div>
                        @if($recentParcels->isEmpty())
                            <div class="lc-empty"><i class="bi bi-box-seam"></i>No parcels yet.</div>
                        @else
                            @foreach($recentParcels as $parcel)
                                <div class="lc-item">
                                    <div class="lc-item-main">
                                        <span class="lc-code">#{{ $parcel->id }} · {{ $parcel->shipping_name }}</span>
                                        <span class="lc-item-sub">{{ \Illuminate\Support\Str::limit($parcel->shipping_address, 48) }} · {{ \Carbon\Carbon::parse($parcel->updated_at)->diffForHumans() }}</span>
                                    </div>
                                    <x-status-pill :status="$parcel->status" />
                                </div>
                            @endforeach
                        @endif
                    </section>
                </div>

                <aside class="lc-col-side">
                    <section class="lc-card" aria-labelledby="riders-title">
                        <div class="lc-card-head">
                            <h2 id="riders-title">Riders</h2>
                            <span class="lc-ok">{{ $activeRiders }} active</span>
                        </div>
                        @if($ridersOnDuty->isEmpty())
                            <div class="lc-empty"><i class="bi bi-bicycle"></i>No active riders yet.</div>
                        @else
                            <div class="lc-riders">
                                @foreach($ridersOnDuty as $rider)
                                    <div class="lc-rider">
                                        <span class="lc-avatar">{{ mb_strtoupper(mb_substr($rider['name'], 0, 1)) }}<span class="lc-avatar-dot {{ $rider['load'] > 0 ? 'is-road' : 'is-free' }}"></span></span>
                                        <span class="lc-rider-main">
                                            <strong>{{ $rider['name'] }}</strong>
                                            <span>{{ $rider['load'] > 0 ? 'On the road' : 'Available' }} · {{ $rider['area'] ?: 'No area set' }}</span>
                                        </span>
                                        <span class="lc-rider-load"><b>{{ $rider['load'] }}</b>{{ $rider['load'] === 1 ? 'parcel' : 'parcels' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if($ridersWithoutArea > 0)
                            <p class="lc-muted lc-note"><i class="bi bi-info-circle"></i> {{ $ridersWithoutArea }} {{ $ridersWithoutArea === 1 ? 'rider has' : 'riders have' }} no delivery area yet.</p>
                        @endif
                        <a href="{{ route('logistics.riders') }}" class="lc-link lc-note">Manage riders →</a>
                    </section>

                    <section class="lc-cod" aria-labelledby="cod-title">
                        <div class="lc-cod-head"><h2 id="cod-title">Cash on delivery</h2><span>today</span></div>
                        <div class="lc-cod-grid">
                            <div><span>Collected today</span><b>₱{{ number_format($codCollectedToday, 2) }}</b></div>
                            <div><span>With riders</span><b class="is-lilac">₱{{ number_format((float) $codToReceive->amount, 2) }}</b></div>
                            <div><span>Parcels</span><b>{{ (int) $codToReceive->parcels }}</b></div>
                        </div>
                        <a href="{{ route('logistics.parcels') }}#cod" class="lc-btn lc-btn-lilac">Receive cash from riders</a>
                    </section>
                </aside>
            </div>

        </div>
    </main>

    @include('partials.pwa-register')
</body>
</html>
