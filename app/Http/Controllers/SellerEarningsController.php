<?php

namespace App\Http\Controllers;

use App\Support\ProductRules;
use App\Support\SellerBalance;
use App\Support\SubmitOnce;
use Illuminate\Support\Facades\DB;

/**
 * Seller → Earnings (Seller Modules S12, A8, A9, A15): each order's
 * breakdown (items, the seller's own vouchers, commission, net), the
 * Pending and Available balances, and payout requests.
 *
 * BoomBuy's platform and shipping vouchers and the shipping fee are not
 * the seller's, so they are never taken from the seller's earnings.
 */
class SellerEarningsController extends Controller
{
    public function index()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $sellerId = (int) $user['id'];
        $balances = SellerBalance::balances($sellerId);

        // Every order with this seller's items that isn't cancelled: its breakdown.
        $orderIds = DB::table('orders')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.seller_id', $sellerId)
            ->where('orders.status', '!=', 'Cancelled')
            ->distinct()
            ->pluck('orders.id')
            ->all();

        $rows = SellerBalance::orders($sellerId, null, null, null, $orderIds);
        $orders = DB::table('orders')->whereIn('id', $rows->pluck('id'))->get()->keyBy('id');

        return view('pages.seller.earnings', [
            'user' => $user,
            'balances' => $balances,
            'rows' => $rows,
            'orders' => $orders,
            'payouts' => DB::table('seller_payout_requests')->where('seller_id', $sellerId)->orderByDesc('id')->get(),
        ]);
    }

    /**
     * Ask BoomBuy for (part of) the Available balance: ₱100.00 or more, not
     * more than what is available, one pending request at a time, and a
     * double-click asks once.
     */
    public function requestPayout()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $sellerId = (int) $user['id'];
        $token = request(SubmitOnce::FIELD);

        if (!SubmitOnce::claim($token)) {
            return redirect()->route('seller.earnings')->with('success', 'Your payout request was already sent.');
        }

        $amount = ProductRules::cleanNumber(request('amount'));
        $balances = SellerBalance::balances($sellerId);
        $error = match (true) {
            DB::table('seller_payout_requests')->where('seller_id', $sellerId)->where('status', 'Pending')->exists() => 'You already have a payout request pending. Wait for it to be paid (or cancel it) before asking again.',
            !preg_match('/^\d+(\.\d{1,2})?$/', $amount) => 'Enter the amount in pesos, e.g. 100.00.',
            (float) $amount < SellerBalance::PAYOUT_MIN => 'A payout must be at least ₱' . number_format(SellerBalance::PAYOUT_MIN, 2) . '.',
            round((float) $amount, 2) > $balances['available'] => 'You can request at most your Available balance of ₱' . number_format($balances['available'], 2) . '.',
            default => null,
        };

        if ($error) {
            SubmitOnce::release($token);

            return back()->withInput()->withErrors(['amount' => $error])->with('error', $error);
        }

        DB::table('seller_payout_requests')->insert([
            'seller_id' => $sellerId,
            'amount' => round((float) $amount, 2),
            'status' => 'Pending',
            'method' => request('method') ?: null,
            'account_name' => request('account_name') ?: null,
            'account_number' => request('account_number') ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('seller.earnings')->with('success', 'Payout of ₱' . number_format((float) $amount, 2) . ' requested. It shows as Pending until BoomBuy sends it.');
    }

    public function cancelPayout($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $cancelled = DB::table('seller_payout_requests')
            ->where('id', (int) $id)
            ->where('seller_id', $user['id'])
            ->where('status', 'Pending')
            ->update(['status' => 'Cancelled', 'updated_at' => now()]);

        return back()->with($cancelled ? 'success' : 'error', $cancelled ? 'Payout request cancelled. The amount is Available again.' : 'This payout request can no longer be cancelled.');
    }
}
