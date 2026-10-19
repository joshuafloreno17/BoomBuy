<?php

namespace App\Http\Controllers;

use App\Support\Payments;
use Illuminate\Support\Facades\DB;

/**
 * BoomBuy's sandbox payment gateway for GCash, Maya and card orders: a test
 * checkout page where the buyer pays, makes the payment fail, or goes back.
 * No real money moves. Paying confirms every order of that checkout once.
 */
class PaymentController extends Controller
{
    public function show(string $group)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        Payments::sweep();

        $orders = DB::table('orders')
            ->where('buyer_id', $user['id'])
            ->where('payment_group', $group)
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            abort(404);
        }

        $payable = $orders->filter(fn ($order) => Payments::awaitingPayment($order) && $order->pay_by && now()->lt($order->pay_by));

        return view('pages.payment', [
            'user' => $user,
            'group' => $group,
            'orders' => $orders,
            'payable' => $payable,
            'amount' => round((float) $payable->sum('total_amount'), 2),
            'method' => $orders->first()->payment_method,
            'payBy' => $payable->min('pay_by'),
            'failed' => $payable->contains(fn ($order) => $order->payment_status === 'Failed'),
        ]);
    }

    /** The sandbox approves the payment. */
    public function pay(string $group)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        Payments::sweep();

        $paid = Payments::markPaid((int) $user['id'], $group);

        if ($paid === 0) {
            $alreadyPaid = DB::table('orders')->where('buyer_id', $user['id'])->where('payment_group', $group)->where('payment_status', 'Paid')->exists();

            return redirect()->route('buyer.orders')->with($alreadyPaid ? 'success' : 'error', $alreadyPaid
                ? 'This order is already paid.'
                : 'This order can no longer be paid. It was cancelled because it was not paid within ' . Payments::PAY_WINDOW_MINUTES . ' minutes.');
        }

        $first = DB::table('orders')->where('buyer_id', $user['id'])->where('payment_group', $group)->orderBy('id')->first();

        return redirect()
            ->route('orders.success', $first->id)
            ->with('success', 'Payment successful. Your ' . ($paid > 1 ? $paid . ' orders are' : 'order is') . ' now To Ship.');
    }

    /** The sandbox's "declined" option: the order stays To Pay with Pay Again. */
    public function fail(string $group)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        Payments::markFailed((int) $user['id'], $group);

        return redirect()->route('payment.show', $group)->with('error', 'Payment failed. You were not charged. You can try again with Pay Again.');
    }

    /** Back from the gateway without paying: the order waits in To Pay. */
    public function cancel(string $group)
    {
        $user = requireUserRole('buyer');

        if (!is_array($user)) {
            return $user;
        }

        return redirect()->route('buyer.orders', ['tab' => 'to-pay'])->with('success', 'Payment not completed. Your order is waiting in To Pay — pay within ' . Payments::PAY_WINDOW_MINUTES . ' minutes of ordering or it is cancelled.');
    }
}
