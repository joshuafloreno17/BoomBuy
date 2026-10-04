<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'My Account — BoomBuy'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ vasset('css/views/buyer-account.css') }}">
</head>

<body>

    @include('partials.buyer-navbar', ['activeNav' => 'account'])

    <main class="account-page">
        @include('partials.page-head', ['title' => 'My account'])

        @include('partials.buyer-account-panel', ['panel' => $panel, 'panelVariant' => 'page'])
    </main>

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>
</html>
