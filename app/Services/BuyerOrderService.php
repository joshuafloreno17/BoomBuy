<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Support\AutoReceive;
use App\Support\ChatAutomation;
use App\Support\CodPolicy;
use App\Support\OrderStock;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * What a buyer does with their own order: cancel it (Cash on Delivery, while
 * it is still with the seller) and ask for a return or refund after receiving it.
 */
class BuyerOrderService
{
    /** Return/refund requests that still block a new one for the same item. */
    private const OPEN_REQUEST_STATUSES = ReturnRefundService::OPEN;

    /**
     * @return string  What happened, for the buyer.
     *
     * @throws ActionFailed
     */
    public function cancel(int $buyerId, int $orderId, string $reason, string $details = ''): string
    {
        $order = $this->buyerOrder($buyerId, $orderId);

        // Paid orders can't be cancelled by the buyer once checked out; COD
        // orders only until the seller hands them over (Pending/Processing).
        if (!CodPolicy::isCod($order->payment_method)) {
            throw new ActionFailed('Paid orders can no longer be cancelled. You can request a return once you receive it.');
        }

        if (!CodPolicy::buyerCanCancel($order)) {
            throw new ActionFailed('This order can no longer be cancelled — it is already on its way. You can refuse the parcel on delivery or request a return after receiving it.');
        }

        $reason = trim($reason);
        $details = trim($details);

        if (!in_array($reason, CodPolicy::CANCEL_REASONS, true)) {
            throw new ActionFailed('Please choose a reason for cancelling.');
        }

        if ($reason === 'Other' && $details === '') {
            throw new ActionFailed('Please tell us why you are cancelling.');
        }

        $reasonText = $reason === 'Other'
            ? Str::limit($details, 250)
            : $reason . ($details !== '' ? ' — ' . Str::limit($details, 200) : '');

        // Conditional on the status just checked, so a double click (or the
        // seller moving it on at the same moment) can't cancel/restock twice.
        $cancelled = DB::table('orders')
            ->where('id', $orderId)
            ->where('status', $order->status)
            ->update([
                'status' => 'Cancelled',
                'cancellation_reason' => 'Cancelled by buyer: ' . $reasonText,
                'cancelled_by' => 'buyer',
                'cancelled_at' => now(),
                'updated_at' => now(),
            ]);

        if (!$cancelled) {
            throw new ActionFailed('This order was just updated by the seller. Please refresh and try again.');
        }

        \App\Support\OrderTimeline::log($orderId, 'Cancelled', 'Cancelled by you', $reasonText);

        OrderStock::cancelled($orderId, $order->status);
        ChatAutomation::orderUpdate($orderId, 'cancelled');

        $stage = $order->status === 'Processing' ? 'while you were preparing it' : 'before processing';

        foreach (DB::table('order_items')->where('order_id', $orderId)->distinct()->pluck('seller_id') as $sellerId) {
            createNotification(
                $sellerId,
                'Order Cancelled by Buyer',
                "Order #{$orderId} was cancelled by the buyer {$stage}. Reason: {$reasonText}. The items were returned to your stock.",
                'order_status',
                $orderId
            );
        }

        $message = 'Your order has been cancelled.';
        $cod = CodPolicy::status($buyerId);

        if ($cod['blocked']) {
            $message .= ' Cash on Delivery is paused on your account until '
                . $cod['available_at']->format('M d, Y')
                . ' because of repeated cancellations.';
        }

        return $message;
    }

