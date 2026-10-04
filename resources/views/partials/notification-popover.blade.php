{{--
    Bell button with a drop-down of the latest notifications (portal dashboards).
    Needs: $notes (latest few, newest first), $unread (count), $allUrl (the full list).
    Each item opens through notifications.open, which marks it read and goes to
    the order / return / page it is about.
--}}
@php
    $popIcons = [
        'order' => 'bi-cart',
        'order_status' => 'bi-box-seam',
        'return_refund' => 'bi-arrow-counterclockwise',
        'seller' => 'bi-shop',
        'rider' => 'bi-bicycle',
        'delivery' => 'bi-truck',
        'delivery_status' => 'bi-truck',
        'parcel' => 'bi-box',
    ];
@endphp

<div class="pd-pop" data-pop>
    <button type="button" class="pd-icon-btn" data-pop-toggle aria-haspopup="true" aria-expanded="false" aria-controls="pd-pop-panel"
        aria-label="Notifications{{ $unread ? ', ' . $unread . ' unread' : '' }}">
        <i class="bi bi-bell"></i>
        @if($unread > 0)<em>{{ $unread > 99 ? '99+' : $unread }}</em>@endif
    </button>

    <div class="pd-pop-panel" id="pd-pop-panel" role="dialog" aria-label="Notifications" hidden>
        <div class="pd-pop-head">
            <strong>Notifications</strong>
            @if($unread > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit"><i class="bi bi-check2-all"></i> Mark all as read</button>
                </form>
            @endif
        </div>

        @if($notes->isEmpty())
            <div class="pd-pop-empty"><i class="bi bi-bell-slash"></i>You're all caught up.</div>
        @else
            <ul class="pd-pop-list">
                @foreach($notes as $note)
                    <li>
                        <a href="{{ route('notifications.open', $note->id) }}" class="pd-pop-item {{ $note->read_at ? '' : 'is-unread' }}">
                            <span class="pd-pop-icon"><i class="bi {{ $popIcons[$note->type] ?? 'bi-bell' }}"></i></span>
                            <span class="pd-pop-text">
                                <strong>{{ $note->title }}</strong>
                                <span>{{ \Illuminate\Support\Str::limit($note->message, 90) }}</span>
                                <small>{{ \Carbon\Carbon::parse($note->created_at)->diffForHumans() }}</small>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <a href="{{ $allUrl }}" class="pd-pop-all">See all notifications</a>
    </div>
</div>

@once
<script>
    // Bell drop-downs. With a mouse: open on hover (short delays so passing
    // over doesn't flash it and moving into the list doesn't close it).
    // Everywhere: click/tap toggles; outside click or Esc closes.
    document.querySelectorAll('[data-pop]').forEach(function (pop) {
        var toggle = pop.querySelector('[data-pop-toggle]');
        var panel = pop.querySelector('.pd-pop-panel');
        var canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        var timer = null;

        function setOpen(open) {
            clearTimeout(timer);
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        if (canHover) {
            pop.addEventListener('mouseenter', function () {
                clearTimeout(timer);
                timer = setTimeout(function () { setOpen(true); }, 120);
            });

            pop.addEventListener('mouseleave', function () {
                clearTimeout(timer);
                timer = setTimeout(function () { setOpen(false); }, 300);
            });
        }

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            // Already open from hovering: a click keeps it open instead of closing it.
            setOpen(canHover && !panel.hidden ? true : panel.hidden);
        });

        document.addEventListener('click', function (e) {
            if (!panel.hidden && !pop.contains(e.target)) setOpen(false);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.hidden) {
                setOpen(false);
                toggle.focus();
            }
        });
    });
</script>
@endonce
