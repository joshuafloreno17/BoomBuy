@php
    $isRiders = $who === 'riders';
    $noun = $isRiders ? 'rider' : 'buyer';
    $nouns = $isRiders ? 'riders' : 'buyers';
    $route = $isRiders ? 'admin.compliance.riders' : 'admin.compliance.buyers';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => ($isRiders ? 'Rider' : 'Buyer') . ' Compliance — BoomBuy Admin'])

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-accounts.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/views/admin-compliance.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="compliance" />

    <main class="main">

        <div class="page-header">
            <small>Admin Panel</small>
            <h1>Compliance</h1>
            <p>
                @if($isRiders)
                    Riders who leave a delivery untouched for more than {{ \App\Support\PeopleCompliance::STUCK_DAYS }} days, fail {{ round(\App\Support\PeopleCompliance::FAIL_RATE * 100) }}% or more of their attempts, or have open complaints.
                @else
                    Buyers whose Cash on Delivery is paused (or one strike away) for cancelling or refusing parcels, or who have open complaints. COD pauses on their own — warn or suspend when it keeps happening.
                @endif
            </p>
        </div>

        @include('pages.admin.partials.compliance-tabs', ['active' => $who])

        @if(session('success'))
            <div class="success-box"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="error-box"><i class="bi bi-x-circle-fill"></i> {{ session('error') }}</div>
        @endif

        <div class="stats">
            <div class="stat-card">
                <span>{{ $isRiders ? 'Approved Riders' : 'Buyers With Orders' }}</span>
                <strong>{{ $total }}</strong>
            </div>
            <div class="stat-card">
                <span>Need Attention</span>
                <strong style="{{ $withIssues > 0 ? 'color:#e8420f;' : '' }}">{{ $withIssues }}</strong>
            </div>
            <div class="stat-card">
                <span>Suspended</span>
                <strong>{{ $people->where('account_status', 'Suspended')->count() }}</strong>
            </div>
        </div>

        <div class="account-toolbar">
            <div class="view-chips" id="liveChips">
                <a href="{{ route($route, array_filter(['view' => 'issues', 'q' => $search])) }}" class="view-chip {{ $view === 'issues' ? 'active' : '' }} {{ $withIssues > 0 ? 'has-work' : '' }}">
                    <i class="bi bi-exclamation-triangle-fill"></i> Needs attention <span class="n">{{ $withIssues }}</span>
                </a>
                <a href="{{ route($route, array_filter(['view' => 'all', 'q' => $search])) }}" class="view-chip {{ $view === 'all' ? 'active' : '' }}">
                    All {{ $nouns }} <span class="n">{{ $total }}</span>
                </a>
            </div>

            <form method="GET" action="{{ route($route) }}" style="display:contents;" data-live-search data-live-target="#liveChips, #liveResults">
                <input type="hidden" name="view" value="{{ $view }}">

                <div class="account-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search name or email{{ $isRiders ? ' or area' : '' }}" aria-label="Search {{ $nouns }}">
                </div>

                <button type="submit" class="toolbar-btn"><i class="bi bi-search"></i> Search</button>
            </form>
        </div>

        <div id="liveResults">

            @php
                $withProblems = $people->where('has_issues', true);
                $clean = $people->where('has_issues', false);
            @endphp

            {{-- ========== NEED ATTENTION ========== --}}
            @foreach($withProblems as $person)

                <div class="issue-card">

                    <div class="seller-head">
                        <div>
                            <div class="shop-name">{{ $person['name'] }}</div>
                            <div class="owner">{{ $person['email'] }}{{ !empty($person['area']) ? ' · ' . $person['area'] : '' }}</div>
                            <div class="meta-line">
                                @if($isRiders)
                                    {{ $person['delivered'] }} delivered · {{ $person['failed'] }} failed attempt(s) · carrying {{ $person['carrying'] }}
                                @else
                                    {{ $person['orders'] }} order(s) · {{ $person['cancelled'] }} cancelled · {{ $person['refused'] }} refused
                                @endif
                                · <span class="status-badge status-{{ strtolower($person['account_status']) }}">{{ $person['account_status'] }}</span>
                            </div>
                        </div>

                        @include('pages.admin.partials.compliance-tools', ['seller' => $person])
                    </div>

                    <div class="issue-block">
                        <div class="issue-label"><i class="bi bi-exclamation-triangle-fill"></i> Why it's here</div>
                        @foreach($person['issues'] as $issue)
                            <div class="issue-row"><div><strong>{{ $issue }}</strong></div></div>
                        @endforeach
                    </div>

                    @if($isRiders && $person['stuck']->isNotEmpty())
                        <div class="issue-block">
                            <div class="issue-label"><i class="bi bi-hourglass-split"></i> Stuck deliveries</div>
                            @foreach($person['stuck'] as $order)
                                <div class="issue-row">
                                    <div>
                                        <strong>Order #{{ $order->id }} · {{ $order->status }}</strong>
                                        <span class="why">Last updated {{ \Illuminate\Support\Carbon::parse($order->updated_at)->diffForHumans() }} — {{ $order->shipping_address }}</span>
                                    </div>
                                    <a href="{{ route('admin.order.details', $order->id) }}" class="act"><i class="bi bi-eye"></i> View</a>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($person['complaints']->isNotEmpty())
                        <div class="issue-block">
                            <div class="issue-label"><i class="bi bi-megaphone-fill"></i> Open complaints</div>
                            @foreach($person['complaints'] as $complaint)
                                <div class="issue-row">
                                    <div>
                                        <strong>{{ $complaint->subject }}</strong>
                                        <span class="why">{{ $complaint->status }} · filed {{ \Illuminate\Support\Carbon::parse($complaint->created_at)->diffForHumans() }}</span>
                                    </div>
                                    <a href="{{ route('admin.complaints') }}" class="act"><i class="bi bi-eye"></i> Review</a>
                                </div>
                            @endforeach
                        </div>
                    @endif

                </div>

            @endforeach

            {{-- ========== EVERYONE ELSE ========== --}}
            @if($clean->isNotEmpty())

                @if($withProblems->isNotEmpty())
                    <div class="clean-heading">
                        <h2>No issues</h2>
                        <span>{{ $clean->count() }} {{ $noun }}(s)</span>
                    </div>
                @endif

                <div class="table-card">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ strtoupper($noun) }}</th>
                                    @if($isRiders)
                                        <th>AREA</th>
                                        <th>DELIVERED</th>
                                        <th>FAILED</th>
                                    @else
                                        <th>ORDERS</th>
                                        <th>CANCELLED</th>
                                        <th>REFUSED</th>
                                    @endif
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clean as $person)
                                    <tr>
                                        <td>
                                            <div class="user-name">{{ $person['name'] }}</div>
                                            <div class="user-id">{{ $person['email'] }}</div>
                                        </td>
                                        @if($isRiders)
                                            <td>{{ $person['area'] ?: '—' }}</td>
                                            <td>{{ $person['delivered'] }}</td>
                                            <td>{{ $person['failed'] }}</td>
                                        @else
                                            <td>{{ $person['orders'] }}</td>
                                            <td>{{ $person['cancelled'] }}</td>
                                            <td>{{ $person['refused'] }}</td>
                                        @endif
                                        <td><span class="status-badge status-{{ strtolower($person['account_status']) }}">{{ $person['account_status'] }}</span></td>
                                        <td>@include('pages.admin.partials.compliance-tools', ['seller' => $person])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            @endif

            @if($people->isEmpty())
                <div class="table-card">
                    <div class="empty">
                        <div class="empty-icon"><i class="bi bi-shield-check" style="font-size:40px; color:#bbe3c9;"></i></div>
                        <h3>
                            @if($search !== '')
                                No {{ $nouns }} match "{{ $search }}"
                            @elseif($view === 'issues')
                                All clear
                            @else
                                No {{ $nouns }} yet
                            @endif
                        </h3>
                        <p>
                            @if($view === 'issues' && $search === '')
                                Nobody here needs a look right now.
                            @else
                                Try another search, or switch to All {{ $nouns }}.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

        </div>

        <div class="footer">
            © {{ date('Y') }} BoomBuy · {{ $isRiders ? 'Rider' : 'Buyer' }} Compliance
        </div>

    </main>

