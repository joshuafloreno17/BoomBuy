@props(['notification', 'icon' => 'bi-bell-fill', 'readRoute'])

<form
    action="{{ !$notification->read_at
        ? route($readRoute, $notification->id)
        : '#'
    }}"
    method="{{ !$notification->read_at
        ? 'POST'
        : 'GET'
    }}"
    class="notification-form"
>

    @if(!$notification->read_at)
        @csrf
    @endif

    <button
        type="{{ !$notification->read_at
            ? 'submit'
            : 'button'
        }}"
        class="notification-card
            {{ $notification->read_at
                ? 'read'
                : 'unread' }}"
        {{ $notification->read_at
            ? 'disabled'
            : '' }}
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
                    {{ \Carbon\Carbon::parse(
                        $notification->created_at
                    )->diffForHumans() }}
                </div>

            </div>

        </div>


        <div class="notification-bottom">

            @if(!$notification->read_at)

                <span class="unread-dot"></span>

            @endif

            @if($notification->type)

                <span class="notification-type">
                    {{ str_replace(
                        '_',
                        ' ',
                        $notification->type
                    ) }}
                </span>

            @endif

            @if($notification->read_at)

                <span class="read-label">
                    <i class="bi bi-check2"></i> Read
                </span>

            @endif

        </div>

    </button>

</form>
