<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Support\OrderStock;
use Illuminate\Support\Facades\DB;

/**
 * A seller working through a buyer's return or refund request:
 * pending → approved or rejected; a Return then → returned;
 * a Refund then → refund_processing → completed.
 */
class ReturnRefundService
{
    /** @throws ActionFailed */
    public function approve(int $sellerId, int $requestId): string
    {
        $request = DB::table('return_refund_requests')
            ->join('order_items', 'return_refund_requests.order_item_id', '=', 'order_items.id')
            ->where('return_refund_requests.id', $requestId)
            ->where('return_refund_requests.seller_id', $sellerId)
            ->select('return_refund_requests.*', 'order_items.product_name', 'order_items.quantity')
            ->first();

        if (!$request) {
            throw new ActionFailed('Return/Refund request not found.');
        }

        if ($request->status !== 'pending') {
            throw new ActionFailed('This request has already been processed.');
        }

        $this->move($requestId, 'pending', 'approved', 'Request approved by seller.');

        createNotification(
            (int) $request->buyer_id,
            ucfirst($request->request_type) . ' Request Approved',
            'Your ' . strtolower($request->request_type) . ' request for ' . $request->product_name . ' has been approved.',
            'return_refund',
            $requestId
        );

        return 'Return/Refund request for ' . $request->product_name . ' has been approved.';
    }

    /** @throws ActionFailed */
    public function reject(int $sellerId, int $requestId, string $note = ''): string
    {
        $request = $this->sellerRequest($sellerId, $requestId);

        if ($request->status !== 'pending') {
            throw new ActionFailed('This request has already been processed.');
        }

        $note = trim($note);

        $this->move($requestId, 'pending', 'rejected', $note !== '' ? $note : 'Request rejected by seller.');

        createNotification(
            (int) $request->buyer_id,
            ucfirst($request->request_type) . ' Request Rejected',
            'Your ' . strtolower($request->request_type) . ' request was rejected by the seller.' . ($note !== '' ? ' Reason: ' . $note : ''),
            'return_refund',
            $requestId
        );

        return 'Return/Refund request has been rejected.';
    }

    /**
     * The returned item arrived; it goes back into stock unless the seller
     * unticks "Add back to stock" (e.g. it came back damaged).
     *
     * @throws ActionFailed
     */
    public function markReturned(int $sellerId, int $requestId, bool $restock = true): string
    {
        $request = $this->sellerRequest($sellerId, $requestId);

        if ($request->status !== 'approved') {
            throw new ActionFailed('Only approved requests can be marked as returned.');
        }

        if ($request->request_type !== 'Return') {
            throw new ActionFailed('This request is not a return request.');
        }

        // Conditional on still being "approved", so a double click can't
        // restock the same item twice.
        $this->move($requestId, 'approved', 'returned', 'Item has been marked as returned by the seller.');

        $restocked = false;

        if ($restock) {
            $item = DB::table('order_items')->where('id', $request->order_item_id)->first();
            $restocked = $item && OrderStock::restockItem($item);
        }

        createNotification(
            (int) $request->buyer_id,
            'Item Marked as Returned',
            'The seller has confirmed receipt of your returned item.',
            'return_refund',
            $requestId
        );

        return 'Return request has been marked as returned.' . ($restocked ? ' The item was added back to your stock.' : '');
    }

    /** @throws ActionFailed */
    public function startRefund(int $sellerId, int $requestId): string
    {
        $request = $this->sellerRequest($sellerId, $requestId);

        if ($request->status !== 'approved') {
            throw new ActionFailed('Only approved refund requests can be processed.');
        }

        if ($request->request_type !== 'Refund') {
            throw new ActionFailed('This request is not a refund request.');
        }

        $this->move($requestId, 'approved', 'refund_processing', 'Refund is currently being processed.');

        createNotification((int) $request->buyer_id, 'Refund Processing', 'Your refund is now being processed by the seller.', 'return_refund', $requestId);

        return 'Refund is now being processed.';
    }

    /** @throws ActionFailed */
    public function completeRefund(int $sellerId, int $requestId): string
    {
        $request = $this->sellerRequest($sellerId, $requestId);

        if ($request->status !== 'refund_processing') {
            throw new ActionFailed('Only refunds that are being processed can be completed.');
        }

        if ($request->request_type !== 'Refund') {
            throw new ActionFailed('This request is not a refund request.');
        }

        $this->move($requestId, 'refund_processing', 'completed', 'Refund has been completed by the seller.');

        createNotification((int) $request->buyer_id, 'Refund Completed', 'Your refund has been completed by the seller.', 'return_refund', $requestId);

        return 'Refund has been marked as completed.';
    }

    private function sellerRequest(int $sellerId, int $requestId): object
    {
        $request = DB::table('return_refund_requests')->where('id', $requestId)->where('seller_id', $sellerId)->first();

        if (!$request) {
            throw new ActionFailed('Return/Refund request not found.');
        }

        return $request;
    }

    /** Only from the status just checked: a double click can't run a step (or notify) twice. */
    private function move(int $requestId, string $from, string $to, string $note): void
    {
        $moved = DB::table('return_refund_requests')
            ->where('id', $requestId)
            ->where('status', $from)
            ->update(['status' => $to, 'seller_note' => $note, 'updated_at' => now()]);

        if (!$moved) {
            throw new ActionFailed('This request was just updated. Please refresh and try again.');
        }
    }
}
