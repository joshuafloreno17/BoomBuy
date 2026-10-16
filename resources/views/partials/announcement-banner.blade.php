@if($latestAnnouncement)

    <div style="
        background: #f4f0fd;
        border: 1px solid #dcd4f0;
        color: #3e2b74;
        padding: 13px 18px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-size: 13px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    ">
        <span><i class="bi bi-megaphone-fill"></i></span>
        <span>
            <strong>{{ $latestAnnouncement->title }}</strong>
            — {{ $latestAnnouncement->message }}
        </span>
    </div>

@endif
