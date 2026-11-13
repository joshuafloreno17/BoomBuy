<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Audit Log — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/logistics-ops.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="audit" :user="$user" />

    @php
        // An icon and colour per kind of action, read from its name.
        $kind = function (string $action) {
            $a = strtolower($action);
            return match (true) {
                str_contains($a, 'suspend') || str_contains($a, 'lost') || str_contains($a, 'damag') || str_contains($a, 'reject') => ['bi-exclamation-octagon', 'is-failed'],
                str_contains($a, 'remit') || str_contains($a, 'cod') => ['bi-cash-coin', 'is-delivered'],
                str_contains($a, 'rate') || str_contains($a, 'zone') => ['bi-cash-stack', 'is-in_transit'],
                str_contains($a, 'rider') || str_contains($a, 'approv') || str_contains($a, 'reactivat') => ['bi-person-check', 'is-delivered'],
                str_contains($a, 'override') || str_contains($a, 'status') => ['bi-pencil-square', 'is-pending'],
                default => ['bi-journal-text', 'is-other'],
            };
        };
    @endphp

    <main class="main-content">
        <div class="container ops">

            <div class="page-header">
                <h1>Audit Log</h1>
                <p>Status overrides, rider approvals and suspensions, rate changes and remittance confirmations. Entries can't be edited or deleted.</p>
                <p class="center-badge"><i class="bi bi-shield-lock"></i> Read-only record <span>· {{ $entries->count() }} entries</span></p>
            </div>

            <section class="ops-card">
                <div class="ops-card-head">
                    <div>
                        <h2><i class="bi bi-journal-text"></i> Activity <small>{{ $entries->count() }}</small></h2>
                        <p class="muted">Newest first: who did what, to which record, and what changed.</p>
                    </div>
                </div>

                @if($entries->isEmpty())
                    <div class="ops-empty"><i class="bi bi-journal-check"></i><p>Nothing logged yet.</p></div>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Date &amp; time</th><th>User</th><th>Action</th><th>Record</th><th>Change</th></tr></thead>
                            <tbody>
                                @foreach($entries as $entry)
                                    @php
                                        $details = json_decode($entry->details ?? '[]', true) ?: [];
                                        [$icon, $tone] = $kind((string) $entry->action);
                                        $at = \Illuminate\Support\Carbon::parse($entry->created_at);
                                    @endphp
                                    <tr data-audit="{{ $entry->id }}">
                                        <td>
                                            <span class="strong">{{ $at->format('M d, Y g:i:s A') }}</span>
                                            <small class="muted ops-sub">{{ $at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            <span class="ops-who"><span class="ops-avatar is-sm">{{ mb_strtoupper(mb_substr($entry->user_name ?? '?', 0, 1)) }}</span> {{ $entry->user_name ?? '—' }}</span>
                                        </td>
                                        <td><span class="ops-pill {{ $tone }}"><i class="bi {{ $icon }}"></i> {{ $entry->action }}</span></td>
                                        <td>
                                            @if($entry->subject_type === 'order')
                                                <span class="wb-chip is-light">{{ \App\Support\Waybill::number((int) $entry->subject_id) }}</span>
                                            @else
                                                <span class="muted">{{ $entry->subject_type }}</span> #{{ $entry->subject_id }}
                                            @endif
                                        </td>
                                        <td class="ops-change">
                                            @if(isset($details['from']) || isset($details['to']))
                                                <span class="ops-to">{{ is_array($details['from'] ?? null) ? json_encode($details['from']) : ($details['from'] ?? '—') }} → {{ is_array($details['to'] ?? null) ? json_encode($details['to']) : ($details['to'] ?? '—') }}</span>
                                            @endif
                                            @if(!empty($details['reason']))
                                                <small class="muted ops-sub">Reason: {{ $details['reason'] }}</small>
                                            @endif
                                            @if(isset($details['amount']))
                                                <small class="muted ops-sub">₱{{ number_format((float) $details['amount'], 2) }} of ₱{{ number_format((float) ($details['expected'] ?? 0), 2) }}</small>
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
