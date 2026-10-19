{{--
    Claimable vouchers on a store page or a product page:
    "Claim ₱50 off, min. spend ₱300" with a Claim button.

    @include('partials.voucher-strip', ['vouchers' => $vouchers])
--}}
@php
    $buyerId = session('user.role') === 'buyer' ? (int) session('user.id') : 0;
    $claimedIds = $buyerId ? \App\Models\VoucherClaim::where('user_id', $buyerId)->pluck('voucher_id')->all() : [];
@endphp

@if(collect($vouchers ?? [])->isNotEmpty())
    <section class="voucher-strip" aria-label="Vouchers">
        @foreach($vouchers as $voucher)
            <div class="voucher-chip" data-claim-voucher="{{ $voucher->code }}">
                <span class="voucher-chip-text">
                    <strong>{{ $voucher->summary() }}</strong>
                    <small>
                        {{ $voucher->applies_to === 'selected' ? 'On selected items' : ($voucher->kind() === 'store' ? 'This store' : 'BoomBuy voucher') }}
                        @if($voucher->ends_at) · until {{ $voucher->ends_at->format('M d') }} @endif
                    </small>
                </span>

                @if(in_array($voucher->id, $claimedIds, true))
                    <span class="voucher-chip-done"><i class="bi bi-check2"></i> Claimed</span>
                @else
                    <form method="POST" action="{{ route('vouchers.claim', $voucher->id) }}">
                        @csrf
                        <button type="submit" class="voucher-chip-btn">Claim</button>
                    </form>
                @endif
            </div>
        @endforeach
    </section>

    @once
    <style>
        .voucher-strip { display: flex; gap: 10px; overflow-x: auto; padding: 2px 0 12px; margin: 0 0 6px; }
        .voucher-chip {
            flex: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border: 1px dashed #b9a6ef;
            border-radius: 12px;
            background: #f8f5ff;
            max-width: 100%;
        }
        .voucher-chip-text { display: flex; flex-direction: column; min-width: 0; }
        .voucher-chip-text strong { font-size: 13px; color: #3f2d86; }
        .voucher-chip-text small { font-size: 11px; color: #6b6385; }
        .voucher-chip-btn { border: none; border-radius: 8px; background: #6d4ad1; color: #fff; padding: 7px 14px; font: inherit; font-size: 12px; font-weight: 800; cursor: pointer; }
        .voucher-chip-done { font-size: 12px; font-weight: 700; color: #15803d; white-space: nowrap; }
    </style>
    @endonce
@endif
