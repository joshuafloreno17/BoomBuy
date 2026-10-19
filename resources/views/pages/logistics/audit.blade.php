<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Audit Log — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/logistics-ops.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="audit" :user="$user" />

    <main class="main-content">
        <div class="container ops">

            <div class="page-header">
                <div>
                    <h1>Audit Log</h1>
                    <p>Status overrides, rider approvals and suspensions, rate changes and remittance confirmations. Entries can't be edited or deleted.</p>
                </div>
            </div>

            <section class="ops-card">
                @if($entries->isEmpty())
                    <p class="muted">Nothing logged yet.</p>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Date &amp; time</th><th>User</th><th>Action</th><th>Record</th><th>Change</th></tr></thead>
                            <tbody>
                                @foreach($entries as $entry)
                                    @php $details = json_decode($entry->details ?? '[]', true) ?: []; @endphp
                                    <tr data-audit="{{ $entry->id }}">
                                        <td>{{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('M d, Y g:i:s A') }}</td>
                                        <td>{{ $entry->user_name ?? '—' }}</td>
                                        <td>{{ $entry->action }}</td>
                                        <td>{{ $entry->subject_type }} {{ $entry->subject_type === 'order' ? \App\Support\Waybill::number((int) $entry->subject_id) : '#' . $entry->subject_id }}</td>
                                        <td>
                                            @if(isset($details['from']) || isset($details['to']))
                                                {{ is_array($details['from'] ?? null) ? json_encode($details['from']) : ($details['from'] ?? '—') }} → {{ is_array($details['to'] ?? null) ? json_encode($details['to']) : ($details['to'] ?? '—') }}
                                            @endif
                                            @if(!empty($details['reason']))
                                                <br><small>Reason: {{ $details['reason'] }}</small>
                                            @endif
                                            @if(isset($details['amount']))
                                                <br><small>₱{{ number_format((float) $details['amount'], 2) }} of ₱{{ number_format((float) ($details['expected'] ?? 0), 2) }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

        </div>
    </main>

</body>
</html>
