<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Complaints & Disputes — BoomBuy'])

    @php
        $role = $user['role'] ?? 'buyer';
    @endphp

    @if($role === 'seller')
        <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    @elseif($role === 'rider')
        <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">
    @elseif($role === 'logistics')
        <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
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

    @include('partials.buyer-navbar', ['activeNav' => 'complaints'])

    <div class="container">

        @include('pages.partials.complaints-body')

    </div>

    {{-- Shop footer for buyers only; the panels have their own sidebar. --}}
    @include('partials.buyer-footer')

@endif

    @include('partials.pwa-register')

</body>
</html>
