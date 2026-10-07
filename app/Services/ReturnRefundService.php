<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Support\OrderStock;
use App\Support\ParcelRoute;
use App\Support\SupportAccount;
use Illuminate\Support\Facades\DB;

/**
 * A buyer's return or refund request, from start to refund.
 *
 *   pending ─ seller approves ─┬─ Refund (nothing to send back) ─────────────┐
 *     │                        └─ Return: approved → dropped_off (buyer's     │
 *     │                           center) → in_transit → ready_for_seller     │
 *     │                           (seller's center) → handed to the seller ───┤
 *     ├─ seller rejects → rejected ─ buyer asks BoomBuy ─┐                    │
 *     └─ seller silent 3 days (StaleOrders) ─────────────┴→ disputed ─ admin ─┤
 *                                                                             ▼
 *                                   refund_pending ─ BoomBuy sends it → completed
 *
 * BoomBuy (the admin) sends the refund: it holds the buyer's money until the
 * return window closes (SellerBalance), and the refunded amount never
 * reaches the seller. "returned" and "refund_processing" are from the older
 * flow, before the item went through the Sorting Centers.
 */
class ReturnRefundService
{
    /** A request in one of these blocks another for the same item and holds the seller's payout. */
    public const OPEN = ['pending', 'disputed', 'approved', 'dropped_off', 'in_transit', 'ready_for_seller', 'refund_pending', 'refund_processing'];

    /** Where the buyer's refund can go. */
    public const REFUND_METHODS = ['GCash', 'Maya', 'Bank transfer'];

    /** Days after a rejection the buyer may still ask BoomBuy to review it. */
    public const DISPUTE_DAYS = 7;

