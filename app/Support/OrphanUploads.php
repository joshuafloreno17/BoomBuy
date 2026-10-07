<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Registration saves the ID and documents before the email code is
 * entered (the account is only created after). When someone never enters
 * the code, those files stay on the server with no account — IDs and
 * permits of people who aren't customers. Once a day, folders older than
 * MAX_AGE_HOURS that no application points to are deleted.
 */
class OrphanUploads
{
    /** Each registration's files sit in their own folder under one of these. */
    public const ROOTS = ['buyer-ids', 'seller-applications', 'rider-applications', 'logistics-applications'];

    /** Long enough for anyone still entering their code. */
    public const MAX_AGE_HOURS = 24;

    /** Every column that keeps a registration file. */
    private const COLUMNS = [
        'users' => ['id_photo'],
        'buyer_applications' => ['id_photo'],
        'seller_applications' => ['national_id', 'business_permit'],
        'rider_applications' => ['national_id', 'drivers_license', 'profile_selfie', 'proof_of_address', 'or_cr'],
        'logistics_applications' => ['id_photo', 'business_permit'],
    ];

    private const THROTTLE_KEY = 'orphan-uploads-sweep';

    /** @return int folders removed */
    public static function sweep(bool $force = false): int
    {
        // Tests never sweep the real storage folder by accident.
        if (!$force && (app()->runningUnitTests() || !Cache::add(self::THROTTLE_KEY, true, now()->addDay()))) {
            return 0;
        }

        $disk = Storage::disk('local');
        $used = self::usedFolders();
        $cutoff = now()->subHours(self::MAX_AGE_HOURS)->getTimestamp();
        $removed = 0;

        foreach (self::ROOTS as $root) {
            foreach ($disk->directories($root) as $folder) {
                if (isset($used[$folder])) {
                    continue;
                }

                $files = $disk->allFiles($folder);
                $newest = collect($files)->map(fn ($file) => $disk->lastModified($file))->max() ?? 0;

                if ($newest > $cutoff) {
                    continue; // someone may still be entering their code
                }

                $disk->deleteDirectory($folder);
                $removed++;
            }
        }

        return $removed;
    }

    /** "seller-applications/<uuid>" => true for every folder an account still uses. */
    private static function usedFolders(): array
    {
        $folders = [];

        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)->whereNotNull($column)->pluck($column)->each(function ($path) use (&$folders) {
                    $folders[dirname((string) $path)] = true;
                });
            }
        }

        return $folders;
    }
}
