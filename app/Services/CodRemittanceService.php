<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Support\AuditLog;
use App\Support\RiderJobs;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cash on Delivery money from riders to their logistics company (Rider R7,
 * Logistics L8, A24): the rider sends a remittance for the cash they hold;
 * only the company confirms it; a short remittance leaves the difference
 * outstanding on the rider and is flagged on the reconciliation screen.
 */
class CodRemittanceService
{
    /** COD orders the rider delivered and hasn't remitted yet. */
    public function held(int $riderId): Collection
    {
        return DB::table('orders')
            ->where('delivery_rider_id', $riderId)
            ->whereNotNull('cod_collected_at')
            ->whereNull('cod_remitted_at')
            ->orderBy('cod_collected_at')
            ->get(['id', 'total_amount', 'cod_amount_collected', 'cod_collected_at']);
    }

    /** Short amounts of confirmed remittances, still owed by the rider. */
    public function shortfall(int $riderId): float
    {
        return round((float) DB::table('cod_remittances')
            ->where('rider_id', $riderId)
            ->where('status', 'Confirmed')
            ->whereColumn('amount', '<', 'expected')
            ->sum(DB::raw('expected - amount')), 2);
    }

    /** @return array{held: float, orders: Collection, pending: ?object, shortfall: float, balance: float} */
    public function balance(int $riderId): array
    {
        $orders = $this->held($riderId);
        $held = round((float) $orders->sum(fn ($o) => (float) ($o->cod_amount_collected ?? $o->total_amount)), 2);
        $shortfall = $this->shortfall($riderId);

        return [
            'held' => $held,
            'orders' => $orders,
            'pending' => DB::table('cod_remittances')->where('rider_id', $riderId)->where('status', 'Pending')->latest('id')->first(),
            'shortfall' => $shortfall,
            'balance' => round($held + $shortfall, 2),
        ];
    }

    /** @throws ActionFailed */
    public function submit(int $riderId, mixed $amount): string
    {
        $amount = trim((string) $amount);

        if ($amount === '' || !is_numeric($amount) || (float) $amount <= 0 || preg_match('/\.\d{3,}$/', $amount)) {
            throw new ActionFailed('Enter the amount you are remitting (more than ₱0, up to 2 decimals).');
        }

        if (DB::table('cod_remittances')->where('rider_id', $riderId)->where('status', 'Pending')->exists()) {
            throw new ActionFailed('You already have a remittance waiting for confirmation.');
        }

        $orders = $this->held($riderId);

        if ($orders->isEmpty()) {
            throw new ActionFailed('You have no Cash on Delivery money to remit.');
        }

        $expected = round((float) $orders->sum(fn ($o) => (float) ($o->cod_amount_collected ?? $o->total_amount)), 2);

        if (round((float) $amount, 2) > $expected) {
            throw new ActionFailed('That is more than the ₱' . number_format($expected, 2) . ' you collected.');
        }

        DB::table('cod_remittances')->insert([
            'rider_id' => $riderId, 'amount' => round((float) $amount, 2), 'expected' => $expected,
            'order_ids' => json_encode($orders->pluck('id')->all()), 'status' => 'Pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return 'Remittance of ₱' . number_format((float) $amount, 2) . ' sent. It counts once your logistics company confirms it.';
    }

    /** @throws ActionFailed */
    public function confirm(int $remittanceId, int $logisticsUserId, ?int $centerId = null): string
    {
        $remittance = DB::table('cod_remittances')->find($remittanceId);

        if (!$remittance) {
            throw new ActionFailed('Remittance not found.');
        }

        if (!RiderJobs::worksFor((int) $remittance->rider_id, $centerId)) {
            throw new ActionFailed('This rider belongs to another logistics company.');
        }

        $confirmed = DB::table('cod_remittances')->where('id', $remittanceId)->where('status', 'Pending')
            ->update(['status' => 'Confirmed', 'confirmed_by' => $logisticsUserId, 'confirmed_at' => now(), 'updated_at' => now()]);

        if (!$confirmed) {
            throw new ActionFailed('This remittance was already confirmed.');
        }

        DB::table('orders')->whereIn('id', json_decode($remittance->order_ids, true) ?: [])->whereNull('cod_remitted_at')
            ->update(['cod_remitted_at' => now(), 'cod_remitted_center_id' => $centerId, 'updated_at' => now()]);

        $short = round((float) $remittance->expected - (float) $remittance->amount, 2);

        AuditLog::record('cod.remittance.confirmed', 'rider', (int) $remittance->rider_id, [
            'remittance' => $remittanceId, 'amount' => (float) $remittance->amount, 'expected' => (float) $remittance->expected, 'shortfall' => max(0, $short),
        ], $logisticsUserId);

        createNotification((int) $remittance->rider_id, 'Remittance Confirmed', 'Your COD remittance of ₱' . number_format((float) $remittance->amount, 2) . ' was confirmed.'
            . ($short > 0 ? ' You still owe ₱' . number_format($short, 2) . '.' : ''), 'delivery_status');

        return 'Remittance of ₱' . number_format((float) $remittance->amount, 2) . ' confirmed.' . ($short > 0 ? ' Shortfall: ₱' . number_format($short, 2) . '.' : '');
    }

    /**
     * One row per rider for a day: COD collected that day, remitted, the
     * difference (negative = short) and the orders.
     */
    public function reconciliation(?int $centerId, string $date): Collection
    {
        $day = Carbon::parse($date);
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();

        $collected = DB::table('orders')
            ->whereNotNull('cod_collected_at')
            ->whereBetween('cod_collected_at', [$start, $end])
            ->get(['id', 'delivery_rider_id', 'total_amount', 'cod_amount_collected'])
            ->filter(fn ($o) => $o->delivery_rider_id && RiderJobs::worksFor((int) $o->delivery_rider_id, $centerId))
            ->groupBy('delivery_rider_id');

        return $collected->map(function ($orders, $riderId) use ($start, $end) {
            $total = round((float) $orders->sum(fn ($o) => (float) ($o->cod_amount_collected ?? $o->total_amount)), 2);
            $remittances = DB::table('cod_remittances')->where('rider_id', $riderId)->whereBetween('created_at', [$start, $end])->get();
            $remitted = round((float) $remittances->sum('amount'), 2);

            return (object) [
                'rider_id' => (int) $riderId,
                'rider' => DB::table('users')->where('id', $riderId)->value('name'),
                'orders' => $orders->pluck('id')->all(),
                'collected' => $total,
                'remitted' => $remitted,
                'difference' => round($remitted - $total, 2),
                'short' => $remitted < $total,
                'pending' => $remittances->firstWhere('status', 'Pending'),
            ];
        })->values();
    }
}
