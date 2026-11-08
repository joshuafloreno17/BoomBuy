{{--
    The buyer's delivery code, big, while the parcel is out for delivery:
    who is bringing it, the code to say at the door, and the cash to prepare.
    @include('partials.delivery-code-card', ['code' => …, 'rider' => …, 'amount' => …, 'isCod' => …])
--}}
@once
<style>
    .dcode { margin: 12px 0; padding: 16px; border-radius: 18px; background: #2a1846; color: #fff; display: flex; flex-direction: column; gap: 10px; text-align: center; }
    .dcode-top { display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 800; font-size: 15px; }
    .dcode-dot { width: 10px; height: 10px; border-radius: 50%; background: #fbbf24; box-shadow: 0 0 0 5px rgba(251, 191, 36, .25); }
    .dcode-rider { font-size: 13px; color: #c9b6ff; }
    .dcode-box { background: #fff; color: #221a3a; border-radius: 14px; padding: 12px; display: flex; flex-direction: column; gap: 4px; }
    .dcode-box small { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #6d4ad1; }
    .dcode-box strong { font-family: 'Space Mono', ui-monospace, Consolas, monospace; font-size: 34px; letter-spacing: .16em; line-height: 1.1; }
    .dcode-box span { font-size: 12px; color: #645a80; }
    .dcode-cash { align-self: center; padding: 6px 12px; border-radius: 999px; background: rgba(251, 191, 36, .18); color: #fde68a; font-weight: 800; font-size: 13px; }
</style>
@endonce

<div class="dcode" role="region" aria-label="Delivery code">
    <div class="dcode-top"><span class="dcode-dot" aria-hidden="true"></span> Out for delivery today</div>
    @if(!empty($rider))
        <div class="dcode-rider">{{ $rider }} is bringing it</div>
    @endif
    <div class="dcode-box">
        <small>Delivery OTP · give this code to the rider</small>
        <strong data-delivery-otp>{{ $code }}</strong>
        <span>Say it only when the parcel is in your hands.</span>
    </div>
    @if(!empty($isCod))
        <span class="dcode-cash">Prepare ₱{{ number_format((float) ($amount ?? 0), 2) }} cash</span>
    @endif
</div>
