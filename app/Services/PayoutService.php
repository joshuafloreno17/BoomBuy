<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * BoomBuy pays a seller's payout request (A2): the admin sends the money
 * outside the system (GCash, Maya, bank) and records the reference here.
 * The request then counts as Paid on the seller's Earnings page.
 */
class PayoutService
{
    /** @throws ActionFailed */
    public function markPaid(int $requestId, string $reference): string
    {
        $request = DB::table('seller_payout_requests')->find($requestId);

        if (!$request) {
            throw new ActionFailed('Payout request not found.');
        }

        if ($request->status !== 'Pending') {
            throw new ActionFailed('This payout request is already ' . strtolower($request->status) . '.');
        }

        $reference = trim($reference);

        if ($reference === '') {
            throw new ActionFailed('Enter the transfer\'s reference number so the seller can check it.');
        }

        // Only from Pending, so a double click or a cancel at the same moment can't both win.
        $paid = DB::table('seller_payout_requests')
            ->where('id', $requestId)
            ->where('status', 'Pending')
            ->update(['status' => 'Paid', 'reference' => Str::limit($reference, 60, ''), 'paid_at' => now(), 'updated_at' => now()]);

        if (!$paid) {
            throw new ActionFailed('This payout request was just updated. Please refresh and try again.');
        }

        createNotification(
            (int) $request->seller_id,
            'Payout Sent',
            'BoomBuy sent your payout of ₱' . number_format((float) $request->amount, 2)
                . ($request->method ? ' to your ' . $request->method : '') . '. Reference: ' . $reference . '.',
            'payout'
        );

        return 'Payout #' . $requestId . ' of ₱' . number_format((float) $request->amount, 2) . ' marked as paid.';
    }
}
