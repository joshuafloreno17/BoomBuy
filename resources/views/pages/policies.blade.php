<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'BoomBuy — Policies'])

    <link rel="stylesheet" href="{{ vasset('css/views/policies.css') }}">
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
