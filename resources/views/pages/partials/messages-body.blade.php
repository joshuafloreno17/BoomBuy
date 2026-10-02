{{--
    Messages: conversation list (left) + open conversation (right).
    On phones only one shows: the list at /messages, the chat at /messages/{id}.
--}}
@php
    $listQuery = array_filter([
        'q' => $search !== '' ? $search : null,
        'filter' => $filter !== 'all' ? $filter : null,
        'role' => $roleFilter,
    ]);
    $hasSupportChat = $support && $conversations->contains('partner_id', $support->id);

    // A chat opened from a shop/order before anyone wrote yet still shows in the list.
    $draftPartner = $partner && $search === '' && $filter === 'all' && !$roleFilter
        && !$conversations->contains('partner_id', $partner['id']);
@endphp

<div class="chat-container {{ $partner ? 'has-thread' : 'no-thread' }}">

    @if(session('error'))
        <div class="chat-alert"><i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}</div>
    @endif

    <div class="chat-shell">

        {{-- ================= CONVERSATION LIST ================= --}}
        <aside class="chat-list">

            <div class="chat-list-head">
                <h1>Messages</h1>

                @if($me['role'] === 'admin')
                    <button type="button" class="chat-new-btn" id="newChatBtn">
                        <i class="bi bi-pencil-square"></i> New
                    </button>
                @endif
            </div>

            <form
                method="GET"
                action="{{ $partner ? route('messages.thread', $partner['id']) : route('messages.index') }}"
                class="chat-search"
                data-live-search
                data-live-target="#chatFilters, #chatConversations"
            >
                @if($filter !== 'all')
                    <input type="hidden" name="filter" value="{{ $filter }}">
                @endif
                @if($roleFilter)
                    <input type="hidden" name="role" value="{{ $roleFilter }}">
                @endif

                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ $search }}" placeholder="Search conversations" aria-label="Search conversations" autocomplete="off">
            </form>

            <div class="chat-filters" id="chatFilters">
                @php $base = $partner ? fn ($q) => route('messages.thread', [$partner['id']] + $q) : fn ($q) => route('messages.index', $q); @endphp

                <a href="{{ $base(array_filter(['q' => $search ?: null, 'role' => $roleFilter])) }}" class="chat-chip {{ $filter === 'all' ? 'active' : '' }}">
                    All <span>{{ $totalConversations }}</span>
                </a>
                <a href="{{ $base(array_filter(['q' => $search ?: null, 'role' => $roleFilter, 'filter' => 'unread'])) }}" class="chat-chip {{ $filter === 'unread' ? 'active' : '' }} {{ $unreadConversations > 0 ? 'has-work' : '' }}">
                    Unread <span>{{ $unreadConversations }}</span>
                </a>

                @foreach($roleFilters as $roleKey => $roleLabel)
                    <a
                        href="{{ $base(array_filter(['q' => $search ?: null, 'filter' => $filter !== 'all' ? $filter : null, 'role' => $roleFilter === $roleKey ? null : $roleKey])) }}"
                        class="chat-chip subtle {{ $roleFilter === $roleKey ? 'active' : '' }}"
                    >{{ $roleLabel }}</a>
                @endforeach
            </div>

            <div class="chat-conversations" id="chatConversations">

                @if($draftPartner)
                    <a href="{{ route('messages.thread', $partner['id']) }}" class="conv open">
                        <span class="conv-avatar {{ $partner['role'] === 'admin' ? 'support' : '' }}">
                            @if($partner['photo'])
                                <img src="{{ $partner['photo'] }}" alt="">
                            @elseif($partner['role'] === 'admin')
                                <i class="bi bi-headset"></i>
                            @else
                                {{ $partner['initial'] }}
                            @endif
                        </span>
                        <span class="conv-main">
                            <span class="conv-top">
                                <span class="conv-name">{{ $partner['name'] }}</span>
                                <span class="conv-role role-{{ $partner['role'] }}">{{ $partner['role_label'] }}</span>
                            </span>
                            <span class="conv-preview">New conversation</span>
                        </span>
                    </a>
                @endif

                @if($support && !$hasSupportChat && $search === '' && $filter === 'all' && !($partner && $partner['id'] === (int) $support->id))
                    <a href="{{ route('messages.thread', $support->id) }}" class="conv conv-support">
                        <span class="conv-avatar support"><i class="bi bi-headset"></i></span>
                        <span class="conv-main">
                            <span class="conv-top"><span class="conv-name">BoomBuy Support</span></span>
                            <span class="conv-preview">Questions about an order or your account? Message us.</span>
                        </span>
                    </a>
                @endif

                @forelse($conversations as $conversation)
                    @php
                        $isOpen = $partner && $partner['id'] === $conversation['partner_id'];
                        $when = $conversation['last_message_at'];
                        $whenLabel = $when->isToday() ? $when->format('g:i A') : ($when->isYesterday() ? 'Yesterday' : ($when->year === now()->year ? $when->format('M j') : $when->format('M j, Y')));
                    @endphp

                    <a
                        href="{{ route('messages.thread', [$conversation['partner_id']] + $listQuery) }}"
                        class="conv {{ $isOpen ? 'open' : '' }} {{ $conversation['unread_count'] > 0 ? 'unread' : '' }}"
                    >
                        <span class="conv-avatar {{ $conversation['role'] === 'admin' ? 'support' : '' }}">
                            @if($conversation['photo'])
                                <img src="{{ $conversation['photo'] }}" alt="" loading="lazy">
                            @elseif($conversation['role'] === 'admin')
                                <i class="bi bi-headset"></i>
                            @else
                                {{ $conversation['initial'] }}
                            @endif
                        </span>

                        <span class="conv-main">
                            <span class="conv-top">
                                <span class="conv-name">{{ $conversation['name'] }}</span>
                                @if($conversation['role_label'])
                                    <span class="conv-role role-{{ $conversation['role'] }}">{{ $conversation['role_label'] }}</span>
                                @endif
                                <span class="conv-time">{{ $whenLabel }}</span>
                            </span>
                            <span class="conv-bottom">
                                <span class="conv-preview">
                                    @if($conversation['last_is_mine'])<span class="conv-you">You:</span>@endif
                                    {{ \Illuminate\Support\Str::limit($conversation['last_message'], 70) }}
                                </span>
                                @if($conversation['unread_count'] > 0)
                                    <span class="conv-badge">{{ $conversation['unread_count'] > 99 ? '99+' : $conversation['unread_count'] }}</span>
                                @endif
                            </span>
                        </span>
                    </a>
                @empty
                    @if(!$draftPartner)
                    <div class="conv-empty">
                        <i class="bi bi-chat-square-text"></i>
                        @if($search !== '' || $filter !== 'all' || $roleFilter)
                            <p>No conversations match.</p>
                        @else
                            <p>No conversations yet.</p>
                        @endif
                    </div>
                    @endif
                @endforelse

            </div>

        </aside>

        {{-- ================= OPEN CONVERSATION ================= --}}
        <section class="chat-thread">

            @if($partner)

                <header class="thread-head">
                    <a href="{{ route('messages.index', $listQuery) }}" class="thread-back" aria-label="Back to conversations">
                        <i class="bi bi-arrow-left"></i>
                    </a>

                    <span class="conv-avatar {{ $partner['role'] === 'admin' ? 'support' : '' }}">
                        @if($partner['photo'])
                            <img src="{{ $partner['photo'] }}" alt="">
                        @elseif($partner['role'] === 'admin')
                            <i class="bi bi-headset"></i>
                        @else
                            {{ $partner['initial'] }}
                        @endif
                    </span>

                    <div class="thread-who">
                        <div class="thread-name">{{ $partner['name'] }}</div>
                        <div class="thread-sub">
                            {{ $partner['role_label'] }}@if($partner['subtitle']) · {{ $partner['subtitle'] }}@endif
                        </div>
                    </div>

                    @if($partner['shop_url'])
                        <a href="{{ $partner['shop_url'] }}" class="thread-shop" target="_blank"><i class="bi bi-shop"></i> <span>View shop</span></a>
                    @endif
                </header>

                @php
                    $lastMineId = optional($thread->where('sender_id', $me['id'])->last())->id ?? 0;
                    $lastDay = null;
                @endphp

                <div
                    class="thread-box"
                    id="threadBox"
                    data-last-id="{{ $thread->max('id') ?? 0 }}"
                    data-last-day="{{ optional($thread->last())->created_at?->toDateString() }}"
                    data-last-mine-id="{{ $lastMineId }}"
                    data-seen-up-to="{{ $seenUpTo }}"
                    data-poll-url="{{ route('messages.thread', $partner['id']) }}"
                >
                    @forelse($thread as $message)
                        @php $day = $message->created_at->toDateString(); @endphp

                        @if($day !== $lastDay)
                            <div class="day-sep"><span>{{ \App\Http\Controllers\MessageController::dayLabel($message->created_at) }}</span></div>
                            @php $lastDay = $day; @endphp
                        @endif

                        <div class="bubble {{ (int) $message->sender_id === (int) $me['id'] ? 'mine' : 'theirs' }}" data-id="{{ $message->id }}">
                            <span class="bubble-text">{{ $message->message }}</span>
                            <span class="bubble-time">{{ $message->created_at->format('g:i A') }}</span>
                        </div>
                    @empty
                        <div class="thread-empty" id="emptyThread">
                            <i class="bi bi-chat-heart"></i>
                            <p>No messages yet — say hello to {{ $partner['name'] }}.</p>
                        </div>
                    @endforelse

                    <div class="seen-marker" id="seenMarker" @if(!$lastMineId || $seenUpTo < $lastMineId) hidden @endif>
                        <i class="bi bi-check2-all"></i> Seen
                    </div>
                </div>

                <form method="POST" action="{{ route('messages.store', $partner['id']) }}" class="thread-compose" id="sendForm">
                    @csrf
                    <textarea name="message" rows="1" maxlength="2000" placeholder="Write a message…" aria-label="Message" required autofocus></textarea>
                    <button type="submit" class="thread-send" aria-label="Send"><i class="bi bi-send-fill"></i></button>
                </form>
                <div class="compose-hint">Enter to send · Shift + Enter for a new line</div>

            @else

                <div class="thread-placeholder">
                    <i class="bi bi-chat-dots"></i>
                    <h2>Your messages</h2>
                    <p>Pick a conversation on the left{{ $me['role'] === 'admin' ? ', or start a new one' : '' }}.</p>
                    @if($me['role'] === 'admin')
                        <button type="button" class="chat-new-btn" data-open-new-chat><i class="bi bi-pencil-square"></i> New message</button>
                    @endif
                </div>

            @endif

        </section>

    </div>
