<?php

namespace App\Http\Controllers;

use App\Exceptions\ActionFailed;
use App\Services\CodRemittanceService;
use App\Services\DispatchService;
use App\Services\PickupService;
use App\Services\SortingCenterService;
use App\Support\AuditLog;
use App\Support\RiderJobs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * The rider's side of Rider Modules R1–R7: Online/Offline, job offers,
 * pickup by waybill scan, returns to the seller, vehicle and documents,
 * and COD remittances.
 */
class RiderWorkController extends Controller
{
    private function act(callable $action)
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        try {
            $message = $action($user);

            return request()->expectsJson() ? response()->json(['ok' => true, 'message' => $message]) : back()->with('success', $message);
        } catch (ActionFailed $e) {
            return request()->expectsJson() ? response()->json(['ok' => false, 'message' => $e->getMessage()], 422) : back()->with('error', $e->getMessage());
        }
    }

    public function availability()
    {
        return $this->act(function (array $user) {
            $online = request()->boolean('online');
            $status = DB::table('users')->where('id', $user['id'])->value('status');

            if ($online && $status !== 'Active') {
                throw new ActionFailed('Your account is ' . strtolower((string) $status) . ', so you can\'t go Online. Contact your logistics company.');
            }

            RiderJobs::setOnline((int) $user['id'], $online);

            if (!$online) {
                // Open offers go to the next rider.
                DB::table('job_offers')->where('rider_id', $user['id'])->where('status', 'pending')->update(['expires_at' => now()->subSecond()]);
                app(DispatchService::class)->sweep();
            }

            return $online ? 'You are Online. New job offers will come to you.' : 'You are Offline. You keep the jobs you already have.';
        });
    }

    public function offers(DispatchService $dispatch)
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        return response()->json(['offers' => $dispatch->offersFor((int) $user['id'])->values()]);
    }

    public function respond($id, DispatchService $dispatch)
    {
        return $this->act(fn (array $user) => $dispatch->respond((int) $user['id'], (int) $id, request('answer') === 'accept'));
    }

    public function scanPickup($id, PickupService $pickups)
    {
        return $this->act(fn (array $user) => $pickups->scan((int) $user['id'], (int) $id, (string) request('tracking_number')));
    }

    public function returnedToSeller($id, SortingCenterService $centers)
    {
        return $this->act(fn (array $user) => $centers->riderReturnedToSeller((int) $user['id'], (int) $id));
    }

    public function remit(CodRemittanceService $cod)
    {
        return $this->act(fn (array $user) => $cod->submit((int) $user['id'], request('amount')));
    }

    /** The rider's own uploaded document (no rider id in the URL, so only their own). */
    public function document(string $field)
    {
        $user = requireUserRole('rider');

        if (!is_array($user)) {
            return $user;
        }

        $path = DB::table('rider_applications')->where('user_id', $user['id'])->latest('id')->value($field);
        abort_unless($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->response($path);
    }

    /** Vehicle and documents (R1): changes are flagged for the company to verify again. */
    public function updateVehicle()
    {
        return $this->act(function (array $user) {
            $application = DB::table('rider_applications')->where('user_id', $user['id'])->latest('id')->first();

            if (!$application) {
                throw new ActionFailed('Rider record not found.');
            }

            $changes = [];
            $plate = strtoupper(preg_replace('/\s+/', '', (string) request('plate_number', '')));

            if ($plate !== '' && $plate !== strtoupper(preg_replace('/\s+/', '', (string) $application->plate_number))) {
                if (!preg_match('/^[A-Z0-9]{5,8}$/', $plate)) {
                    throw new ActionFailed('Enter a valid plate number (5 to 8 letters and numbers).');
                }

                $taken = DB::table('rider_applications')->where('user_id', '!=', $user['id'])
                    ->whereRaw("UPPER(REPLACE(plate_number, ' ', '')) = ?", [$plate])->exists();

                if ($taken) {
                    throw new ActionFailed('That plate number is already registered to another rider.');
                }

                $changes['plate_number'] = $plate;
            }

            if (request()->filled('license_expiry')) {
                $expiry = \Illuminate\Support\Carbon::parse((string) request('license_expiry'))->startOfDay();

                if ($expiry->lt(today())) {
                    throw new ActionFailed('Your driver\'s license has expired. Enter an expiry date from today onward.');
                }

                $changes['license_expiry'] = $expiry->toDateString();
            }

            foreach (['drivers_license' => 'driver\'s license', 'or_cr' => 'OR/CR'] as $field => $label) {
                if (!request()->hasFile($field)) {
                    continue;
                }

                $file = request()->file($field);

                if (Validator::make([$field => $file], [$field => 'file|mimes:jpg,jpeg,png,pdf|max:5120'])->fails()) {
                    throw new ActionFailed('The ' . $label . ' must be a JPG, PNG or PDF file of at most 5 MB.');
                }

                $changes[$field] = $file->store('rider-documents', 'local');
            }

            if (!$changes) {
                throw new ActionFailed('Nothing to update.');
            }

            DB::table('rider_applications')->where('id', $application->id)->update($changes + ['needs_reverification' => true, 'updated_at' => now()]);
            AuditLog::record('rider.vehicle.updated', 'rider', (int) $user['id'], ['fields' => array_keys($changes), 'plate' => $changes['plate_number'] ?? null]);

            if ($application->created_by) {
                createNotification((int) $application->created_by, 'Rider Details Changed', ($application->full_name ?? 'A rider') . ' updated their vehicle or documents. Please verify them again.', 'rider', (int) $application->id);
            }

            return 'Saved. Your logistics company will verify the new details.';
        });
    }
}
