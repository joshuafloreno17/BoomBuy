<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BoomBuy — Policies</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <style>
@import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: #fff7f4;
            color: #172033;
        }

        .policies {
            max-width: 860px;
            margin: 36px auto;
            padding: 0 16px;
        }

        .policies h1 {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            font-size: 32px;
            margin-bottom: 6px;
        }

        .policies .lead {
            color: #8e7067;
            margin-bottom: 22px;
        }

        .policy-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .policy-tabs a {
            padding: 9px 16px;
            border-radius: 999px;
            border: 1px solid #f0dcd5;
            background: #fff;
            color: #6f5850;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
        }

        .policy-tabs a:hover {
            border-color: var(--accent, #f13f09);
            color: var(--accent, #f13f09);
        }

        .policy-card {
            background: #fff;
            border: 1px solid #f6e1db;
            border-radius: 16px;
            padding: 26px 28px;
            margin-bottom: 18px;
            scroll-margin-top: 90px;
        }

        .policy-card h2 {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            font-size: 22px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .policy-card h2 i {
            color: var(--accent, #f13f09);
        }

        .terms-section {
            margin-bottom: 16px;
        }

        .terms-section:last-child {
            margin-bottom: 0;
        }

        .terms-section h3 {
            font-size: 15px;
            margin-bottom: 4px;
        }

        .terms-section p,
        .terms-section li {
            color: #5f4a43;
            font-size: 14px;
            line-height: 1.7;
        }

        .terms-section p + p {
            margin-top: 10px;
        }

        .terms-section ul {
            padding-left: 20px;
            margin-top: 6px;
        }

        @media (max-width: 600px) {
            .policies { margin: 22px auto; }
            .policies h1 { font-size: 26px; }
            .policy-card { padding: 20px 18px; }
        }
    </style>
</head>

<body>

@include('partials.buyer-navbar', ['activeNav' => 'shop'])

<main class="policies">

    <h1>BoomBuy Policies</h1>
    <p class="lead">The rules every buyer, seller and rider agrees to when using BoomBuy.</p>

    <nav class="policy-tabs" aria-label="Policies">
        <a href="#terms">Terms &amp; Conditions</a>
        <a href="#privacy">Privacy Policy</a>
        <a href="#returns">Return &amp; Refund Policy</a>
    </nav>

    <section class="policy-card" id="terms">
        <h2><i class="bi bi-file-earmark-text"></i> Terms &amp; Conditions</h2>
        @include('partials.policies.body', ['key' => 'terms_policy', 'default' => 'partials.policies.terms-default'])
    </section>

    <section class="policy-card" id="privacy">
        <h2><i class="bi bi-shield-lock"></i> Privacy Policy</h2>
        @include('partials.policies.body', ['key' => 'privacy_policy', 'default' => 'partials.policies.privacy-default'])
    </section>

    <section class="policy-card" id="returns">
        <h2><i class="bi bi-arrow-return-left"></i> Return &amp; Refund Policy</h2>
        @include('partials.policies.body', ['key' => 'return_policy', 'default' => 'partials.policies.return-default'])
    </section>

</main>

@include('partials.buyer-footer')

@include('partials.pwa-register')

</body>
</html>
