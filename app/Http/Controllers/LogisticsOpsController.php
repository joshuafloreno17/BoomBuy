<?php

namespace App\Http\Controllers;

use App\Exceptions\ActionFailed;
use App\Services\CodRemittanceService;
use App\Services\DispatchService;
use App\Services\ShipmentExceptionService;
use App\Services\SortingCenterService;
use App\Support\AuditLog;
use App\Support\RiderJobs;
use App\Support\ShippingZones;
use App\Support\Shipments;
use App\Support\Waybill;
use Illuminate\Support\Facades\DB;

/**
 * Logistics Modules L1–L8 for a logistics company: shipments and counts,
 * dispatch (offers, manual and bulk assignment, reassignment), hub scans,
 * exceptions, Return to Sender, COD reconciliation, exports, Zones & Rates,
 * rider suspension and the audit log. Everything is scoped to the
 * company's Sorting Center (head office sees all).
 */
class LogisticsOpsController extends Controller
{
    /** The company's center id; null = head office; false = not assigned yet. */
    private function center(array $user): int|null|false
    {
        $row = DB::table('users')->where('id', $user['id'])->first(['sorting_center_id', 'is_head_office']);

        if ($row && $row->sorting_center_id) {
            return (int) $row->sorting_center_id;
        }

        return $row && $row->is_head_office ? null : false;
    }

    /** Runs an action for a logged-in, assigned logistics user and flashes its message. */
    private function act(callable $action)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $center = $this->center($user);

        if ($center === false) {
            return back()->with('error', 'You are not assigned to a Sorting Center yet. Ask the BoomBuy admin to assign you.');
        }

        try {
            return back()->with('success', $action($user, $center));
        } catch (ActionFailed $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /* ---------------- Shipments, dispatch, hub (one page) ---------------- */

    public function index(DispatchService $dispatch)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $center = $this->center($user);
        $center = $center === false ? -1 : $center;
        $dispatch->sweep();

        $from = request('from') ?: null;
        $to = request('to') ?: null;
        $bucket = request('status');
        $search = (string) request('q', '');

        $riders = DB::table('users')
            ->join('rider_applications', 'rider_applications.user_id', '=', 'users.id')
            ->where('users.role', 'rider')
            ->where('rider_applications.status', 'Approved')
            ->select('users.id', 'users.name', 'users.status', 'users.is_online')
            ->distinct()
            ->get()
            ->filter(fn ($r) => RiderJobs::worksFor((int) $r->id, $center ?: null))
            ->map(function ($r) {
                $r->active_jobs = RiderJobs::activeCount((int) $r->id);
                $r->delivered_today = DB::table('orders')->where('delivery_rider_id', $r->id)->whereDate('delivered_at', today())->count();
                $r->assignable = $r->status === 'Active' && $r->is_online && $r->active_jobs < RiderJobs::ACTIVE_LIMIT;

                return $r;
            })
            ->values();

        $readyForPickup = DB::table('orders')->where('status', 'Ready for Pickup')->whereNull('rider_id')
            ->when($center, fn ($q) => $q->where('origin_center_id', $center))->orderBy('id')->get();

        return view('pages.logistics.operations', [
            'user' => $user,
            'counts' => Shipments::counts($center ?: null, $from, $to),
            'shipments' => Shipments::list($center ?: null, $bucket, $from, $to, $search),
            'filters' => ['from' => $from, 'to' => $to, 'status' => $bucket, 'q' => $search],
            'riders' => $riders,
            'assignableRiders' => $riders->where('assignable', true)->values(),
            'readyForPickup' => $readyForPickup,
            'assignedPickups' => DB::table('orders')->where('status', 'Pickup Assigned')->when($center, fn ($q) => $q->where('origin_center_id', $center))->orderBy('id')->get(),
            'offers' => DB::table('job_offers')->where('status', 'pending')->whereIn('order_id', $readyForPickup->pluck('id'))->get()->keyBy('order_id'),
            'unassigned' => $dispatch->unassigned($center ?: null),
            'zoneLists' => Shipments::zoneLists($center ?: null),
            'rtsQueue' => Shipments::rtsQueue($center ?: null),
            'returnReady' => DB::table('orders')->where('status', 'Return Ready')->when($center, fn ($q) => $q->where('current_center_id', $center))->get(),
        ]);
    }

    public function autoAssign($id, DispatchService $dispatch)
    {
        return $this->act(fn ($user, $center) => $dispatch->autoAssign((int) $id, $center));
    }

    public function assign($id, DispatchService $dispatch)
    {
        return $this->act(fn ($user, $center) => $dispatch->assign((int) $id, request('rider_id'), $center));
    }

    public function reassign($id, DispatchService $dispatch)
    {
        return $this->act(fn ($user, $center) => $dispatch->reassign((int) $id, request('rider_id'), $center));
    }

