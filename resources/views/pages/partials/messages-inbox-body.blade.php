    <div class="header">
        <h1>Messages</h1>
        <p>Conversations with BoomBuy users and support.</p>
    </div>

    @if(session('error'))
        <div class="error-box">{{ session('error') }}</div>
    @endif

    @if($me['role'] !== 'admin')

        @php
            $admin = \App\Models\User::where('email', 'admin@boombuy.com')->first();
        @endphp

        @if($admin)
            <div style="margin-bottom:15px;">
                <a href="{{ route('messages.thread', $admin->id) }}" class="send-btn" style="display:inline-block; padding:11px 18px; border-radius:10px;">
                    <i class="bi bi-chat-dots-fill"></i> Message BoomBuy Support
                </a>
            </div>
        @endif

    @endif

    @if($conversations->count() > 0)

        <div class="conversation-list">

            @foreach($conversations as $conversation)

                <a href="{{ route('messages.thread', $conversation['partner_id']) }}" class="conversation-item {{ $conversation['unread_count'] > 0 ? 'unread' : '' }}">

                    <div class="avatar">
                        {{ strtoupper(substr($conversation['partner_name'], 0, 1)) }}
                    </div>

                    <div style="flex:1; min-width:0;">
                        <div>
                            <span class="conversation-name">{{ $conversation['partner_name'] }}</span>
                            <span class="conversation-role">{{ $conversation['partner_role'] }}</span>
                        </div>
                        <div class="conversation-preview">{{ $conversation['last_message'] }}</div>
                    </div>

                    <div class="conversation-meta">
                        <div class="conversation-time">{{ $conversation['last_message_at']->diffForHumans() }}</div>
                        @if($conversation['unread_count'] > 0)
                            <span class="unread-badge">{{ $conversation['unread_count'] }}</span>
                        @endif
                    </div>

                </a>

            @endforeach

        </div>

    @else

        <div class="empty">No conversations yet.</div>

    @endif
