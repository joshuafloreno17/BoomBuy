<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Notifications — BoomBuy Seller</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/pages/seller-notifications.css') }}">

</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="notifications" :user="$user" />

    <!-- MAIN -->

    <main class="main-content">

        <div class="container">

            <section class="page-header">

                <small>
                    Seller Panel
                </small>

                <h1>
                    Notifications
                </h1>

                <p>
                    Stay updated with your orders, returns, and application status.
                </p>

            </section>


            @if(($unreadCount ?? 0) > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}" class="bb-mark-all">
                    @csrf
                    <span><strong>{{ $unreadCount }}</strong> unread</span>
                    <button type="submit"><i class="bi bi-check2-all"></i> Mark all as read</button>
                </form>
            @endif

            @if($notifications->count() > 0)

                <div class="notifications-box">

                    @foreach($notifications as $notification)

                        @php

                            $icon = match($notification->type) {

                                'order' => 'bi-cart-fill',

                                'order_status' => 'bi-box-seam-fill',

                                'return_refund' => 'bi-arrow-repeat',

                                'seller' => 'bi-shop',

                                'rider' => 'bi-bicycle',

                                default => 'bi-bell-fill',

                            };

                        @endphp

                        <x-notification-card
                            :notification="$notification"
                            :icon="$icon"
                            read-route="seller.notifications.read"
                        />

                    @endforeach
                @include('partials.simple-pager', ['paginator' => $notifications])

                </div>

            @else

                <x-notification-empty
                    message="New order and seller updates will appear here."
                />

            @endif

        </div>

    </main>

</div>


<footer>

    <div>
        © 2026 <strong>BoomBuy</strong>
    </div>

    <div>
        Seller Notifications
    </div>

</footer>


@include('partials.pwa-register')

</body>

</html>
