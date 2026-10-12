<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Mail\RiderActivationMail;
use App\Models\User;
use App\Support\RegistrationRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Riders a logistics company adds itself (the other way in besides the
 * public rider application). The account starts as "Pending Activation";
 * the rider sets their own password through an emailed link that works for
 * 24 hours and only once. No separate approval is needed.
 */
class RiderAccountService
{
    public const LINK_HOURS = 24;

    /**
     * @param  array<string, mixed>  $data  validated Add Rider fields
     * @param  array<string, UploadedFile>  $files
     */
    public function create(int $logisticsUserId, array $data, array $files): User
    {
        $email = strtolower(trim($data['email']));

        // A double-clicked Save sends the same rider twice: only the first one goes through.
        $lock = Cache::lock('add-rider:' . $email, 10);
        if (!$lock->get()) {
            throw new ActionFailed('This rider is already being saved. Please wait a moment.');
        }

        try {
            if (User::where('email', $email)->exists()) {
                throw new ActionFailed('This email is already in use.');
            }

            $folder = 'rider-applications/' . Str::uuid();
            $paths = [];
            foreach ($files as $field => $file) {
                $paths[$field] = $file->store($folder, 'local');
            }

            $name = formatFullName($data['first_name'], $data['middle_initial'] ?? null, $data['last_name']);
            $address = $data['street_address'] . ', ' . $data['barangay'] . ', ' . $data['city_municipality'] . ', ' . $data['province'];

            $user = DB::transaction(function () use ($logisticsUserId, $data, $paths, $email, $name, $address) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    // Unusable until the rider sets their own on activation.
                    'password' => Hash::make(Str::random(40)),
                    'role' => 'rider',
                    'phone' => RegistrationRules::normalizePhone($data['phone']),
                    'address' => $address,
                    'is_verified' => true,
                    'last_name' => $data['last_name'],
                    'first_name' => $data['first_name'],
                    'middle_initial' => $data['middle_initial'] ?? null,
                    'sex' => $data['sex'],
                    'birthdate' => $data['birthdate'],
                    'age' => calculateAge($data['birthdate']),
                    'province' => $data['province'],
                    'city_municipality' => $data['city_municipality'],
                    'barangay' => $data['barangay'],
                    'street_address' => $data['street_address'],
                ]);
                // Not fillable on purpose; set here only.
                $user->forceFill(['status' => 'Pending Activation'])->save();

                DB::table('rider_applications')->insert([
                    'user_id' => $user->id,
                    'created_by' => $logisticsUserId,
                    'full_name' => $name,
                    'phone' => $user->phone,
                    'address' => $address,
                    'vehicle_type' => $data['vehicle_type'],
                    'vehicle_model' => $data['vehicle_model'] ?? '',
                    'plate_number' => strtoupper(trim($data['plate_number'])),
                    'drivers_license_no' => RegistrationRules::normalizeLicenseNo($data['drivers_license_no']),
                    'license_expiry' => $data['license_expiry'],
                    'drivers_license' => $paths['drivers_license'] ?? null,
                    'or_cr' => $paths['or_cr'] ?? null,
                    // Added by an approved company: no separate approval.
                    'status' => 'Approved',
                    'reviewed_at' => now(),
                    'reviewed_by' => $logisticsUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $user;
            });
        } finally {
            $lock->release();
        }

        $this->sendActivation($user->id);

        return $user;
    }

    /** Emails a new activation link; any earlier link stops working. */
    public function sendActivation(int $riderUserId): void
    {
        $application = $this->pendingApplication($riderUserId);
        $token = Str::random(48);

        DB::table('rider_applications')->where('id', $application->id)->update([
            'activation_token' => hash('sha256', $token),
            'activation_expires_at' => now()->addHours(self::LINK_HOURS),
            'updated_at' => now(),
        ]);

        $rider = User::findOrFail($riderUserId);
        Mail::to($rider->email)->send(new RiderActivationMail(
            $rider->name,
            self::companyName($application->created_by),
            route('rider.activate', $token),
            self::LINK_HOURS,
        ));
    }

    /**
     * The rider application behind an activation link.
     *
     * @return object|string the application row, or why the link cannot be used
     */
    public function findByToken(string $token): object|string
    {
        $application = DB::table('rider_applications')->where('activation_token', hash('sha256', $token))->first();

        if (!$application || $application->activated_at) {
            return 'This activation link is invalid or has already been used.';
        }

        if (now()->greaterThan($application->activation_expires_at)) {
            return 'This activation link has expired. Ask your logistics company to send a new one.';
        }

        return $application;
    }

    /** Sets the rider's password and makes the account Active; the link is used up. */
    public function activate(object $application, string $password): void
    {
        DB::transaction(function () use ($application, $password) {
            User::whereKey($application->user_id)->update([
                'password' => Hash::make($password),
                'status' => 'Active',
            ]);

            DB::table('rider_applications')->where('id', $application->id)->update([
                'activation_token' => null,
                'activation_expires_at' => null,
                'activated_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /** The company (logistics business name) that added a rider. */
    public static function companyName(?int $logisticsUserId): ?string
    {
        if (!$logisticsUserId) {
            return null;
        }

        return DB::table('logistics_applications')->where('user_id', $logisticsUserId)->latest('id')->value('business_name')
            ?: DB::table('users')->where('id', $logisticsUserId)->value('name');
    }

    private function pendingApplication(int $riderUserId): object
    {
        $application = DB::table('rider_applications')
            ->where('user_id', $riderUserId)
            ->whereNotNull('created_by')
            ->latest('id')
            ->first();

        if (!$application || $application->activated_at) {
            throw new ActionFailed('This rider has already activated their account.');
        }

        return $application;
    }
}
