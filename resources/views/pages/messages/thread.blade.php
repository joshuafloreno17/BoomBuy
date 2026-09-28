<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $partner->name }} — Messages — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    @php
        $role = $me['role'] ?? 'buyer';
    @endphp

    @if($role === 'seller')
        <link rel="stylesheet" href="{{ asset('css/seller-sidebar.css') }}">
    @elseif($role === 'rider')
        <link rel="stylesheet" href="{{ asset('css/rider-sidebar.css') }}">
    @elseif($role === 'logistics')
        <link rel="stylesheet" href="{{ asset('css/logistics-sidebar.css') }}">
    @elseif($role === 'admin')
        <link rel="stylesheet" href="{{ asset('css/admin-sidebar.css') }}">
    @endif

    <link rel="stylesheet" href="{{ asset('css/pages/messages.css') }}">
</head>

<body>

@if($role === 'seller')

    <div class="layout">

        <x-layout.seller-sidebar active="messages" :user="$me" />

        <main class="main-content">
            <div class="container">
                @include('pages.partials.messages-thread-body')
            </div>
        </main>

    </div>

@elseif($role === 'rider')

    <x-layout.rider-sidebar active="messages" :user="$me" />

    <main class="main-content">
        <div class="container">
            @include('pages.partials.messages-thread-body')
        </div>
    </main>

@elseif($role === 'logistics')

    <x-layout.logistics-sidebar active="messages" :user="$me" />

    <main class="main-content">
        <div class="container">
            @include('pages.partials.messages-thread-body')
        </div>
    </main>

@elseif($role === 'admin')

    <div class="layout">

        <x-layout.admin-sidebar active="messages" />

        <main class="main">
            <div class="container">
                @include('pages.partials.messages-thread-body')
            </div>
        </main>

    </div>

@else

    @include('partials.buyer-navbar', ['activeNav' => 'messages'])

    <div class="container">
        @include('pages.partials.messages-thread-body')
    </div>

@endif

<script>
    var box = document.getElementById('threadBox');
    box.scrollTop = box.scrollHeight;
</script>

    @include('partials.pwa-register')

</body>
</html>
