@props(['notification', 'icon' => 'bi-bell-fill', 'readRoute'])

@php
    $createdAt = \Carbon\Carbon::parse($notification->created_at);
    $typeLabel = $notification->type
        ? str_replace('_', ' ', $notification->type)
        : '';
@endphp

{{-- Opens the notification popup; unread ones are marked read in the background. --}}
<button
    type="button"
    class="notification-card {{ $notification->read_at ? 'read' : 'unread' }}"
    data-notification-open
    data-title="{{ $notification->title }}"
    data-message="{{ $notification->message }}"
    data-icon="{{ $icon }}"
    data-type="{{ $typeLabel }}"
    data-date="{{ $createdAt->format('M j, Y · g:i A') }}"
    data-ago="{{ $createdAt->diffForHumans() }}"
    @if(!$notification->read_at)
        data-read-url="{{ route($readRoute, $notification->id) }}"
    @endif
>

    <div class="notification-top">

        <div class="notification-icon">
            <i class="bi {{ $icon }}"></i>
        </div>

        <div class="notification-content">

            <div class="notification-title">
                {{ $notification->title }}
            </div>

            <div class="notification-message">
                {{ $notification->message }}
            </div>

            <div class="notification-time">
                {{ $createdAt->diffForHumans() }}
            </div>

        </div>

    </div>


    <div class="notification-bottom">

        @if(!$notification->read_at)

            <span class="unread-dot"></span>

        @endif

        @if($typeLabel)

            <span class="notification-type">
                {{ $typeLabel }}
            </span>

        @endif

        @if($notification->read_at)

            <span class="read-label">
                <i class="bi bi-check2"></i> Read
            </span>

        @endif

    </div>

</button>

@once
    @include('partials.notification-modal')
@endonce
