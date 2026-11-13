<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Support\OrderStatus;
use App\Support\ParcelRoute;
use App\Support\Waybill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Scan-only hub work: a Sorting Center or branch scans a waybill and the
 * parcel takes its next step there — arrival when it comes in (sorted too, at
 * the center that delivers it), the truck out when it leaves — with no button
 * to press. Two scans per hub: in and out. The staff member's center decides
 * whether the parcel belongs here; a wrong center or a repeat scan changes
 * nothing. Each scan can be undone for a short while.
 *
 * Steps that need a person (choosing the delivery rider, the buyer's pickup
 * code, returns) are not done by a scan: it only finds the parcel.
 */
class ParcelScanService
{
    /** Seconds a scan can be undone (the page shows a little less). */
    public const UNDO_SECONDS = 20;

    /** The same parcel scanned again this soon is a double read, not a new step. */
    public const REPEAT_SECONDS = 30;

    public function __construct(private SortingCenterService $centers)
    {
    }

    /** A waybill no. or the label's QR link — not an order no. or a name (those are searches). */
    public static function looksLikeWaybill(string $code): bool
    {
        $code = trim($code);

        return (bool) preg_match('#/logistics/scan/#i', $code)
            || (bool) preg_match('/^BB[\s-]?\d/i', $code)
            || (bool) preg_match('/^[1-9]\d{9}$/', preg_replace('/[\s-]+/', '', $code));
    }

    /**
     * Scan one waybill at this center.
     *
     * @return array{ok: bool, kind: string, message: string, order_id: int, waybill: string, undo: ?string}
     *         kind: done | manual (found, but the next step needs a person)
     *
     * @throws ActionFailed not found, wrong center, repeat scan, or a rule the step broke
     */
    public function scan(string $code, int $centerId, ?string $mode = null): array
    {
        $orderId = Waybill::parse($code);
        $order = $orderId ? DB::table('orders')->where('id', $orderId)->first() : null;

        if (!$order) {
            throw new ActionFailed('No parcel matches "' . mb_substr(trim($code), 0, 60) . '". Check the waybill and scan again.');
        }

        $waybill = Waybill::pretty(Waybill::number($orderId));
        $step = $this->nextStep($order);

        if (!$step) {
            return [
                'ok' => true, 'kind' => 'manual', 'order_id' => $orderId, 'waybill' => $waybill, 'undo' => null,
                'message' => $waybill . ' is ' . OrderStatus::label($order->status) . ' — ' . $this->manualHint($order),
            ];
        }

        [$expectedCenter, $run] = $step;

        if ($expectedCenter && (int) $expectedCenter !== $centerId) {
            throw new ActionFailed('Not for this center — ' . $waybill . ' goes to ' . (ParcelRoute::centerName((int) $expectedCenter) ?? 'another center') . '. Nothing was changed.');
        }

        // The Scan Station's IN / OUT switch: a parcel scanned in the wrong mode is left as is.
        $dir = $this->direction((string) $order->status);

        if ($mode && $dir && $mode !== $dir) {
            throw new ActionFailed($dir === 'out'
                ? 'Wrong mode — ' . $waybill . ' is already here and waiting to leave. Switch to Leaving (OUT) and scan again. Nothing was changed.'
                : 'Wrong mode — ' . $waybill . ' has not been received here yet. Switch to Arriving (IN) and scan again. Nothing was changed.');
        }

        $last = session('scan_last');

        if (is_array($last) && (int) $last['order_id'] === $orderId && now()->timestamp - (int) $last['at'] < self::REPEAT_SECONDS) {
            throw new ActionFailed('Already scanned — ' . $waybill . ' is ' . OrderStatus::label($order->status) . '. Nothing was changed.');
        }

        // Kept so the scan can be undone: the parcel as it was, and where its
        // timeline and notifications stood.
        $before = (array) $order;
        $eventsBefore = (int) DB::table('order_events')->max('id');
        $notificationsBefore = (int) DB::table('notifications')->max('id');

        $message = DB::transaction(fn () => $run($centerId));

        $after = DB::table('orders')->where('id', $orderId)->first();
        $token = Str::random(20);

        session()->put('scan_last', ['order_id' => $orderId, 'at' => now()->timestamp]);
        session()->put('scan_undo.' . $token, [
            'order_id' => $orderId,
            'center_id' => $centerId,
            'before' => $before,
            'after_updated_at' => (string) $after->updated_at,
            'events_before' => $eventsBefore,
            'notifications_before' => $notificationsBefore,
            'expires_at' => now()->addSeconds(self::UNDO_SECONDS)->timestamp,
        ]);

        return [
            'ok' => true, 'kind' => 'done', 'order_id' => $orderId, 'waybill' => $waybill, 'undo' => $token, 'dir' => $dir,
            'message' => $waybill . ' → ' . OrderStatus::label($after->status) . $this->where($after) . '. ' . $this->next($message),
        ];
    }

    /** in = a parcel arriving here, out = one leaving; null = not a scan step. */
    public function direction(string $status): ?string
    {
        return match ($status) {
            'Picked Up', 'Dropped Off', 'To Sorting Center', 'In Transit', 'To Branch' => 'in',
            'Received at Branch', 'At Sorting Center', 'Sorted' => 'out',
            default => null,
        };
    }