</div>

{{-- One Warn dialog; the button fills in who and where. --}}
<dialog class="warn-dialog" id="warnDialog">
    <form method="POST" id="warnForm" action="">
        @csrf
        <h3>Warn <span id="warnName">{{ $noun }}</span></h3>
        <p>They'll get this as a notification. Leave it empty to send the standard warning.</p>
        <textarea name="warning_message" maxlength="1000" placeholder="{{ $isRiders ? 'e.g. Order #66 has been Out for Delivery for 3 days — please deliver it or report why.' : 'e.g. You have refused 3 COD parcels this month. Further refusals may suspend your account.' }}"></textarea>
        <div class="dialog-actions">
            <button type="button" class="act" id="warnCancel">Cancel</button>
            <button type="submit" class="act send"><i class="bi bi-send"></i> Send Warning</button>
        </div>
    </form>
</dialog>

<script>
    (function () {
        var dialog = document.getElementById('warnDialog');
        var form = document.getElementById('warnForm');

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-warn-url]');
            if (!button) return;

            form.action = button.getAttribute('data-warn-url');
            document.getElementById('warnName').textContent = button.getAttribute('data-warn-name');
            form.querySelector('textarea').value = '';
            dialog.showModal();
            form.querySelector('textarea').focus();
        });

        document.getElementById('warnCancel').addEventListener('click', function () {
            dialog.close();
        });
    })();
</script>

    @include('partials.live-search')
    @include('partials.pwa-register')

</body>
</html>