</div>

{{-- ================= NEW MESSAGE (admin) ================= --}}
@if($me['role'] === 'admin')
    <dialog class="new-chat-dialog" id="newChatDialog">
        <div class="new-chat-head">
            <h3>New message</h3>
            <button type="button" class="new-chat-close" id="newChatClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="chat-search in-dialog">
            <i class="bi bi-search"></i>
            <input type="search" id="newChatSearch" placeholder="Search a shop, name or email" autocomplete="off" aria-label="Search people">
        </div>
        <div class="new-chat-results" id="newChatResults" data-url="{{ route('messages.recipients') }}">
            <p class="new-chat-hint">Loading…</p>
        </div>
    </dialog>
@endif

<script>
(function () {
    /* ---------- New message (admin) ---------- */
    var dialog = document.getElementById('newChatDialog');

    if (dialog) {
        var input = document.getElementById('newChatSearch');
        var results = document.getElementById('newChatResults');
        var timer = null;
        var controller = null;

        function openDialog() {
            dialog.showModal();
            input.value = '';
            input.focus();
            search(''); // suggestions right away, before anything is typed
        }

        document.addEventListener('click', function (e) {
            if (e.target.closest('#newChatBtn, [data-open-new-chat]')) openDialog();
        });
        document.getElementById('newChatClose').addEventListener('click', function () { dialog.close(); });

        function show(people) {
            results.innerHTML = '';

            if (!people.length) {
                var none = document.createElement('p');
                none.className = 'new-chat-hint';
                none.textContent = 'Nobody matches.';
                results.appendChild(none);
                return;
            }

            // Built with textContent — names and emails are user input.
            people.forEach(function (p) {
                var row = document.createElement('a');
                row.className = 'conv';
                row.href = p.url;

                var avatar = document.createElement('span');
                avatar.className = 'conv-avatar';
                if (p.photo) {
                    var img = document.createElement('img');
                    img.src = p.photo;
                    img.alt = '';
                    avatar.appendChild(img);
                } else {
                    avatar.textContent = p.initial;
                }

                var main = document.createElement('span');
                main.className = 'conv-main';

                var top = document.createElement('span');
                top.className = 'conv-top';
                var name = document.createElement('span');
                name.className = 'conv-name';
                name.textContent = p.name;
                var role = document.createElement('span');
                role.className = 'conv-role role-' + p.role;
                role.textContent = p.role_label;
                top.appendChild(name);
                top.appendChild(role);

                var sub = document.createElement('span');
                sub.className = 'conv-preview';
                sub.textContent = (p.subtitle ? p.subtitle + ' · ' : '') + p.email;

                main.appendChild(top);
                main.appendChild(sub);
                row.appendChild(avatar);
                row.appendChild(main);
                results.appendChild(row);
            });
        }

        function search(q) {
            if (controller) controller.abort();
            controller = new AbortController();

            fetch(results.dataset.url + '?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: controller.signal
            })
                .then(function (r) { return r.json(); })
                .then(function (data) { show(data.people || []); })
                .catch(function () {});
        }

        // Every keystroke (even one letter, or clearing the box) updates the list.
        input.addEventListener('input', function () {
            clearTimeout(timer);
            var q = input.value.trim();
            timer = setTimeout(function () { search(q); }, 200);
        });
    }

    /* ---------- Open conversation ---------- */
    var box = document.getElementById('threadBox');
    if (!box) return;

    var form = document.getElementById('sendForm');
    var textarea = form.querySelector('textarea');
    var button = form.querySelector('button[type="submit"]');
    var token = form.querySelector('input[name="_token"]').value;
    var seenMarker = document.getElementById('seenMarker');

    var lastId = parseInt(box.dataset.lastId, 10) || 0;
    var lastDay = box.dataset.lastDay || '';
    var lastMineId = parseInt(box.dataset.lastMineId, 10) || 0;
    var seenUpTo = parseInt(box.dataset.seenUpTo, 10) || 0;
    var shown = {};

    box.querySelectorAll('.bubble[data-id]').forEach(function (b) { shown[b.dataset.id] = true; });
    box.scrollTop = box.scrollHeight;

    function updateSeen() {
        seenMarker.hidden = !(lastMineId && seenUpTo >= lastMineId);
    }

    // textContent only — message text is user input.
    function addBubble(m) {
        if (shown[m.id]) return;
        shown[m.id] = true;

        var empty = document.getElementById('emptyThread');
        if (empty) empty.remove();

        var nearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 120;

        if (m.day !== lastDay) {
            var sep = document.createElement('div');
            sep.className = 'day-sep';
            var label = document.createElement('span');
            label.textContent = m.day_label;
            sep.appendChild(label);
            box.insertBefore(sep, seenMarker);
            lastDay = m.day;
        }

        var bubble = document.createElement('div');
        bubble.className = 'bubble ' + (m.mine ? 'mine' : 'theirs');
        bubble.dataset.id = m.id;

        var text = document.createElement('span');
        text.className = 'bubble-text';
        text.textContent = m.text;

        var time = document.createElement('span');
        time.className = 'bubble-time';
        time.textContent = m.time;

        bubble.appendChild(text);
        bubble.appendChild(time);
        box.insertBefore(bubble, seenMarker);

        lastId = Math.max(lastId, m.id);
        if (m.mine) lastMineId = Math.max(lastMineId, m.id);
        updateSeen();

        if (nearBottom || m.mine) box.scrollTop = box.scrollHeight;
    }

    function poll() {
        if (document.hidden) return;

        fetch(box.dataset.pollUrl + '?after=' + lastId, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) return;
                data.messages.forEach(addBubble);
                seenUpTo = Math.max(seenUpTo, data.seen_up_to || 0);
                updateSeen();
            })
            .catch(function () {});
    }

    setInterval(poll, 5000);
    document.addEventListener('visibilitychange', poll);

    // Grow with the text, up to a few lines.
    function autosize() {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 140) + 'px';
    }
    textarea.addEventListener('input', autosize);

    // Enter sends, Shift+Enter adds a line.
    textarea.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    form.addEventListener('submit', function (event) {
        var text = textarea.value.trim();
        event.preventDefault();
        if (!text || button.disabled) return;

        button.disabled = true;

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ message: text })
        })
            .then(function (r) {
                if (!r.ok) throw new Error('send failed');
                return r.json();
            })
            .then(function (data) {
                addBubble(data.message);
                textarea.value = '';
                autosize();
                textarea.focus();
            })
            .catch(function () { HTMLFormElement.prototype.submit.call(form); })
            .finally(function () { button.disabled = false; });
    });
})();
</script>
