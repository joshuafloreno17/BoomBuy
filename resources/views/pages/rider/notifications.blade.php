<!DOCTYPE html>
<html lang="en">

<head>

    @include('partials.head', ['title' => 'Notifications — BoomBuy Rider'])

    <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/rider-notifications.css') }}">

</head>

<body>

    <!-- =========================
         SIDEBAR
    ========================= -->

    <x-layout.rider-sidebar active="notifications" :user="$user" />


    <!-- =========================
         MAIN
    ========================= -->

    <main class="main main-content">

        <div class="container">

            <div class="page-header">

                <div>

                    <h1>
                        Notifications
                    </h1>

                    <p>
                        Stay updated with your delivery assignments
                        and rider account status.
                    </p>

                </div>

                <div class="profile-top">

                    <i class="bi bi-bicycle"></i>

                    <strong>
                        {{ $user['name'] ?? 'Rider' }}
                    </strong>

                </div>

            </div>


            @if(($unreadCount ?? 0) > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}" class="bb-mark-all">
                    @csrf
                    <span><strong>{{ $unreadCount }}</strong> unread</span>
                    <button type="submit"><i class="bi bi-check2-all"></i> Mark all as read</button>
                </form>
            @endif

            @if($notifications->count() > 0)

                <div class="notifications">

                    @foreach($notifications as $notification)

                        @php

                            $icon = match($notification->type) {

                                'delivery',
                                'order' => 'bi-truck',

                                'delivery_status',
                                'order_status' => 'bi-box-seam-fill',

                                'rider' => 'bi-bicycle',

                                default => 'bi-bell-fill',

                            };

                        @endphp


                        <form
                            action="{{ !$notification->read_at
                                ? route(
                                    'rider.notifications.read',
                                    $notification->id
                                )
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
                                        : 'unread'
                                    }}"
                                {{ $notification->read_at
                                    ? 'disabled'
                                    : ''
                                }}
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

                    @endforeach
                @include('partials.simple-pager', ['paginator' => $notifications])

                </div>

            @else

                <div class="empty">

                    <div class="empty-icon">
                        <i class="bi bi-bell-fill"></i>
                    </div>

                    <h3>
                        No Notifications Yet
                    </h3>

                    <p>
                        New delivery assignments and rider updates
                        will appear here.
                    </p>

                </div>

            @endif

        </div>

    </main>


    @include('partials.pwa-register')

</body>

</html>