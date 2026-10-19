<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Support\AuditLog;
use App\Support\OrderTimeline;
use App\Support\Payments;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Logistics fixes and exceptions (L5, A35): a manual status override with
 * a reason (logged, never editable), and parcels Lost or Damaged (both
 * sides told; a paid order is refunded, a COD one goes to a claim).
 */
class ShipmentExceptionService
{
    /** Where a stuck parcel may be set by hand, from where. */
    public const OVERRIDES = [
        'Delivered' => ['Out for Delivery', 'Assigned for Delivery'],
        'Out for Delivery' => ['Assigned for Delivery'],
        'At Sorting Center' => ['Picked Up', 'In Transit', 'To Sorting Center'],
    ];

    /** @throws ActionFailed */
    public function override(int $orderId, string $status, string $reason, int $userId, ?int $centerId = null): string
    {
        $order = $this->parcel($orderId, $centerId);
        $reason = $this->reason($reason);

        if (!isset(self::OVERRIDES[$status]) || !in_array($order->status, self::OVERRIDES[$status], true)) {
            throw new ActionFailed('A parcel that is ' . $order->status . ' can\'t be set to ' . $status . ' by hand.');
        }

        $extra = match ($status) {
            'Delivered' => [
                'delivered_at' => now(),
                'rider_fee' => \App\Support\DeliveryFee::baseFee(),
                'cod_collected_at' => \App\Support\CodPolicy::isCod((string) $order->payment_method) ? now() : null,
                'cod_amount_collected' => \App\Support\CodPolicy::isCod((string) $order->payment_method) ? round((float) $order->total_amount, 2) : null,
            ],
            'At Sorting Center' => ['sorting_center_received_at' => now()],
            default => [],
        };

        $updated = DB::table('orders')->where('id', $orderId)->where('status', $order->status)->update(['status' => $status, 'updated_at' => now()] + $extra);

        if (!$updated) {
            throw new ActionFailed('This parcel was just updated. Please refresh and try again.');
        }

        OrderTimeline::log($orderId, $status, 'Status set by logistics', $reason);
        AuditLog::record('shipment.override', 'order', $orderId, ['from' => $order->status, 'to' => $status, 'reason' => $reason], $userId);
        createNotification((int) $order->buyer_id, $status === 'Delivered' ? 'Order Delivered' : 'Order Update', 'Order #' . $orderId . ' is now ' . $status . '.', 'order', $orderId);

        return 'Parcel #' . $orderId . ' set to ' . $status . '.';
    }

    /** @throws ActionFailed */
    public function lost(int $orderId, string $reason, int $userId, ?int $centerId = null): string
    {
        $order = $this->parcel($orderId, $centerId);

        return $this->exception($order, 'Lost', $this->reason($reason), null, $userId);
    }

    /** @throws ActionFailed */
    public function damaged(int $orderId, string $reason, ?UploadedFile $photo, int $userId, ?int $centerId = null): string
    {
        $order = $this->parcel($orderId, $centerId);

        if (!$photo || !isValidProductImage($photo)) {
            throw new ActionFailed('Attach a photo of the damage (JPG or PNG, up to 5 MB).');
        }

        return $this->exception($order, 'Damaged', $this->reason($reason), $photo->store('parcel-exceptions', 'local'), $userId);
    }

    private function exception(object $order, string $status, string $reason, ?string $photo, int $userId): string
    {
        if (in_array($order->status, \App\Support\OrderStatus::FINISHED, true) || in_array($order->status, ['Delivered', 'Lost', 'Damaged'], true)) {
            throw new ActionFailed('This parcel is already ' . $order->status . '.');
        }

        $updated = DB::table('orders')->where('id', $order->id)->where('status', $order->status)->update([
            'status' => $status, 'exception_reason' => $reason, 'exception_photo' => $photo, 'exception_at' => now(), 'updated_at' => now(),
        ]);

        if (!$updated) {
            throw new ActionFailed('This parcel was just updated. Please refresh and try again.');
        }

        $orderId = (int) $order->id;
        OrderTimeline::log($orderId, $status, 'Parcel ' . strtolower($status), $reason);
        AuditLog::record('shipment.' . strtolower($status), 'order', $orderId, ['from' => $order->status, 'to' => $status, 'reason' => $reason, 'photo' => $photo], $userId);

        // Paid online: refund now. COD: nothing was paid; the seller's claim goes to BoomBuy.
        $refund = Payments::startRefund($orderId);
        createNotification((int) $order->buyer_id, 'Parcel ' . $status, 'Order #' . $orderId . ' was reported ' . strtolower($status) . ' (' . $reason . ').' . ($refund ? ' ' . $refund : ' You will not be charged for it.'), 'order', $orderId);
        notifyOrderSellers($orderId, 'Parcel ' . $status, 'Order #' . $orderId . ' was reported ' . strtolower($status) . ' by logistics (' . $reason . '). A claim for it was opened with BoomBuy.');

        return 'Parcel #' . $orderId . ' marked ' . $status . '.' . ($refund ? ' Refund started.' : ' Claim opened.');
    }

    private function reason(string $reason): string
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 300) {
            throw new ActionFailed('Please give a reason of 10 to 300 characters.');
        }

        return $reason;
    }

    private function parcel(int $orderId, ?int $centerId): object
    {
        $order = DB::table('orders')->where('id', $orderId)->first();

        if (!$order) {
            throw new ActionFailed('Parcel not found.');
        }

        if ($centerId && !in_array($centerId, array_map('intval', array_filter([$order->origin_center_id, $order->destination_center_id, $order->current_center_id, $order->branch_id])), true)) {
            throw new ActionFailed('This parcel belongs to another logistics company.');
        }

        return $order;
    }
}
