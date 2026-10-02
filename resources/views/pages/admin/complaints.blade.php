<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Complaints & Disputes — BoomBuy Admin</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-complaints.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="complaints" />

    <main class="main">

    <div class="container">

        <div class="header">
            <h1>Complaints &amp; Disputes</h1>
            <p>Review complaint details and supporting evidence, then coordinate with the buyer, seller, and/or rider involved.</p>
        </div>

        @if(session('success'))
            <div class="success-box">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="error-box">{{ session('error') }}</div>
        @endif

        @php
            $underReviewCount = $complaints->where('status', 'Under Review')->count();
            $resolvedCount = $complaints->where('status', 'Resolved')->count();
            $dismissedCount = $complaints->where('status', 'Dismissed')->count();
        @endphp

        <div class="stats">

            <div class="stat-card">
                <span>Pending</span>
                <strong>{{ $pendingCount }}</strong>
            </div>

            <div class="stat-card">
                <span>Under Review</span>
                <strong>{{ $underReviewCount }}</strong>
            </div>

            <div class="stat-card">
                <span>Resolved</span>
                <strong>{{ $resolvedCount }}</strong>
            </div>

            <div class="stat-card">
                <span>Dismissed</span>
                <strong>{{ $dismissedCount }}</strong>
            </div>

        </div>

        @forelse($complaints as $complaint)

            <div class="complaint-card">

                <div class="complaint-top">

                    <div>
                        <div class="complaint-subject">{{ $complaint->subject }}</div>
                        <div class="complaint-meta">
                            Filed by {{ $complaint->complainant->name ?? 'Unknown User' }}
                            ({{ ucfirst($complaint->complainant_role) }})
                            — {{ $complaint->created_at->format('M d, Y • h:i A') }}
                            @if($complaint->order_id)
                                — Order #{{ $complaint->order_id }}
                            @endif
                        </div>
                    </div>

                    <span class="status-badge status-{{ \Illuminate\Support\Str::slug($complaint->status) }}">
                        {{ $complaint->status }}
                    </span>

                </div>

                <div class="complaint-desc">
                    {{ $complaint->description }}
                </div>

                @if($complaint->evidence)
                    <a href="{{ route('complaints.evidence', $complaint->id) }}" target="_blank" class="evidence-link">
                        <i class="bi bi-paperclip"></i> View Evidence
                    </a>
                @endif

                <form method="POST" action="{{ route('admin.complaints.update', $complaint->id) }}" class="resolve-form">
                    @csrf

                    <div>
                        <label for="status-{{ $complaint->id }}">Status</label>
                        <select id="status-{{ $complaint->id }}" name="status">
                            <option value="Pending" {{ $complaint->status === 'Pending' ? 'selected' : '' }}>Pending</option>
                            <option value="Under Review" {{ $complaint->status === 'Under Review' ? 'selected' : '' }}>Under Review</option>
                            <option value="Resolved" {{ $complaint->status === 'Resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="Dismissed" {{ $complaint->status === 'Dismissed' ? 'selected' : '' }}>Dismissed</option>
                        </select>
                    </div>

                    <div>
                        <label for="notes-{{ $complaint->id }}">Admin Notes / Resolution</label>
                        <input type="text" id="notes-{{ $complaint->id }}" name="admin_notes" value="{{ $complaint->admin_notes }}" placeholder="e.g. Coordinated with seller, refund issued.">
                    </div>

                    <button type="submit" class="resolve-btn">Update</button>

                </form>

            </div>

        @empty

            <div class="empty">
                <h3>No Complaints Filed</h3>
                <p>Buyer, seller, and rider complaints will appear here.</p>
            </div>

        @endforelse

    </div>

    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>
