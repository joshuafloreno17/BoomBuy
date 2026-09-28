<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications — BoomBuy Logistics</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/logistics-notifications.css') }}">
</head>

<body>

    <x-layout.logistics-sidebar active="notifications" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header">
                <h1>Notifications</h1>
                <p>Stay updated on rider applications, incoming parcels, and account status.</p>
            </div>

            @if($notifications->count() > 0)

                <div class="notifications-box">

                    @foreach($notifications as $notification)

                        @php
                            $icon = match($notification->type) {
                                'rider' => 'bi-bicycle',
                                'parcel' => 'bi-box-seam-fill',
                                'return_refund' => 'bi-arrow-repeat',
                                'logistics' => 'bi-truck',
                                default => 'bi-bell-fill',
                            };
                        @endphp

                        <x-notification-card
                            :notification="$notification"
                            :icon="$icon"
                            read-route="logistics.notifications.read"
                        />

                    @endforeach

                </div>

            @else

                <x-notification-empty
                    message="New rider applications and parcel updates will appear here."
                />

            @endif

        </div>

    </main>

    @include('partials.pwa-register')

</body>
</html>
