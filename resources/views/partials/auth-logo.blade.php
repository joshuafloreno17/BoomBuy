{{--
    Logo on the sign-in helper pages (forgot / reset password, verify code).
    With 'logistics' => true it shows the "Logistics" tag and goes to the
    BoomBuy Logistics landing instead of the marketplace.
    Usage: @include('partials.auth-logo', ['logistics' => $isLogistics])
--}}
<a href="{{ !empty($logistics) ? route('logistics.home') : route('home') }}" class="logo bb-auth-logo">
    <img src="{{ vasset('images/logo-wordmark.svg') }}" alt="BoomBuy" class="bb-logo-wordmark" width="131" height="46">
    @if(!empty($logistics))
        <span class="bb-portal-tag">Logistics</span>
    @endif
</a>

@once
<style>
    .bb-auth-logo { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; }
    .bb-portal-tag {
        background: #f1ecfd;
        color: var(--theme-primary);
        border: 1px solid #e0d6fb;
        border-radius: 999px;
        padding: 4px 10px;
        font-family: 'Figtree', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .02em;
    }
</style>
@endonce
