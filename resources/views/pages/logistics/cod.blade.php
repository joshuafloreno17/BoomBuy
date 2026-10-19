<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'COD & Reports — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/logistics-ops.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="cod" :user="$user" />

    <main class="main-content">
        <div class="container ops">

            <div class="page-header">
                <div>
                    <h1>COD &amp; Reports</h1>
                    <p>Cash collected by your riders against what they remitted, and report exports.</p>
                </div>
            </div>

            @if (session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('logistics.cod') }}" class="ops-filter">
                <label>Day <input type="date" name="date" value="{{ $date }}"></label>
                <button type="submit" class="btn-main">Show</button>
            </form>

            <section class="ops-card">
                <h2>Reconciliation · {{ \Illuminate\Support\Carbon::parse($date)->format('M d, Y') }}</h2>
                @if($rows->isEmpty())
                    <p class="muted">No COD collected on this day.</p>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Rider</th><th>Orders</th><th>Collected</th><th>Remitted</th><th>Difference</th></tr></thead>
                            <tbody>
                                @foreach($rows as $row)
                                    <tr data-reco="{{ $row->rider_id }}" class="{{ $row->short ? 'is-short' : '' }}">
                                        <td>{{ $row->rider }}</td>
                                        <td>{{ collect($row->orders)->map(fn ($id) => \App\Support\Waybill::number($id))->implode(', ') }}</td>
                                        <td>₱{{ number_format($row->collected, 2) }}</td>
                                        <td>₱{{ number_format($row->remitted, 2) }}</td>
                                        <td><strong>{{ $row->difference < 0 ? '−' : '' }}₱{{ number_format(abs($row->difference), 2) }}</strong>{{ $row->short ? ' · shortfall' : '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="ops-card">
                <h2>Remittances to confirm <small>{{ $pending->count() }}</small></h2>
                @forelse($pending as $remittance)
                    <form method="POST" action="{{ route('logistics.cod.confirm', $remittance->id) }}" class="ops-inline">
                        @csrf
                        <span>{{ \Illuminate\Support\Facades\DB::table('users')->where('id', $remittance->rider_id)->value('name') }} · ₱{{ number_format($remittance->amount, 2) }} of ₱{{ number_format($remittance->expected, 2) }}{{ $remittance->amount < $remittance->expected ? ' (short ₱' . number_format($remittance->expected - $remittance->amount, 2) . ')' : '' }}</span>
                        <button type="submit">Confirm received</button>
                    </form>
                @empty
                    <p class="muted">Nothing to confirm.</p>
                @endforelse
            </section>

            <section class="ops-card">
                <h2>Export</h2>
                <form method="GET" action="{{ route('logistics.reports.export') }}" class="ops-inline">
                    <select name="type"><option value="deliveries">Deliveries</option><option value="cod">COD</option></select>
                    <label>From <input type="date" name="from" value="{{ $from }}"></label>
                    <label>To <input type="date" name="to" value="{{ $to }}"></label>
                    <select name="format"><option value="csv">CSV</option><option value="xlsx">Excel (XLSX)</option></select>
                    <button type="submit">Download</button>
                </form>
            </section>

        </div>
    </main>

</body>
</html>