    public function bulkAssign(DispatchService $dispatch)
    {
        return $this->act(function ($user, $center) use ($dispatch) {
            $result = $dispatch->bulkAssign((array) request('order_ids', []), request('rider_id'), $center);
            session()->flash('bulk_result', $result);

            return count($result['assigned']) . ' assigned' . ($result['skipped'] ? ', ' . count($result['skipped']) . ' skipped: ' . collect($result['skipped'])->map(fn ($why, $id) => '#' . $id . ' (' . $why . ')')->implode('; ') : '') . '.';
        });
    }

    /** Hub scan: Inbound (received at the hub) or Outbound (sorted and handed to a rider). */
    public function scan(SortingCenterService $centers)
    {
        return $this->act(function ($user, $center) use ($centers) {
            $code = trim((string) request('code'));
            $orderId = Waybill::parse($code);
            $order = $orderId ? DB::table('orders')->where('id', $orderId)->first() : null;

            if (!$order) {
                throw new ActionFailed('Parcel "' . $code . '" not found.');
            }

            $ours = array_map('intval', array_filter([$order->origin_center_id, $order->destination_center_id, $order->current_center_id, $order->branch_id, $order->origin_branch_id]));

            if ($center && !in_array($center, $ours, true)) {
                throw new ActionFailed('Parcel ' . Waybill::number($orderId) . ' belongs to another logistics company.');
            }

            $received = ['At Sorting Center', 'Sorted', 'Assigned for Delivery', 'Out for Delivery', 'In Transit', 'To Branch', 'At Branch'];

            if (request('stage') === 'outbound') {
                if (in_array($order->status, ['Picked Up', 'Dropped Off', 'To Sorting Center'], true)) {
                    throw new ActionFailed('Scan parcel ' . Waybill::number($orderId) . ' at Inbound first.');
                }

                if ($order->status === 'At Sorting Center') {
                    $centers->sort($orderId, $center);
                }

                return $centers->assign($orderId, request('rider_id'), $center);
            }

            if (in_array($order->status, $received, true)) {
                throw new ActionFailed('Parcel ' . Waybill::number($orderId) . ' was already received at the hub.');
            }

            return $centers->confirmReceived($orderId, $center);
        });
    }

    public function override($id, ShipmentExceptionService $exceptions)
    {
        return $this->act(fn ($user, $center) => $exceptions->override((int) $id, (string) request('status'), (string) request('reason'), (int) $user['id'], $center));
    }

    public function lost($id, ShipmentExceptionService $exceptions)
    {
        return $this->act(fn ($user, $center) => $exceptions->lost((int) $id, (string) request('reason'), (int) $user['id'], $center));
    }

    public function damaged($id, ShipmentExceptionService $exceptions)
    {
        return $this->act(fn ($user, $center) => $exceptions->damaged((int) $id, (string) request('reason'), request()->file('photo'), (int) $user['id'], $center));
    }

    public function returnRider($id, SortingCenterService $centers)
    {
        return $this->act(fn ($user, $center) => $centers->assignReturnRider((int) $id, request('rider_id'), $center));
    }

    /* ---------------- Riders: suspend / reactivate ---------------- */

    public function suspendRider($riderId)
    {
        return $this->act(function ($user, $center) use ($riderId) {
            $rider = $this->ownRider($user, (int) $riderId);
            $reason = trim((string) request('reason'));

            if (mb_strlen($reason) < 10 || mb_strlen($reason) > 300) {
                throw new ActionFailed('Please give a reason of 10 to 300 characters.');
            }

            if ($rider->status === 'Suspended') {
                throw new ActionFailed($rider->name . ' is already suspended.');
            }

            DB::table('users')->where('id', $rider->id)->update(['status' => 'Suspended', 'is_online' => false, 'online_at' => null, 'updated_at' => now()]);
            DB::table('rider_applications')->where('user_id', $rider->id)->update(['suspension_reason' => $reason]);

            // Not-yet-picked-up pickups go to the Unassigned queue; picked-up parcels stay with the rider.
            $pickups = DB::table('orders')->where('rider_id', $rider->id)->where('status', 'Pickup Assigned')->pluck('id');
            DB::table('orders')->whereIn('id', $pickups)->update(['status' => 'Ready for Pickup', 'rider_id' => null, 'dispatch_unassigned_at' => now(), 'updated_at' => now()]);
            DB::table('job_offers')->where('rider_id', $rider->id)->where('status', 'pending')->update(['status' => 'cancelled', 'updated_at' => now()]);

            AuditLog::record('rider.suspended', 'rider', (int) $rider->id, ['from' => $rider->status, 'to' => 'Suspended', 'reason' => $reason, 'unassigned_jobs' => $pickups->all()], (int) $user['id']);
            createNotification((int) $rider->id, 'Account Suspended', 'Your rider account was suspended by your logistics company. Reason: ' . $reason, 'account_status');

            return $rider->name . ' was suspended.' . ($pickups->isNotEmpty() ? ' ' . $pickups->count() . ' pickup(s) moved to the Unassigned queue.' : '');
        });
    }