    /**
     * Put the parcel back as it was before the scan.
     *
     * @throws ActionFailed too late, or the parcel moved on since
     */
    public function undo(string $token, int $centerId): string
    {
        $saved = session('scan_undo.' . $token);

        if (!is_array($saved) || (int) $saved['center_id'] !== $centerId) {
            throw new ActionFailed('That scan can no longer be undone.');
        }

        session()->forget('scan_undo.' . $token);

        if (now()->timestamp > (int) $saved['expires_at']) {
            throw new ActionFailed('Too late to undo that scan — use the parcel\'s buttons below instead.');
        }

        $orderId = (int) $saved['order_id'];

        DB::transaction(function () use ($saved, $orderId) {
            $now = DB::table('orders')->where('id', $orderId)->lockForUpdate()->first();

            if (!$now || (string) $now->updated_at !== $saved['after_updated_at']) {
                throw new ActionFailed('Can\'t undo — the parcel was updated again since that scan.');
            }

            $restore = $saved['before'];
            unset($restore['id']);

            DB::table('orders')->where('id', $orderId)->update($restore);
            DB::table('order_events')->where('order_id', $orderId)->where('id', '>', $saved['events_before'])->delete();
            DB::table('notifications')->where('reference_id', $orderId)->where('id', '>', $saved['notifications_before'])->delete();
        });

        session()->forget('scan_last');

        return 'Undone — ' . Waybill::pretty(Waybill::number($orderId)) . ' is back to ' . OrderStatus::label((string) $saved['before']['status']) . '.';
    }

    /**
     * What a scan does to this parcel next, and at which center it must be:
     * [center id, fn(int $centerId): string], or null when no scan step applies.
     */
    private function nextStep(object $order): ?array
    {
        $id = (int) $order->id;
        $c = $this->centers;

        return match ($order->status) {
            // Arriving from the pickup rider (or the seller's branch truck).
            'Picked Up', 'Dropped Off', 'To Sorting Center' => [
                $order->status === 'Picked Up' && !empty($order->origin_branch_id) ? $order->origin_branch_id : $order->origin_center_id,
                fn (int $here) => $this->sortIfDeliveredHere($id, $here, $c->confirmReceived($id, $here)),
            ],
            // Leaving the seller's branch for its Sorting Center.
            'Received at Branch' => [$order->current_center_id, fn (int $here) => $c->sendToCenter($id, $here)],
            // At a center: on to the buyer's center, or sorted and onto the branch truck.
            'At Sorting Center' => [$order->current_center_id, function (int $here) use ($c, $id, $order) {
                if ($c->needsTransfer($order)) {
                    return $c->dispatch($id, $here);
                }

                $message = $c->sort($id, $here);
                $now = DB::table('orders')->where('id', $id)->first();

                return $now->status === 'Sorted' && !empty($now->branch_id) ? $c->dispatchToBranch($id, $here) : $message;
            }],
            'Sorted' => !empty($order->branch_id)
                ? [$order->current_center_id, fn (int $here) => $c->dispatchToBranch($id, $here)]
                : null,
            // Arriving at the buyer's center, or at the buyer's town branch.
            'In Transit' => [$order->next_center_id ?: $order->destination_center_id, fn (int $here) => $this->sortIfDeliveredHere($id, $here, $c->confirmArrival($id, $here))],
            'To Branch' => [$order->branch_id, fn (int $here) => $c->confirmAtBranch($id, $here)],
            default => null,
        };
    }

    /**
     * The arrival scan at the center that delivers it also sorts it: sorting
     * only reads the buyer's address (area, branch), so a hub needs just two
     * scans — in and out. A parcel going on to another center is left as is.
     */
    private function sortIfDeliveredHere(int $id, int $here, string $arrived): string
    {
        $now = DB::table('orders')->where('id', $id)->first();

        if ($now->status !== 'At Sorting Center' || $this->centers->needsTransfer($now)) {
            return $arrived;
        }

        return $this->centers->sort($id, $here);
    }

    /** Found, but not a scan step: what to do instead. */
    private function manualHint(object $order): string
    {
        return match ($order->status) {
            'Pending', 'Confirmed', 'Preparing', 'Processing', 'Ready for Pickup', 'Pickup Assigned' => 'still with the seller, not picked up yet.',
            'Sorted', 'At Branch' => 'assign the delivery rider below.',
            'Ready to Collect' => 'hand it over below with the buyer\'s pickup code.',
            'Assigned for Delivery', 'Out for Delivery' => 'already with the delivery rider.',
            'Delivered', 'Completed' => 'already delivered.',
            default => 'open it below for its next step.',
        };
    }

    private function where(object $order): string
    {
        $at = $order->current_center_id ? ParcelRoute::centerName((int) $order->current_center_id) : null;

        return match ($order->status) {
            'In Transit' => ($order->next_center_id ?: $order->destination_center_id) ? ' (to ' . ParcelRoute::centerName((int) ($order->next_center_id ?: $order->destination_center_id)) . ')' : '',
            'To Branch' => $order->branch_id ? ' (to ' . ParcelRoute::centerName((int) $order->branch_id) . ')' : '',
            'To Sorting Center' => $order->origin_center_id ? ' (to ' . ParcelRoute::centerName((int) $order->origin_center_id) . ')' : '',
            default => $at ? ' at ' . $at : '',
        };
    }

    /** The service's own "Next: …" hint, if it gave one. */
    private function next(string $message): string
    {
        return preg_match('/(Next: .*)$/', $message, $m) ? $m[1] : '';
    }
}
