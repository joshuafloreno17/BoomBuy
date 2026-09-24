@php
    $latestAnnouncement = \App\Models\PlatformAnnouncement::where('is_active', true)
        ->orderByDesc('created_at')
        ->first();
@endphp

@if($latestAnnouncement)

    <div style="
        background: #fff4ee;
        border: 1px solid #f7d9c5;
        color: #7c3a12;
        padding: 13px 18px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-size: 13px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    ">
        <span>📣</span>
        <span>
            <strong>{{ $latestAnnouncement->title }}</strong>
            — {{ $latestAnnouncement->message }}
        </span>
    </div>

@endif