    public function reactivateRider($riderId)
    {
        return $this->act(function ($user, $center) use ($riderId) {
            $rider = $this->ownRider($user, (int) $riderId);

            if ($rider->status === 'Active') {
                throw new ActionFailed($rider->name . ' is already active.');
            }

            DB::table('users')->where('id', $rider->id)->update(['status' => 'Active', 'updated_at' => now()]);
            DB::table('rider_applications')->where('user_id', $rider->id)->update(['suspension_reason' => null]);
            AuditLog::record('rider.reactivated', 'rider', (int) $rider->id, ['from' => $rider->status, 'to' => 'Active'], (int) $user['id']);
            createNotification((int) $rider->id, 'Account Reactivated', 'Your rider account is active again. You can go Online.', 'account_status');

            return $rider->name . ' is active again.';
        });
    }

    /** A rider this company added (or, for riders who applied, of its area). */
    private function ownRider(array $user, int $riderId): object
    {
        $rider = DB::table('users')->where('id', $riderId)->where('role', 'rider')->first();
        $createdBy = DB::table('rider_applications')->where('user_id', $riderId)->latest('id')->value('created_by');
        $center = $this->center($user);

        if (!$rider || ($createdBy && (int) $createdBy !== (int) $user['id'] && $center !== null) || (!$createdBy && !RiderJobs::worksFor($riderId, $center ?: null))) {
            throw new ActionFailed('This rider belongs to another logistics company.');
        }

        return $rider;
    }

    /* ---------------- COD reconciliation & reports ---------------- */

    public function cod(CodRemittanceService $cod)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $center = $this->center($user);
        $date = request('date') ?: today()->toDateString();
        $rows = $center === false ? collect() : $cod->reconciliation($center ?: null, $date);