    /**
     * The buyer confirms a delivered order arrived (AutoReceive does it for
     * them after a few days). Starts the return window.
     *
     * @throws ActionFailed
     */
    public function markReceived(int $buyerId, int $orderId): string
    {
        $order = $this->buyerOrder($buyerId, $orderId);

        if ($order->status !== 'Delivered') {
            throw new ActionFailed('You can only confirm an order after it has been delivered.');
        }

        if (!empty($order->buyer_received_at)) {
            throw new ActionFailed('This order has already been marked as received.');
        }

        DB::table('orders')->where('id', $orderId)->update([
            'buyer_received_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Support\OrderTimeline::log($orderId, 'Delivered', 'You confirmed you received it');

        foreach (DB::table('order_items')->where('order_id', $orderId)->distinct()->pluck('seller_id') as $sellerId) {
            createNotification(
                $sellerId,
                'Order Received by Buyer',
                "Order #{$orderId} has been confirmed as received by the buyer.",
                'order_status',
                $orderId
            );
        }

        return 'Order received successfully!';
    }

    /**
     * A return or refund request for one item of a delivered, received order,
     * within the return window.
     *
     * @throws ActionFailed
     * @throws \Illuminate\Validation\ValidationException  when the evidence isn't an image under 4 MB
     */
    public function requestReturn(
        int $buyerId,
        int $orderId,
        int $orderItemId,
        string $type,
        string $reason,
        string $message = '',
        ?UploadedFile $evidence = null,
        array $refundTo = []
    ): string {
        $order = $this->buyerOrder($buyerId, $orderId);

        if ($order->status !== 'Delivered') {
            throw new ActionFailed('Only delivered orders can be returned or refunded.');
        }

        if (empty($order->buyer_received_at)) {
            throw new ActionFailed('Please confirm that you received the order before requesting a return or refund.');
        }

        $windowDays = AutoReceive::RETURN_WINDOW_DAYS;

        if (Carbon::parse($order->buyer_received_at)->addDays($windowDays)->isPast()) {
            throw new ActionFailed('The ' . $windowDays . '-day return/refund window for this order has passed.');
        }

        $type = trim($type);
        $reason = trim($reason);
        $message = trim($message);

        if (!in_array($type, ['Return', 'Refund'], true)) {
            throw new ActionFailed('Invalid request type.');
        }

        if ($reason === '') {
            throw new ActionFailed('Please select a reason.');
        }

        // Where BoomBuy sends the money back.
        $refundMethod = trim((string) ($refundTo['method'] ?? ''));
        $refundName = trim((string) ($refundTo['account_name'] ?? ''));
        $refundNumber = trim((string) ($refundTo['account_number'] ?? ''));

        if (!in_array($refundMethod, ReturnRefundService::REFUND_METHODS, true)) {
            throw new ActionFailed('Choose where we should send your refund (GCash, Maya or bank transfer).');
        }

        if ($refundName === '' || $refundNumber === '') {
            throw new ActionFailed('Enter the account name and number for your refund.');
        }

        if ($refundMethod !== 'Bank transfer' && !preg_match('/^(09|\+639)\d{9}$/', preg_replace('/[\s-]/', '', $refundNumber))) {
            throw new ActionFailed('Enter the ' . $refundMethod . ' mobile number, e.g. 09171234567.');
        }

        $item = DB::table('order_items')->where('id', $orderItemId)->where('order_id', $orderId)->first();

        if (!$item) {
            throw new ActionFailed('Order item not found.');
        }

        $alreadyOpen = DB::table('return_refund_requests')
            ->where('order_id', $orderId)
            ->where('order_item_id', $orderItemId)
            ->whereIn('status', self::OPEN_REQUEST_STATUSES)
            ->exists();

        if ($alreadyOpen) {
            throw new ActionFailed('A return/refund request already exists for this item.');
        }

        $evidencePath = null;

        if ($evidence) {
            Validator::make(['evidence' => $evidence], ['evidence' => 'image|max:4096'])->validate();
            $evidencePath = $evidence->store('return-evidence', 'local');
        }

        DB::table('return_refund_requests')->insert([
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'buyer_id' => $buyerId,
            'seller_id' => $item->seller_id,
            'request_type' => $type,
            'reason' => $reason,
            'message' => $message ?: null,
            'evidence' => $evidencePath,
            'status' => 'pending',
            'refund_amount' => self::refundAmount($order, $item),
            'refund_method' => $refundMethod,
            'refund_account_name' => mb_substr($refundName, 0, 120),
            'refund_account_number' => mb_substr($refundNumber, 0, 60),
            'seller_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!empty($item->seller_id)) {
            createNotification(
                (int) $item->seller_id,
                'New Return / Refund Request',
                'A buyer submitted a ' . strtolower($type) . ' request for Order #' . $orderId . '.',
                'return_refund',
                $orderId
            );
        }

        return 'Your ' . strtolower($type) . ' request has been submitted successfully.';
    }

    /**
     * What the buyer gets back for one line: what they paid for it — its
     * price less its share of the order's voucher discount. The delivery
     * fee isn't refunded.
     */
    public static function refundAmount(object $order, object $item): float
    {
        $line = (float) $item->price * (int) $item->quantity;
        $discount = (float) ($order->discount_amount ?? 0);

        if ($discount <= 0) {
            return round($line, 2);
        }

        $subtotal = (float) DB::table('order_items')->where('order_id', $order->id)->sum(DB::raw('price * quantity'));

        return $subtotal > 0 ? round(max(0, $line - $discount * $line / $subtotal), 2) : round($line, 2);
    }

    private function buyerOrder(int $buyerId, int $orderId): object
    {
        $order = DB::table('orders')->where('id', $orderId)->where('buyer_id', $buyerId)->first();

        if (!$order) {
            throw new ActionFailed('Order not found.');
        }

        return $order;
    }
}
