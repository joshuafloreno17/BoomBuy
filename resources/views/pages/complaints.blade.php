<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Complaints & Disputes — BoomBuy</title>

    @include('partials.pwa-head')

    @php
        $role = $user['role'] ?? 'buyer';
    @endphp

    @if($role === 'seller')
        <link rel="stylesheet" href="{{ asset('css/seller-sidebar.css') }}">
    @elseif($role === 'rider')
        <link rel="stylesheet" href="{{ asset('css/rider-sidebar.css') }}">
    @elseif($role === 'logistics')
        <link rel="stylesheet" href="{{ asset('css/logistics-sidebar.css') }}">
    @endif

    <link rel="stylesheet" href="{{ asset('css/pages/complaints.css') }}">
</head>

<body>

@if($role === 'seller')

    <div class="layout">

        <x-layout.seller-sidebar active="complaints" :user="$user" />

        <main class="main-content">
            <div class="container">
                @include('pages.partials.complaints-body')
            </div>
        </main>

    </div>

@elseif($role === 'rider')

    <x-layout.rider-sidebar active="complaints" :user="$user" />

    <main class="main-content">
        <div class="container">
            @include('pages.partials.complaints-body')
        </div>
    </main>

@elseif($role === 'logistics')

    <x-layout.logistics-sidebar active="complaints" :user="$user" />

    <main class="main-content">
        <div class="container">
            @include('pages.partials.complaints-body')
        </div>
    </main>

@else

    <div class="container">

        @php
            $dashboardRoute = match($role) {
                'seller' => 'seller.dashboard',
                'rider' => 'rider.dashboard',
                'logistics' => 'logistics.dashboard',
                default => 'buyer.dashboard',
            };
        @endphp

        <a href="{{ route($dashboardRoute) }}" class="back-link">
            ← Back to Dashboard
        </a>

        @include('pages.partials.complaints-body')

    </div>

@endif

    @include('partials.pwa-register')

</body>
</html>
