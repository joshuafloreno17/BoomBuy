<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'Notifications — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/admin-notifications.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="notifications" />


    <!-- MAIN -->

    <main class="main">

        <div class="page-header">

            <div>

                <small>
                    BoomBuy Administration
                </small>

                <h1>
                    Notifications
                </h1>

            </div>

            <a
                href="{{ route('admin.dashboard') }}"
                class="back-button"
            >
                ← Back to Dashboard
            </a>

        </div>


        <!-- NOTIFICATION PANEL -->

        <section class="notification-panel">

            <div class="notification-header">

                <div>

                    <h2>
                        Admin Notifications
                    </h2>

                    <p>
                        Updates and alerts for your BoomBuy administration account.
                    </p>

                </div>

                <div>
                    @if($unreadCount > 0)
                        <span class="notification-type">
                            {{ $unreadCount }} Unread
                        </span>
                    @else
                        <span class="read-label">
                            All Caught Up
                        </span>
                    @endif
                </div>

            </div>


            <div class="notification-list">

                @forelse($notifications as $notification)

                    <form
                        action="{{ !$notification->read_at
                            ? route('admin.notifications.read', $notification->id)
                            : '#'
                        }}"
                        method="{{ !$notification->read_at ? 'POST' : 'GET' }}"
                        class="notification-form"
                    >

                        @if(!$notification->read_at)
                            @csrf
                        @endif

                        <button
                            type="{{ !$notification->read_at ? 'submit' : 'button' }}"
                            class="notification-card {{ $notification->read_at ? 'read' : 'unread' }}"
                            {{ $notification->read_at ? 'disabled' : '' }}
                        >

                            <div class="notification-icon">

                                @if($notification->type === 'seller')
                                    <i class="bi bi-shop"></i>
                                @elseif($notification->type === 'rider')
                                    <i class="bi bi-bicycle"></i>
                                @elseif($notification->type === 'order')
                                    <i class="bi bi-box-seam-fill"></i>
                                @else
                                    <i class="bi bi-bell-fill"></i>
                                @endif

                            </div>


                            <div class="notification-content">

                                <div class="notification-title">
                                    {{ $notification->title }}
                                </div>

                                <div class="notification-message">
                                    {{ $notification->message }}
                                </div>

                                <div class="notification-date">
                                    {{ $notification->created_at?->format('M d, Y • h:i A') }}
                                </div>

                                <div class="notification-status">

                                    @if(!$notification->read_at)
                                        <span class="unread-dot"></span>
                                    @endif

                                    @if($notification->type)
                                        <span class="notification-type">
                                            {{ str_replace('_', ' ', $notification->type) }}
                                        </span>
                                    @endif

                                    @if($notification->read_at)
                                        <span class="read-label">
                                            <i class="bi bi-check2"></i> Read
                                        </span>
                                    @endif

                                </div>

                            </div>

                        </button>

                    </form>

                @empty

                    <div class="empty-state">

                        <div class="empty-icon">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="25"
                                height="25"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>

                        </div>

                        <h3>
                            No notifications yet
                        </h3>

                        <p>
                            New BoomBuy administration updates will appear here.
                        </p>

                    </div>

                @endforelse
                @include('partials.simple-pager', ['paginator' => $notifications])

            </div>

        </section>

    </main>

</div>

@include('partials.pwa-register')

</body>
</html>