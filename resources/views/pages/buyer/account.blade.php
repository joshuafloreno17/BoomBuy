<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Account — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-body);
            background: var(--cream);
            color: var(--ink);
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .account-page {
            width: calc(100% - 32px);
            max-width: 560px;
            margin: 24px auto 48px;
        }

        .account-page h1 {
            margin-bottom: 16px;
            font-family: var(--font-display);
            font-size: 26px;
            font-weight: 800;
        }
    </style>
</head>

<body>

    @include('partials.buyer-navbar', ['activeNav' => 'account'])

    <main class="account-page">
        <h1>My account</h1>

        @include('partials.buyer-account-panel', ['panel' => $panel, 'panelVariant' => 'page'])
    </main>

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>
</html>