    /** What each status means, for the buyer, seller and admin pages. */
    public const LABELS = [
        'pending' => 'Waiting for the seller',
        'disputed' => 'BoomBuy is reviewing',
        'approved' => 'Approved — bring the item to the Sorting Center',
        'dropped_off' => 'Received at the Sorting Center',
        'in_transit' => 'On its way back to the seller',
        'ready_for_seller' => 'At the seller\'s Sorting Center',
        'refund_pending' => 'Refund being sent by BoomBuy',
        'refund_processing' => 'Refund being sent by BoomBuy',
        'completed' => 'Refunded',
        'returned' => 'Returned',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    // ------------------------------------------------------------------
    // Buyer
    // ------------------------------------------------------------------

    /** @throws ActionFailed */
    public function cancelByBuyer(int $buyerId, int $requestId): string
    {
        $request = $this->find($requestId, fn ($q) => $q->where('return_refund_requests.buyer_id', $buyerId));

        if (!in_array($request->status, ['pending', 'disputed', 'approved'], true)) {
            throw new ActionFailed('This request can no longer be cancelled.');
        }

        $this->move($requestId, $request->status, 'cancelled', ['closed_at' => now()]);

        if ($request->seller_id) {
            createNotification((int) $request->seller_id, 'Return Request Cancelled', 'The buyer cancelled their ' . strtolower($request->request_type) . ' request for order #' . $request->order_id . '.', 'return_refund', $requestId);
        }

        return 'Your request was cancelled.';
    }

    /**
     * A rejected request goes to BoomBuy for a final decision.
     *
     * @throws ActionFailed
     */
    public function escalate(int $buyerId, int $requestId, string $why = ''): string
    {
        $request = $this->find($requestId, fn ($q) => $q->where('return_refund_requests.buyer_id', $buyerId));

        if ($request->status !== 'rejected') {
            throw new ActionFailed('Only a rejected request can be sent to BoomBuy for review.');
        }

        if (!empty($request->escalated_at)) {
            throw new ActionFailed('BoomBuy has already reviewed this request. Its decision is final.');
        }

        if (\Illuminate\Support\Carbon::parse($request->updated_at)->addDays(self::DISPUTE_DAYS)->isPast()) {
            throw new ActionFailed('You can ask BoomBuy to review a rejection within ' . self::DISPUTE_DAYS . ' days. This one is older.');
        }

        $why = trim($why);

        $this->move($requestId, 'rejected', 'disputed', [
            'escalated_at' => now(),
            'admin_note' => $why !== '' ? 'Buyer: ' . \Illuminate\Support\Str::limit($why, 500) : null,
        ]);

        $this->tellAdmin('Return Review Requested', 'The buyer of order #' . $request->order_id . ' asked BoomBuy to review a rejected ' . strtolower($request->request_type) . ' request.', $requestId);

        if ($request->seller_id) {
            createNotification((int) $request->seller_id, 'Return Sent to BoomBuy', 'The buyer asked BoomBuy to review your rejection of their request for order #' . $request->order_id . '. BoomBuy will decide.', 'return_refund', $requestId);
        }

        return 'Sent to BoomBuy. We will review it and tell you the decision.';
    }

    // ------------------------------------------------------------------
    // Seller
    // ------------------------------------------------------------------

    /** @throws ActionFailed */
    public function approve(int $sellerId, int $requestId): string
    {
        $request = $this->find($requestId, fn ($q) => $q->where('return_refund_requests.seller_id', $sellerId));

        if ($request->status !== 'pending') {
            throw new ActionFailed('This request has already been processed.');
        }

        $this->accept($request, 'pending', 'Approved by the seller.');

        return 'Request for ' . $request->product_name . ' approved.'
            . ($request->request_type === 'Return' ? ' The buyer will drop the item at their Sorting Center; you collect it at yours.' : ' BoomBuy will send the refund.');
    }

    /** @throws ActionFailed */
    public function reject(int $sellerId, int $requestId, string $note = ''): string
    {
        $request = $this->find($requestId, fn ($q) => $q->where('return_refund_requests.seller_id', $sellerId));

        if ($request->status !== 'pending') {
            throw new ActionFailed('This request has already been processed.');
        }

        $note = trim($note);

        if ($note === '') {
            throw new ActionFailed('Please tell the buyer why you are rejecting it.');
        }

        $this->move($requestId, 'pending', 'rejected', ['seller_note' => \Illuminate\Support\Str::limit($note, 500)]);

        createNotification(
            (int) $request->buyer_id,
            ucfirst($request->request_type) . ' Request Rejected',
            'Your ' . strtolower($request->request_type) . ' request for ' . $request->product_name . ' was rejected by the seller. Reason: ' . $note
                . '. If you disagree, open your order and tap "Ask BoomBuy to review" within ' . self::DISPUTE_DAYS . ' days.',
            'return_refund',
            $requestId
        );

        return 'Request rejected. The buyer may still ask BoomBuy to review it.';
    }

    /**
     * The returned item is back with the seller: put it back in stock (once),
     * unless it came back damaged.
     *
     * @throws ActionFailed
     */
    public function restock(int $sellerId, int $requestId): string
    {
        $request = $this->find($requestId, fn ($q) => $q->where('return_refund_requests.seller_id', $sellerId));

        if ($request->request_type !== 'Return' || empty($request->returned_at) && $request->status !== 'returned') {
            throw new ActionFailed('Only an item that came back to you can be restocked.');
        }

        $claimed = DB::table('return_refund_requests')->where('id', $requestId)->whereNull('restocked_at')->update(['restocked_at' => now()]);

        if (!$claimed) {
            throw new ActionFailed('This item was already added back to your stock.');
        }

        $item = DB::table('order_items')->where('id', $request->order_item_id)->first();

        if (!$item || !OrderStock::restockItem($item)) {
            return 'Marked as restocked, but the option it was bought as no longer exists — please adjust its stock by hand.';
        }

        return $request->product_name . ' was added back to your stock.';
    }

    // ------------------------------------------------------------------
    // Sorting Centers: the returned item on its way back
    // ------------------------------------------------------------------

    /**
     * The buyer brought the item to their Sorting Center.
     *
     * @throws ActionFailed
     */
    public function receiveFromBuyer(int $requestId, ?int $centerId = null): string
    {
        $request = $this->find($requestId);

        if ($request->request_type !== 'Return' || $request->status !== 'approved') {
            throw new ActionFailed('This return is not waiting for the buyer to drop it off.');
        }

        $buyerCenter = self::buyerCenterId($request);
        $this->mustBeAt($buyerCenter, $centerId, 'is to be dropped off at another Sorting Center');
        $here = $buyerCenter ?: $centerId;
        $sellerCenter = $request->origin_center_id ?: $here;

        // Same center: it simply waits there for the seller.
        $next = (int) $sellerCenter === (int) $here ? 'ready_for_seller' : 'dropped_off';

        $this->move($requestId, 'approved', $next, ['dropped_off_at' => now(), 'current_center_id' => $here]);

        createNotification((int) $request->buyer_id, 'Return Received', 'We received the ' . $request->product_name . ' you are returning (order #' . $request->order_id . '). It is going back to the seller; your refund follows once they have it.', 'return_refund', $requestId);

        if ($next === 'ready_for_seller') {
            $this->tellSellerToCollect($request, $here);
        }

        return 'Return #' . $requestId . ' received from the buyer.' . ($next === 'dropped_off' ? ' Next: send it to ' . ParcelRoute::centerName((int) $sellerCenter) . '.' : ' The seller has been told to collect it.');
    }

    /** @throws ActionFailed */
    public function sendToSellerCenter(int $requestId, ?int $centerId = null): string
    {
        $request = $this->find($requestId);

        if ($request->status !== 'dropped_off') {
            throw new ActionFailed('This return is not waiting to be sent on.');
        }

        $this->mustBeAt($request->current_center_id, $centerId, 'is at another Sorting Center');

        $this->move($requestId, 'dropped_off', 'in_transit', ['current_center_id' => null]);

        ParcelRoute::notifyCenter($request->origin_center_id ? (int) $request->origin_center_id : null, 'Incoming Return', 'A returned item (return #' . $requestId . ', order #' . $request->order_id . ') is on its way to you for its seller. Confirm when it arrives.', 'parcel', (int) $request->order_id);

        return 'Return #' . $requestId . ' sent to ' . (ParcelRoute::centerName($request->origin_center_id ? (int) $request->origin_center_id : null) ?? 'the seller\'s Sorting Center') . '.';
    }

    /** @throws ActionFailed */
    public function arriveAtSellerCenter(int $requestId, ?int $centerId = null): string
    {
        $request = $this->find($requestId);

        if ($request->status !== 'in_transit') {
            throw new ActionFailed('This return is not on its way.');
        }

        $this->mustBeAt($request->origin_center_id, $centerId, 'is going to another Sorting Center');

        $this->move($requestId, 'in_transit', 'ready_for_seller', ['current_center_id' => $request->origin_center_id]);
        $this->tellSellerToCollect($request, $request->origin_center_id ? (int) $request->origin_center_id : null);

        return 'Return #' . $requestId . ' arrived. The seller has been told to collect it.';
    }

    /**
     * The seller took the returned item. The refund is now BoomBuy's to send.
     *
     * @throws ActionFailed
     */
    public function handToSeller(int $requestId, ?int $centerId = null): string
    {
        $request = $this->find($requestId);

        if ($request->status !== 'ready_for_seller') {
            throw new ActionFailed('This return is not waiting for its seller.');
        }

        $this->mustBeAt($request->current_center_id, $centerId, 'is at another Sorting Center');

        $this->move($requestId, 'ready_for_seller', 'refund_pending', ['returned_at' => now()]);

        if ($request->seller_id) {
            createNotification((int) $request->seller_id, 'Returned Item Collected', 'You collected the returned ' . $request->product_name . ' (order #' . $request->order_id . '). If it can be sold again, tap "Add back to stock" on your Return Requests tab.', 'return_refund', $requestId);
        }

        createNotification((int) $request->buyer_id, 'Return Delivered to Seller', 'The seller has your returned ' . $request->product_name . '. BoomBuy will send your refund of ₱' . number_format((float) $request->refund_amount, 2) . ' shortly.', 'return_refund', $requestId);

        $this->tellAdmin('Refund to Send', 'Return #' . $requestId . ' (order #' . $request->order_id . ') is back with the seller. Send the buyer ₱' . number_format((float) $request->refund_amount, 2) . '.', $requestId);

        return 'Return #' . $requestId . ' handed to the seller.';
    }

    // ------------------------------------------------------------------
    // Admin (BoomBuy)
    // ------------------------------------------------------------------

    /**
     * BoomBuy's final word on a disputed request.
     *
     * @throws ActionFailed
     */
    public function decide(int $requestId, bool $approve, string $note): string
    {
        $request = $this->find($requestId);

        if ($request->status !== 'disputed') {
            throw new ActionFailed('This request is not waiting for BoomBuy\'s decision.');
        }

        $note = trim($note);

        if ($note === '') {
            throw new ActionFailed('Please write the reason for your decision. Both the buyer and the seller see it.');
        }

        $adminNote = trim(($request->admin_note ? $request->admin_note . "\n" : '') . 'BoomBuy: ' . \Illuminate\Support\Str::limit($note, 500));

        if ($approve) {
            DB::table('return_refund_requests')->where('id', $requestId)->update(['admin_note' => $adminNote]);
            $this->accept($request, 'disputed', 'Approved by BoomBuy: ' . $note);

            if ($request->seller_id) {
                createNotification((int) $request->seller_id, 'BoomBuy Approved a Return', 'BoomBuy approved the buyer\'s ' . strtolower($request->request_type) . ' request for order #' . $request->order_id . '. Reason: ' . $note, 'return_refund', $requestId);
            }

            return 'Request approved.';
        }

        $this->move($requestId, 'disputed', 'rejected', ['admin_note' => $adminNote, 'closed_at' => now(), 'escalated_at' => $request->escalated_at ?: now()]);

        createNotification((int) $request->buyer_id, 'Return Request Closed', 'BoomBuy reviewed your ' . strtolower($request->request_type) . ' request for order #' . $request->order_id . ' and closed it. Reason: ' . $note, 'return_refund', $requestId);

        if ($request->seller_id) {
            createNotification((int) $request->seller_id, 'Return Request Closed', 'BoomBuy closed the buyer\'s request for order #' . $request->order_id . ' in your favour. Reason: ' . $note, 'return_refund', $requestId);
        }

        return 'Request closed.';
    }

    /**
     * BoomBuy sent the buyer their money.
     *
     * @throws ActionFailed
     */
    public function markRefunded(int $requestId, string $reference, ?int $adminId = null): string
    {
        $request = $this->find($requestId);

        if (!in_array($request->status, ['refund_pending', 'refund_processing'], true)) {
            throw new ActionFailed('This request has no refund waiting to be sent.');
        }

        $reference = trim($reference);

        if ($reference === '') {
            throw new ActionFailed('Enter the transfer\'s reference number so the buyer can check it.');
        }

        $this->move($requestId, $request->status, 'completed', [
            'refund_reference' => \Illuminate\Support\Str::limit($reference, 100, ''),
            'refunded_at' => now(),
            'refunded_by' => $adminId,
            'closed_at' => now(),
        ]);

        $to = $request->refund_method ? ' to your ' . $request->refund_method . ($request->refund_account_number ? ' (' . self::masked($request->refund_account_number) . ')' : '') : '';

        createNotification((int) $request->buyer_id, 'Refund Sent', 'BoomBuy sent your refund of ₱' . number_format((float) $request->refund_amount, 2) . $to . '. Reference: ' . $reference . '.', 'return_refund', $requestId);

        if ($request->seller_id) {
            createNotification((int) $request->seller_id, 'Buyer Refunded', 'BoomBuy refunded ₱' . number_format((float) $request->refund_amount, 2) . ' for ' . $request->product_name . ' (order #' . $request->order_id . '). It is not part of your payout.', 'return_refund', $requestId);
        }

        return 'Refund for return #' . $requestId . ' marked as sent.';
    }

    // ------------------------------------------------------------------
    // Shared
    // ------------------------------------------------------------------

    /** The buyer's Sorting Center for a request (where they drop the item). */
    public static function buyerCenterId(object $request): ?int
    {
        if (!empty($request->destination_center_id)) {
            return (int) $request->destination_center_id;
        }

        $center = $request->shipping_province ? ParcelRoute::centerFor($request->shipping_province, $request->shipping_city ?? null) : null;

        return $center?->id;
    }

    /** "•••• 4321" — enough for the buyer to recognise their account. */
    public static function masked(?string $number): string
    {
        $digits = preg_replace('/\s+/', '', (string) $number);

        return strlen($digits) > 4 ? '•••• ' . substr($digits, -4) : $digits;
    }

    /** Approved (by the seller or BoomBuy): a Return waits for drop-off, a Refund goes straight to BoomBuy. */
    private function accept(object $request, string $from, string $note): void
    {
        $isReturn = $request->request_type === 'Return';

        $this->move((int) $request->id, $from, $isReturn ? 'approved' : 'refund_pending', ['approved_at' => now(), 'seller_note' => $request->seller_note ?: $note]);

        if ($isReturn) {
            $center = ($id = self::buyerCenterId($request)) ? \App\Models\SortingCenter::find($id) : null;
            $where = $center ? $center->name . ' (' . ($center->address ?: $center->town) . ')' : 'your nearest BoomBuy Sorting Center';

            createNotification(
                (int) $request->buyer_id,
                'Return Approved',
                'Your return for ' . $request->product_name . ' was approved. Pack the item and bring it to ' . $where . ' with return number #' . $request->id . '. Your refund of ₱' . number_format((float) $request->refund_amount, 2) . ' follows once the seller has it.',
                'return_refund',
                (int) $request->id
            );

            return;
        }

        createNotification((int) $request->buyer_id, 'Refund Approved', 'Your refund request for ' . $request->product_name . ' was approved. BoomBuy will send ₱' . number_format((float) $request->refund_amount, 2) . ' to your ' . ($request->refund_method ?: 'account') . ' shortly.', 'return_refund', (int) $request->id);

        $this->tellAdmin('Refund to Send', 'Refund #' . $request->id . ' (order #' . $request->order_id . ') was approved. Send the buyer ₱' . number_format((float) $request->refund_amount, 2) . '.', (int) $request->id);
    }

    private function tellSellerToCollect(object $request, ?int $centerId): void
    {
        if (!$request->seller_id) {
            return;
        }

        $center = $centerId ? \App\Models\SortingCenter::find($centerId) : null;

        createNotification(
            (int) $request->seller_id,
            'Collect a Returned Item',
            'The returned ' . $request->product_name . ' (return #' . $request->id . ', order #' . $request->order_id . ') is waiting for you at '
                . ($center ? $center->name . ' (' . ($center->address ?: $center->town) . ')' : 'your Sorting Center') . '.',
            'return_refund',
            (int) $request->id
        );
    }

    private function tellAdmin(string $title, string $message, int $requestId): void
    {
        if ($adminId = SupportAccount::id()) {
            createNotification($adminId, $title, $message, 'return_refund', $requestId);
        }
    }

    /** The request with what the pages and notices need from its order and item. */
    private function find(int $requestId, ?callable $scope = null): object
    {
        $request = DB::table('return_refund_requests')
            ->join('orders', 'orders.id', '=', 'return_refund_requests.order_id')
            ->leftJoin('order_items', 'order_items.id', '=', 'return_refund_requests.order_item_id')
            ->where('return_refund_requests.id', $requestId)
            ->when($scope, fn ($q) => $scope($q))
            ->select(
                'return_refund_requests.*',
                'order_items.product_name',
                'order_items.quantity',
                'orders.origin_center_id',
                'orders.destination_center_id',
                'orders.shipping_province',
                'orders.shipping_city'
            )
            ->first();

        if (!$request) {
            throw new ActionFailed('Return/Refund request not found.');
        }

        $request->product_name ??= 'the item';

        return $request;
    }

    /**
     * Staff of one center act only on returns at (or headed to) it; head
     * office and requests with no known center pass.
     *
     * @throws ActionFailed
     */
    private function mustBeAt($requestCenterId, ?int $staffCenterId, string $otherwise): void
    {
        if ($staffCenterId && $requestCenterId && (int) $requestCenterId !== $staffCenterId) {
            throw new ActionFailed('This return ' . $otherwise . ' (' . ParcelRoute::centerName((int) $requestCenterId) . ').');
        }
    }

    /** Only from the status just checked: a double click can't run a step (or notify) twice. */
    private function move(int $requestId, string $from, string $to, array $extra = []): void
    {
        $moved = DB::table('return_refund_requests')
            ->where('id', $requestId)
            ->where('status', $from)
            ->update(array_merge(['status' => $to, 'updated_at' => now()], $extra));

        if (!$moved) {
            throw new ActionFailed('This request was just updated. Please refresh and try again.');
        }
    }
}