        return view('pages.logistics.cod', [
            'user' => $user,
            'date' => $date,
            'rows' => $rows,
            'pending' => DB::table('cod_remittances')->where('status', 'Pending')->get()->filter(fn ($r) => $center !== false && RiderJobs::worksFor((int) $r->rider_id, $center ?: null))->values(),
            'from' => request('from') ?: today()->subDays(7)->toDateString(),
            'to' => request('to') ?: today()->toDateString(),
        ]);
    }

    public function confirmRemittance($id, CodRemittanceService $cod)
    {
        // Only the logistics company confirms (a rider gets 403, A22/A24).
        abort_unless((session('user')['role'] ?? null) === 'logistics', 403);

        return $this->act(fn ($user, $center) => $cod->confirm((int) $id, (int) $user['id'], $center));
    }

    /** Rows of a report, the same ones the screen lists for the filter. */
    public static function reportRows(string $type, ?int $center, string $from, string $to): array
    {
        if ($type === 'cod') {
            $orders = DB::table('orders')->whereNotNull('cod_collected_at')
                ->whereDate('cod_collected_at', '>=', $from)->whereDate('cod_collected_at', '<=', $to)
                ->orderBy('cod_collected_at')->get()
                ->filter(fn ($o) => RiderJobs::worksFor((int) $o->delivery_rider_id, $center));

            return [
                ['Tracking number', 'Order', 'Rider', 'COD collected', 'Collected at', 'Remitted at'],
                $orders->map(fn ($o) => [Waybill::number((int) $o->id), (int) $o->id, (string) DB::table('users')->where('id', $o->delivery_rider_id)->value('name'),
                    round((float) ($o->cod_amount_collected ?? $o->total_amount), 2), (string) $o->cod_collected_at, (string) ($o->cod_remitted_at ?? '')])->values()->all(),
            ];
        }

        $orders = Shipments::query($center, null, null)->whereIn('orders.status', Shipments::BUCKETS['delivered']['statuses'])
            ->whereDate('orders.delivered_at', '>=', $from)->whereDate('orders.delivered_at', '<=', $to)
            ->orderBy('orders.delivered_at')->get(['orders.*']);

        return [
            ['Tracking number', 'Order', 'Buyer', 'Rider', 'Delivered at', 'Order total'],
            $orders->map(fn ($o) => [Waybill::number((int) $o->id), (int) $o->id, (string) $o->shipping_name, (string) DB::table('users')->where('id', $o->delivery_rider_id)->value('name'),
                (string) $o->delivered_at, round((float) $o->total_amount, 2)])->values()->all(),
        ];
    }

    public function export()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $center = $this->center($user);
        abort_if($center === false, 403);

        $type = request('type') === 'cod' ? 'cod' : 'deliveries';
        $format = request('format') === 'xlsx' ? 'xlsx' : 'csv';
        $from = request('from') ?: today()->toDateString();
        $to = request('to') ?: today()->toDateString();
        [$headers, $rows] = self::reportRows($type, $center ?: null, $from, $to);
        $name = 'boombuy-' . $type . '-' . $from . '-to-' . $to . '.' . $format;

        if ($format === 'xlsx') {
            return response(\App\Support\XlsxWriter::build($headers, $rows, ucfirst($type)), 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $name . '"',
            ]);
        }

        $out = fopen('php://temp', 'r+');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="' . $name . '"']);
    }

    /* ---------------- Zones & Rates ---------------- */

    public function rates()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $zones = collect(ShippingZones::all())->keyBy('id');

        return view('pages.logistics.rates', [
            'user' => $user,
            'zones' => $zones,
            'rates' => DB::table('shipping_rates')->orderBy('from_zone_id')->orderBy('to_zone_id')->get(),
            'provinces' => \App\Support\PhLocations::provincesByRegionName(),
        ]);
    }

    public function updateRate($id)
    {
        return $this->act(function ($user) use ($id) {
            $rate = DB::table('shipping_rates')->find($id);

            if (!$rate) {
                throw new ActionFailed('Rate not found.');
            }

            foreach (['first_kg' => 'The first-kg rate', 'per_kg' => 'The each-additional-kg rate'] as $field => $label) {
                if ($error = ShippingZones::rateError(request($field), $label)) {
                    throw new ActionFailed($error);
                }
            }

            $new = ['first_kg' => round((float) request('first_kg'), 2), 'per_kg' => round((float) request('per_kg'), 2)];
            DB::table('shipping_rates')->where('id', $id)->update($new + ['updated_at' => now()]);
            AuditLog::record('rate.changed', 'shipping_rate', (int) $id, ['from' => ['first_kg' => (float) $rate->first_kg, 'per_kg' => (float) $rate->per_kg], 'to' => $new], (int) $user['id']);

            return 'Rate saved. New checkouts use it; orders already placed keep their fee.';
        });
    }

    public function storeZone()
    {
        return $this->act(function ($user) {
            $name = trim((string) request('name'));
            $provinces = array_values(array_intersect((array) request('provinces', []), collect(\App\Support\PhLocations::provincesByRegionName())->flatten()->all()));

            if ($name === '' || mb_strlen($name) > 60) {
                throw new ActionFailed('Please give the zone a name (up to 60 characters).');
            }

            if (!$provinces) {
                throw new ActionFailed('Choose at least one province for the zone.');
            }

            if ($taken = collect($provinces)->first(fn ($p) => ShippingZones::zoneOf($p))) {
                throw new ActionFailed($taken . ' is already in zone ' . ShippingZones::zoneOf($taken)['name'] . '.');
            }

            foreach (['first_kg' => 'The first-kg rate', 'per_kg' => 'The each-additional-kg rate'] as $field => $label) {
                if ($error = ShippingZones::rateError(request($field), $label)) {
                    throw new ActionFailed($error);
                }
            }

            $zoneId = DB::table('shipping_zones')->insertGetId(['name' => $name, 'provinces' => json_encode($provinces), 'created_by' => $user['id'], 'created_at' => now(), 'updated_at' => now()]);
            $rate = ['first_kg' => round((float) request('first_kg'), 2), 'per_kg' => round((float) request('per_kg'), 2), 'created_at' => now(), 'updated_at' => now()];

            // The new zone's rate, to and from every zone (itself included).
            foreach (DB::table('shipping_zones')->pluck('id') as $other) {
                DB::table('shipping_rates')->insertOrIgnore(['from_zone_id' => $other, 'to_zone_id' => $zoneId] + $rate);
                DB::table('shipping_rates')->insertOrIgnore(['from_zone_id' => $zoneId, 'to_zone_id' => $other] + $rate);
            }

            AuditLog::record('zone.added', 'shipping_zone', $zoneId, ['name' => $name, 'provinces' => $provinces, 'first_kg' => $rate['first_kg'], 'per_kg' => $rate['per_kg']], (int) $user['id']);

            return 'Zone ' . $name . ' added.';
        });
    }

    /* ---------------- Audit log (read-only) ---------------- */

    public function audit()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $entries = DB::table('audit_logs')
            ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->when($this->center($user) !== null, fn ($q) => $q->where(fn ($own) => $own->where('audit_logs.user_id', $user['id'])
                ->orWhere(fn ($riders) => $riders->where('audit_logs.subject_type', 'rider')->whereIn('audit_logs.subject_id', DB::table('rider_applications')->where('created_by', $user['id'])->select('user_id')))))
            ->orderByDesc('audit_logs.id')
            ->limit(300)
            ->get(['audit_logs.*', 'users.name as user_name']);

        return view('pages.logistics.audit', compact('user', 'entries'));
    }
}
