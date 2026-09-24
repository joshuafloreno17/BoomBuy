<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $partner->name }} — Messages — BoomBuy</title>

    @include('partials.pwa-head')

    <link rel="stylesheet" href="{{ asset('css/pages/messages.css') }}">
</head>

<body>

<div class="container">

    <a href="{{ route('messages.index') }}" class="back-link">
        ← Back to Messages
    </a>

    <div class="thread-header">
        <div class="avatar">
            {{ strtoupper(substr($partner->name, 0, 1)) }}
        </div>
        <div>
            <div class="conversation-name">{{ $partner->name }}</div>
            <div class="conversation-role">{{ $partner->role }}</div>
        </div>
    </div>

    <div class="thread-box" id="threadBox">

        @forelse($thread as $message)

            <div class="bubble {{ $message->sender_id === $me['id'] ? 'bubble-mine' : 'bubble-theirs' }}">
                {{ $message->message }}
                <span class="bubble-time">{{ $message->created_at->format('M d, h:i A') }}</span>
            </div>

        @empty

            <div class="empty-thread">No messages yet. Say hello!</div>

        @endforelse

    </div>

    <form method="POST" action="{{ route('messages.store', $partner->id) }}" class="send-form">
        @csrf
        <input type="text" name="message" placeholder="Type a message..." required autofocus>
        <button type="submit" class="send-btn">Send</button>
    </form>

</div>

<script>
    var box = document.getElementById('threadBox');
    box.scrollTop = box.scrollHeight;
</script>

    @include('partials.pwa-register')

</body>
</html>
