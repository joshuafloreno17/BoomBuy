<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'COD & Reports — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/logistics-ops.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="cod" :user="$user" />

    @php
        $collected = (float) $rows->sum('collected');
        $remitted = (float) $rows->sum('remitted');
        $difference = $remitted - $collected;
        $shortRiders = $rows->where('short', true)->count();
        $riderName = fn ($id) => \Illuminate\Support\Facades\DB::table('users')->where('id', $id)->value('name');
        $peso = fn ($n) => '₱' . number_format((float) $n, 2);
    @endphp

    <main class="main-content">
        <div class="container ops">

            <div class="page-header">
                <h1>COD &amp; Reports</h1>
                <p>Cash on Delivery your riders collected against what they handed in, remittances to confirm, and report downloads.</p>
            </div>

            @if (session('success'))
                <div class="success-box"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="error-box"><i class="bi bi-x-circle-fill"></i> {{ session('error') }}</div>
            @endif

            {{-- The day's totals --}}
            <div class="ops-counts">
                <div class="ops-count">
                    <span class="ops-count-ic"><i class="bi bi-cash-stack"></i></span>
                    <span class="ops-count-text"><span>Collected</span><strong>{{ $peso($collected) }}</strong></span>
                </div>
                <div class="ops-count is-delivered">
                    <span class="ops-count-ic"><i class="bi bi-wallet2"></i></span>
                    <span class="ops-count-text"><span>Remitted</span><strong>{{ $peso($remitted) }}</strong></span>
                </div>
                <div class="ops-count {{ $difference < 0 ? 'is-failed' : 'is-in_transit' }}">
                    <span class="ops-count-ic"><i class="bi {{ $difference < 0 ? 'bi-exclamation-triangle' : 'bi-check2-circle' }}"></i></span>
                    <span class="ops-count-text"><span>{{ $difference < 0 ? 'Short' : 'Difference' }}</span><strong>{{ $difference < 0 ? '−' : '' }}{{ $peso(abs($difference)) }}</strong></span>
                </div>
                <div class="ops-count is-pending">
                    <span class="ops-count-ic"><i class="bi bi-hourglass-split"></i></span>
                    <span class="ops-count-text"><span>To confirm</span><strong>{{ $pending->count() }}</strong></span>
                </div>
            </div>

            {{-- Reconciliation --}}
            <section class="ops-card">
                <div class="ops-card-head">
                    <div>
                        <h2><i class="bi bi-clipboard-check"></i> Reconciliation <small>{{ \Illuminate\Support\Carbon::parse($date)->format('M d, Y') }}</small></h2>
                        <p class="muted">
                            Per rider: the COD they collected that day and what they remitted.
                            @if($shortRiders) <strong class="ops-text-bad">{{ $shortRiders }} rider(s) short.</strong> @endif
                        </p>
                    </div>
                    <form method="GET" action="{{ route('logistics.cod') }}" class="ops-inline ops-head-form">
                        <label class="sr-only" for="codDay">Day</label>
                        <input type="date" id="codDay" name="date" value="{{ $date }}">
                        <button type="submit" class="btn-main"><i class="bi bi-calendar-check"></i> Show</button>
                    </form>
                </div>

                @if($rows->isEmpty())
                    <div class="ops-empty"><i class="bi bi-cash-coin"></i><p>No COD collected on this day.</p></div>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Rider</th><th>Orders</th><th>Collected</th><th>Remitted</th><th>Difference</th></tr></thead>
                            <tbody>
                                @foreach($rows as $row)
                                    <tr data-reco="{{ $row->rider_id }}" class="{{ $row->short ? 'is-short' : '' }}">
                                        <td class="strong">
                                            <span class="ops-who"><span class="ops-avatar is-sm">{{ mb_strtoupper(mb_substr($row->rider, 0, 1)) }}</span> {{ $row->rider }}</span>
                                        </td>
                                        <td>
                                            <div class="ops-chips">
                                                @foreach($row->orders as $id)
                                                    <span class="wb-chip is-light">{{ \App\Support\Waybill::number($id) }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>{{ $peso($row->collected) }}</td>
                                        <td>{{ $peso($row->remitted) }}</td>
                                        <td>
                                            <strong>{{ $row->difference < 0 ? '−' : '' }}₱{{ number_format(abs($row->difference), 2) }}</strong>
                                            @if($row->short)
                                                <span class="ops-pill is-failed">· shortfall</span>
                                            @else
                                                <span class="ops-pill is-delivered"><i class="bi bi-check2"></i> OK</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- Remittances --}}
            <section class="ops-card">
                <div class="ops-card-head">
                    <div>
                        <h2><i class="bi bi-wallet2"></i> Remittances to confirm <small>{{ $pending->count() }}</small></h2>
                        <p class="muted">Cash a rider says they handed in. Count it, then confirm.</p>
                    </div>
                </div>

                @forelse($pending as $remittance)
                    @php $short = $remittance->amount < $remittance->expected; @endphp
                    <form method="POST" action="{{ route('logistics.cod.confirm', $remittance->id) }}" class="ops-remit {{ $short ? 'is-short-card' : '' }}">
                        @csrf
                        <span class="ops-avatar">{{ mb_strtoupper(mb_substr((string) $riderName($remittance->rider_id), 0, 1)) }}</span>
                        <div class="ops-remit-main">
                            <strong>{{ $riderName($remittance->rider_id) }}</strong>
                            <span>
                                {{ $peso($remittance->amount) }} of {{ $peso($remittance->expected) }}
                                @if($short)
                                    <span class="ops-pill is-failed">short {{ $peso($remittance->expected - $remittance->amount) }}</span>
                                @endif
                            </span>
                        </div>
                        <button type="submit"><i class="bi bi-check2-circle"></i> Confirm received</button>
                    </form>
                @empty
                    <div class="ops-empty"><i class="bi bi-check2-all"></i><p>Nothing to confirm.</p></div>
                @endforelse
            </section>

            {{-- Export --}}
            <section class="ops-card">
                <div class="ops-card-head">
                    <div>
                        <h2><i class="bi bi-download"></i> Export</h2>
                        <p class="muted">Download deliveries or COD for a date range, as CSV or Excel.</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('logistics.reports.export') }}" class="ops-export">
                    <label>Report
                        <select name="type"><option value="deliveries">Deliveries</option><option value="cod">COD</option></select>
                    </label>
                    <label>From <input type="date" name="from" value="{{ $from }}"></label>
                    <label>To <input type="date" name="to" value="{{ $to }}"></label>
                    <label>Format
                        <select name="format"><option value="csv">CSV</option><option value="xlsx">Excel (XLSX)</option></select>
                    </label>
                    <button type="submit"><i class="bi bi-download"></i> Download</button>
                </form>
            </section>

        </div>
    </main>

</body>
</html>
