<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $partner ? $partner['name'] . ' — ' : '' }}Messages — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    @php
        $role = $me['role'] ?? 'buyer';
    @endphp

    @if($role === 'seller')
        <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    @elseif($role === 'rider')
        <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">
    @elseif($role === 'logistics')
        <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    @elseif($role === 'admin')
        <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    @endif

    <link rel="stylesheet" href="{{ vasset('css/pages/messages.css') }}">
</head>

<body class="chat-role-{{ $role === 'buyer' ? 'buyer' : 'panel' }}{{ $partner ? ' chat-open' : '' }}">

@if($role === 'seller')

    <div class="layout">
        <x-layout.seller-sidebar active="messages" :user="$me" />
        <main class="main-content">
            @include('pages.partials.messages-body')
        </main>
    </div>

@elseif($role === 'rider')

    <x-layout.rider-sidebar active="messages" :user="$me" />
    <main class="main-content">
        @include('pages.partials.messages-body')
    </main>

@elseif($role === 'logistics')

    <x-layout.logistics-sidebar active="messages" :user="$me" />
    <main class="main-content">
        @include('pages.partials.messages-body')
    </main>

@elseif($role === 'admin')

    <div class="layout">
        <x-layout.admin-sidebar active="messages" />
        <main class="main">
            @include('pages.partials.messages-body')
        </main>
    </div>

@else

    @include('partials.buyer-navbar', ['activeNav' => 'messages'])
    @include('pages.partials.messages-body')
    @include('partials.buyer-footer')

@endif

    @include('partials.live-search')
    @include('partials.pwa-register')

</body>
</html>
