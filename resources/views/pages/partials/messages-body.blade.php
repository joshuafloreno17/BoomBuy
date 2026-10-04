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
                    // "Seen" follows what a person typed, not auto-replies or order updates.
                    $lastMineId = optional($thread->where('sender_id', $me['id'])->filter(fn ($m) => $m->isText())->last())->id ?? 0;
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

                        <div class="bubble {{ (int) $message->sender_id === (int) $me['id'] ? 'mine' : 'theirs' }} {{ $message->isText() ? '' : 'is-auto' }}" data-id="{{ $message->id }}">
                            @if($message->product)
                                @php $card = app(\App\Http\Controllers\MessageController::class)->productCard($message->product); @endphp
                                <a href="{{ $card['url'] }}" class="chat-product">
                                    <span class="chat-product-img">
                                        @if($card['image'])<img src="{{ $card['image'] }}" alt="" onerror="this.remove()">@endif
                                        <i class="bi bi-bag"></i>
                                    </span>
                                    <span class="chat-product-text"><strong>{{ $card['name'] }}</strong><b>{{ $card['price'] }}</b></span>
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            @endif
                            <span class="bubble-text">{{ $message->message }}</span>
                            @if($message->order_id && !empty($orderCards[$message->order_id]))
                                @php $oc = $orderCards[$message->order_id]; @endphp
                                <a href="{{ $oc['url'] }}" class="chat-order">
                                    <span><strong>Order #{{ $oc['id'] }}</strong><em class="chat-order-status">Now: {{ $oc['status'] }}</em></span>
                                    <b>View order <i class="bi bi-chevron-right"></i></b>
                                </a>
                            @endif
                            {{-- Sent by the shop automatically: a quiet note beside the time. --}}
                            <span class="bubble-time">@if($message->kind === \App\Models\Message::AUTO_REPLY)Auto-reply · @elseif($message->kind === \App\Models\Message::ORDER_UPDATE)Order update · @endif{{ $message->created_at->format('g:i A') }}</span>
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

                @if(!empty($askingAbout))
                    {{-- "Chat" from a product page: the next message carries this product. --}}
                    <div class="ask-about" id="askAbout" data-product-id="{{ $askingAbout['id'] }}">
                        <span class="chat-product-img">
                            @if($askingAbout['image'])<img src="{{ $askingAbout['image'] }}" alt="" onerror="this.remove()">@endif
                            <i class="bi bi-bag"></i>
                        </span>
                        <span class="chat-product-text"><small>Asking about</small><strong>{{ $askingAbout['name'] }}</strong><b>{{ $askingAbout['price'] }}</b></span>
                        <button type="button" class="ask-about-remove" id="askAboutRemove" aria-label="Don't attach this product"><i class="bi bi-x-lg"></i></button>
                    </div>
                @endif
                @if(!empty($suggestions))
                    {{-- Buyer → shop: items from their cart (or last order) they may want to ask about. --}}
                    <div class="chat-suggest" id="chatSuggest">
                        <span class="chat-suggest-label"><i class="bi bi-cart3"></i> Want to ask about {{ count($suggestions) > 1 ? 'one of these' : 'this' }}?</span>
                        <div class="chat-suggest-list">
                            @foreach($suggestions as $s)
                                <button type="button" class="chat-suggest-item" data-suggest="{{ json_encode($s) }}">
                                    <span class="chat-product-img">
                                        @if($s['image'])<img src="{{ $s['image'] }}" alt="" onerror="this.remove()">@endif
                                        <i class="bi bi-bag"></i>
                                    </span>
                                    <span class="chat-product-text"><strong>{{ $s['name'] }}</strong><b>{{ $s['price'] }}</b></span>
                                </button>
                            @endforeach
                        </div>
                        <button type="button" class="chat-suggest-close" id="chatSuggestClose" aria-label="Hide suggestions"><i class="bi bi-x-lg"></i></button>
                    </div>
                @endif
                <form method="POST" action="{{ route('messages.store', $partner['id']) }}" class="thread-compose" id="sendForm">
                    @csrf
                    @if(!empty($askingAbout))<input type="hidden" name="product_id" value="{{ $askingAbout['id'] }}">@endif
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

    // A product card for a message about a product (built from data, never HTML).
    function productCard(p) {
        var a = document.createElement('a');
        a.className = 'chat-product';
        a.href = p.url;

        var media = document.createElement('span');
        media.className = 'chat-product-img';
        if (p.image) {
            var img = document.createElement('img');
            img.src = p.image;
            img.alt = '';
            img.onerror = function () { img.remove(); };
            media.appendChild(img);
        }
        var icon = document.createElement('i');
        icon.className = 'bi bi-bag';
        media.appendChild(icon);

        var info = document.createElement('span');
        info.className = 'chat-product-text';
        var name = document.createElement('strong');
        name.textContent = p.name;
        var price = document.createElement('b');
        price.textContent = p.price;
        info.appendChild(name);
        info.appendChild(price);

        var chevron = document.createElement('i');
        chevron.className = 'bi bi-chevron-right';

        a.appendChild(media);
        a.appendChild(info);
        a.appendChild(chevron);
        return a;
    }

    // "Asking about" chip: goes out with the next message, or can be removed.
    var askAbout = document.getElementById('askAbout');
    function dropAskAbout() {
        if (askAbout) askAbout.remove();
        askAbout = null;
        var hidden = form.querySelector('input[name="product_id"]');
        if (hidden) hidden.remove();
    }
    function bindRemove() {
        var btn = document.getElementById('askAboutRemove');
        if (btn) btn.addEventListener('click', function () { dropAskAbout(); textarea.focus(); });
    }
    bindRemove();

    // Picking a suggested product puts it in the "Asking about" chip (replacing any).
    function setAskAbout(p) {
        dropAskAbout();

        askAbout = document.createElement('div');
        askAbout.className = 'ask-about';
        askAbout.id = 'askAbout';
        askAbout.dataset.productId = p.id;

        var card = productCard(p);
        var media = card.querySelector('.chat-product-img');
        var info = card.querySelector('.chat-product-text');
        var small = document.createElement('small');
        small.textContent = 'Asking about';
        info.insertBefore(small, info.firstChild);

        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'ask-about-remove';
        remove.id = 'askAboutRemove';
        remove.setAttribute('aria-label', "Don't attach this product");
        remove.innerHTML = '<i class="bi bi-x-lg"></i>';

        askAbout.appendChild(media);
        askAbout.appendChild(info);
        askAbout.appendChild(remove);
        form.parentNode.insertBefore(askAbout, form);
        bindRemove();

        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'product_id';
        hidden.value = p.id;
        form.appendChild(hidden);
    }

    var suggest = document.getElementById('chatSuggest');
    if (suggest) {
        suggest.addEventListener('click', function (e) {
            if (e.target.closest('#chatSuggestClose')) { suggest.remove(); suggest = null; return; }
            var item = e.target.closest('[data-suggest]');
            if (!item) return;
            setAskAbout(JSON.parse(item.dataset.suggest));
            suggest.querySelectorAll('[data-suggest]').forEach(function (b) { b.classList.toggle('is-picked', b === item); });
            textarea.focus();
        });
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
        var isText = !m.kind || m.kind === 'text';
        bubble.className = 'bubble ' + (m.mine ? 'mine' : 'theirs') + (isText ? '' : ' is-auto');
        bubble.dataset.id = m.id;

        if (m.product) bubble.appendChild(productCard(m.product));

        var text = document.createElement('span');
        text.className = 'bubble-text';
        text.textContent = m.text;

        var time = document.createElement('span');
        time.className = 'bubble-time';
        time.textContent = (m.kind === 'auto_reply' ? 'Auto-reply · ' : m.kind === 'order_update' ? 'Order update · ' : '') + m.time;

        bubble.appendChild(text);

        if (m.order) {
            var order = document.createElement('a');
            order.className = 'chat-order';
            order.href = m.order.url;
            var left = document.createElement('span');
            var num = document.createElement('strong');
            num.textContent = 'Order #' + m.order.id;
            var status = document.createElement('em');
            status.className = 'chat-order-status';
            status.textContent = 'Now: ' + m.order.status;
            left.appendChild(num);
            left.appendChild(status);
            var view = document.createElement('b');
            view.innerHTML = 'View order <i class="bi bi-chevron-right"></i>';
            order.appendChild(left);
            order.appendChild(view);
            bubble.appendChild(order);
        }

        bubble.appendChild(time);
        box.insertBefore(bubble, seenMarker);

        lastId = Math.max(lastId, m.id);
        if (m.mine && isText) lastMineId = Math.max(lastMineId, m.id);
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

        fetch(form.getAttribute('action'), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(askAbout
                ? { message: text, product_id: parseInt(askAbout.dataset.productId, 10) }
                : { message: text })
        })
            .then(function (r) {
                if (!r.ok) throw new Error('send failed');
                return r.json();
            })
            .then(function (data) {
                addBubble(data.message);
                if (askAbout && suggest) { suggest.remove(); suggest = null; }
                dropAskAbout();
                // The shop's auto-reply, after a short "typing" pause.
                if (data.auto_reply) setTimeout(function () { addBubble(data.auto_reply); }, 900);
                textarea.value = '';
                autosize();
                textarea.focus();
            })
            .catch(function () { HTMLFormElement.prototype.submit.call(form); })
            .finally(function () { button.disabled = false; });
    });
})();
</script>
