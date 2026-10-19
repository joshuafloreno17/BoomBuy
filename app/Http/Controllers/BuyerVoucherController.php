<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\VoucherClaim;

/**
 * The buyer's voucher wallet: claim a store's or BoomBuy's claimable
 * vouchers, and see the ones still usable (My Vouchers). A voucher leaves
 * the wallet once it is used up for this buyer, ended or expired — and
 * comes back when the order it was used on is cancelled.
 */
class BuyerVoucherController extends Controller
{
    public function index()
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $buyerId = (int) $user['id'];

        $wallet = VoucherClaim::where('user_id', $buyerId)
            ->with('voucher')
            ->latest()
            ->get()
            ->pluck('voucher')
            ->filter(fn (?Voucher $voucher) => $voucher && $voucher->isUsableNow() && !$voucher->usedUpBy($buyerId))
            ->values();

        $shops = \App\Support\SellerShop::many($wallet->pluck('seller_id')->filter());

        return view('pages.buyer.vouchers', [
            'user' => $user,
            'wallet' => $wallet,
            'shops' => $shops,
            // BoomBuy's own vouchers waiting to be claimed.
            'platform' => Voucher::claimable(null)->reject(fn ($v) => $wallet->contains('id', $v->id))->values(),
        ]);
    }

    public function claim($id)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        $voucher = Voucher::find((int) $id);

        // Code-only vouchers are never listed, so they can't be claimed from a list either.
        if (!$voucher || $voucher->display !== 'claimable' || !$voucher->isUsableNow()) {
            return back()->with('error', 'This voucher is no longer available.');
        }

        if ($voucher->usedUpBy((int) $user['id'])) {
            return back()->with('error', 'You have already used voucher ' . $voucher->code . '.');
        }

        $claim = VoucherClaim::firstOrCreate(['voucher_id' => $voucher->id, 'user_id' => $user['id']]);

        return back()->with('success', $claim->wasRecentlyCreated
            ? 'Voucher ' . $voucher->code . ' claimed — it is in My Vouchers. Apply it in your cart.'
            : 'Voucher ' . $voucher->code . ' is already in My Vouchers.');
    }
}
