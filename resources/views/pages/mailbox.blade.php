<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Test Mailbox - BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <style>
        body { margin: 0; background: #f8f6fe; font-family: Arial, sans-serif; color: #221a3a; }
        .mb-wrap { max-width: 1100px; margin: 32px auto; padding: 0 16px; }
        .mb-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .mb-head h1 { font-size: 22px; margin: 0; display: flex; align-items: center; gap: 8px; }
        .mb-note { color: #645a80; font-size: 13px; margin: 4px 0 0; }
        .mb-search { display: flex; gap: 8px; }
        .mb-search input { padding: 9px 12px; border: 1px solid #d9d1f5; border-radius: 10px; min-width: 240px; font-size: 14px; }
        .mb-search button, .mb-search a { padding: 9px 14px; border-radius: 10px; border: 0; background: #7c3aed; color: #fff; font-size: 14px; text-decoration: none; cursor: pointer; }
        .mb-search a { background: #ede9fe; color: #5b21b6; }
        .mb-grid { display: grid; grid-template-columns: minmax(260px, 360px) 1fr; gap: 16px; }
        .mb-list, .mb-view { background: #fff; border: 1px solid #e9e4fa; border-radius: 16px; overflow: hidden; }
        .mb-list a { display: block; padding: 12px 14px; border-bottom: 1px solid #f1edfc; text-decoration: none; color: inherit; }
        .mb-list a:hover, .mb-list a.on { background: #f5f3ff; }
        .mb-list .to { font-size: 12px; color: #7c3aed; word-break: break-all; }
        .mb-list .subj { font-weight: 700; font-size: 14px; margin: 2px 0; }
        .mb-list .when { font-size: 12px; color: #8a80a6; }
        .mb-empty { padding: 28px 16px; text-align: center; color: #8a80a6; font-size: 14px; }
        .mb-view header { padding: 14px 16px; border-bottom: 1px solid #f1edfc; }
        .mb-view header .subj { font-size: 17px; font-weight: 700; }
        .mb-view header .meta { font-size: 13px; color: #645a80; margin-top: 4px; }
        .mb-view iframe { width: 100%; height: 640px; border: 0; display: block; }
        .bb-viewer-body .mb-view { border: 0; border-radius: 0; }
        .bb-viewer-body .mb-view header { padding-right: 60px; }
        @media (max-width: 760px) {
            .mb-grid { grid-template-columns: 1fr; }
            .mb-search { width: 100%; }
            .mb-search input { min-width: 0; flex: 1; }
        }
    </style>
</head>
<body>
<div class="mb-wrap">
    <div class="mb-head">
        <div>
            <h1><i class="bi bi-envelope-paper"></i> Test Mailbox</h1>
            <p class="mb-note">Emails to the test accounts (@example.com) land here instead of being sent: OTP codes, activation links and notices. Newest first.</p>
        </div>
        <form class="mb-search" method="GET" action="{{ route('mailbox') }}">
            <input type="text" name="to" value="{{ $to }}" placeholder="Filter by email, e.g. juanmiguel">
            <button type="submit">Search</button>
            @if ($to !== '')
                <a href="{{ route('mailbox') }}">All</a>
            @endif
        </form>
    </div>

    <div class="mb-grid">
        <div class="mb-list">
            @forelse ($mails as $mail)
                <a href="{{ route('mailbox.show', $mail->id) }}" class="{{ $open && $open->id === $mail->id ? 'on' : '' }}" data-viewer data-viewer-kind="page" data-viewer-fragment=".mb-view">
                    <div class="to">{{ $mail->to }}</div>
                    <div class="subj">{{ $mail->subject }}</div>
                    <div class="when">{{ \Illuminate\Support\Carbon::parse($mail->created_at)->format('M j, Y g:i:s A') }}</div>
                </a>
            @empty
                <div class="mb-empty">No emails yet.</div>
            @endforelse
        </div>

        <div class="mb-view">
            @if ($open)
                <header>
                    <div class="subj">{{ $open->subject }}</div>
                    <div class="meta">To {{ $open->to }} · {{ \Illuminate\Support\Carbon::parse($open->created_at)->format('M j, Y g:i:s A') }}</div>
                </header>
                {{-- Sandboxed: the email's own HTML can't run scripts here; links open in a new tab. --}}
                <iframe sandbox="allow-popups allow-popups-to-escape-sandbox" srcdoc="{{ '<base target="_blank">' . ($open->html ?? nl2br(e($open->text ?? ''))) }}"></iframe>
            @else
                <div class="mb-empty">Pick an email on the left to read it.</div>
            @endif
        </div>
    </div>
</div>

@include('partials.viewer-modal')
</body>
</html>
