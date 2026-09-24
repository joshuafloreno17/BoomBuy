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
                    Notifications 🔔
                </h1>

                <p>
                    Stay updated with your orders, returns, and application status.
                </p>

            </section>


            @if($notifications->count() > 0)

                <div class="notifications-box">

                    @foreach($notifications as $notification)

                        @php

                            $icon = match($notification->type) {

                                'order' => '🛒',

                                'order_status' => '📦',

                                'return_refund' => '🔄',

                                'seller' => '🏪',

                                'rider' => '🏍️',

                                default => '🔔',

                            };

                        @endphp

                        <x-notification-card
                            :notification="$notification"
                            :icon="$icon"
                            read-route="seller.notifications.read"
                        />

                    @endforeach

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
