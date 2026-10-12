<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * The field rules every registration form shares (buyer, seller, logistics,
 * rider): names, Philippine mobile numbers, passwords and uploaded documents.
 */
class RegistrationRules
{
    /** Letters (ñ and other accented letters too), spaces, hyphen, apostrophe and period. */
    public const NAME_PATTERN = "/^[\\p{L}][\\p{L} .'\\-]*$/u";

    /** 09XXXXXXXXX or +639XXXXXXXXX (spaces are ignored). */
    public const PHONE_PATTERN = '/^(09|\+639)\d{9}$/';

    public const DOCUMENT_TYPES = 'jpg,jpeg,png,pdf';

    /** 5 MB, in kilobytes. */
    public const DOCUMENT_MAX_KB = 5120;

    public const SHOP_NAME_MIN = 3;
    public const SHOP_NAME_MAX = 30;

    public static function name(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'min:2', 'max:50', 'regex:' . self::NAME_PATTERN];
    }

    public static function phone(): array
    {
        return ['required', 'string', function ($attribute, $value, $fail) {
            if (!preg_match(self::PHONE_PATTERN, self::stripSpaces($value))) {
                $fail('Please enter a valid Philippine mobile number (09XXXXXXXXX or +639XXXXXXXXX).');
            }
        }];
    }

    public static function password(): array
    {
        return ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()];
    }

    public static function document(): array
    {
        return ['required', 'file', 'mimes:' . self::DOCUMENT_TYPES, 'max:' . self::DOCUMENT_MAX_KB];
    }

    public static function shopName(): array
    {
        return ['required', 'string', 'min:' . self::SHOP_NAME_MIN, 'max:' . self::SHOP_NAME_MAX];
    }

    /** TIN: 12 digits as 000-000-000-000 (9-digit TIN + 3-digit branch code); dashes may be left out. */
    public static function tin(): array
    {
        return ['required', 'string', function ($attribute, $value, $fail) {
            if (!preg_match('/^\d{3}-?\d{3}-?\d{3}-?\d{3}$/', trim((string) $value))) {
                $fail('The TIN must be 12 digits in the form 000-000-000-000.');
            }
        }];
    }

    public static function normalizeTin(?string $tin): string
    {
        $digits = preg_replace('/\D/', '', (string) $tin);

        return implode('-', str_split($digits, 3));
    }

    /** Whether a seller (or logistics) application of another account already has this TIN. */
    public static function tinTaken(string $tin, ?int $ignoreUserId = null): bool
    {
        $tin = self::normalizeTin($tin);

        foreach (['seller_applications', 'logistics_applications'] as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasColumn($table, 'tin')) {
                continue;
            }
            if (DB::table($table)->where('tin', $tin)->when($ignoreUserId, fn ($q) => $q->where('user_id', '!=', $ignoreUserId))->exists()) {
                return true;
            }
        }

        return false;
    }

    /** SEC registration number: 2 letters followed by 9 digits, e.g. CS202012345. */
    public static function secNo(): array
    {
        return ['required', 'string', function ($attribute, $value, $fail) {
            if (!preg_match('/^[A-Za-z]{2}\d{9}$/', trim((string) $value))) {
                $fail('The SEC registration number must be 2 letters followed by 9 digits (e.g. CS202012345).');
            }
        }];
    }

    /** Whether another logistics company already registered this SEC number. */
    public static function secNoTaken(string $secNo, ?int $ignoreUserId = null): bool
    {
        return DB::table('logistics_applications')
            ->where('sec_registration_no', strtoupper(trim($secNo)))
            ->when($ignoreUserId, fn ($q) => $q->where('user_id', '!=', $ignoreUserId))
            ->exists();
    }

    /** One or more regions a logistics company delivers in. */
    public static function serviceAreas(): array
    {
        return ['required', 'array', 'min:1'];
    }

    public static function region(): array
    {
        return ['required', 'string', \Illuminate\Validation\Rule::in(PhLocations::REGION_NAMES)];
    }

    /** The province must be one of the chosen region's provinces. */
    public static function provinceInRegion(?string $region): array
    {
        return ['required', 'string', function ($attribute, $value, $fail) use ($region) {
            $provinces = PhLocations::provincesByRegionName()[$region] ?? null;
            if ($provinces !== null && !in_array($value, $provinces, true)) {
                $fail('Please choose a province in ' . $region . '.');
            }
        }];
    }

    public static function postalCode(): array
    {
        return ['required', 'string', 'regex:/^\d{4}$/'];
    }

    /** LTO driver's license number: 1 letter and 11 digits as A00-00-000000 (dashes may be left out). */
    public static function licenseNo(): array
    {
        return ['required', 'string', function ($attribute, $value, $fail) {
            if (!preg_match('/^[A-Za-z]\d{2}-?\d{2}-?\d{6}$/', trim((string) $value))) {
                $fail("The driver's license number must be in the form A00-00-000000.");
            }
        }];
    }

    public static function normalizeLicenseNo(?string $license): string
    {
        $plain = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $license));

        return substr($plain, 0, 3) . '-' . substr($plain, 3, 2) . '-' . substr($plain, 5);
    }

    /** The license must not be expired: today or a later date. */
    public static function licenseExpiry(): array
    {
        return ['required', 'date', 'after_or_equal:today'];
    }

    /** Plate numbers compare without spaces or dashes, in capitals ("abc 1234" = "ABC-1234"). */
    public static function normalizePlate(?string $plate): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $plate));
    }

    /** Whether another rider already has this license number. */
    public static function licenseTaken(string $license, ?int $ignoreUserId = null): bool
    {
        return DB::table('rider_applications')
            ->where('drivers_license_no', self::normalizeLicenseNo($license))
            ->when($ignoreUserId, fn ($q) => $q->where('user_id', '!=', $ignoreUserId))
            ->exists();
    }

    /** Whether another rider already has this plate number. */
    public static function plateTaken(string $plate, ?int $ignoreUserId = null): bool
    {
        return DB::table('rider_applications')
            ->whereRaw("UPPER(REPLACE(REPLACE(plate_number, ' ', ''), '-', '')) = ?", [self::normalizePlate($plate)])
            ->when($ignoreUserId, fn ($q) => $q->where('user_id', '!=', $ignoreUserId))
            ->exists();
    }

    /** Messages for the rules above, keyed for the given name fields. */
    public static function messages(array $nameFields = ['first_name', 'last_name']): array
    {
        $messages = [
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Passwords do not match.',
            'mimes' => 'The :attribute must be a JPG, PNG or PDF file.',
            'postal_code.regex' => 'The postal code must be exactly 4 digits.',
            'license_expiry.after_or_equal' => "The driver's license is expired. Enter a license that is valid today or later.",
            // Size rules take one message per type; only files get the friendlier wording.
            'max' => [
                'file' => 'The :attribute must not be larger than 5 MB.',
                'string' => 'The :attribute must not be greater than :max characters.',
                'numeric' => 'The :attribute must not be greater than :max.',
                'array' => 'The :attribute must not have more than :max items.',
            ],
        ];

        foreach ($nameFields as $field) {
            $messages[$field . '.regex'] = 'The :attribute may only contain letters, spaces, hyphens (-), apostrophes (\') and periods (.).';
            $messages[$field . '.min'] = 'The :attribute must be 2 to 50 characters.';
            $messages[$field . '.max'] = 'The :attribute must be 2 to 50 characters.';
        }

        return $messages;
    }

    /** One stored form for a mobile number: +639171234567 and 0917 123 4567 both become 09171234567. */
    public static function normalizePhone(?string $phone): string
    {
        $phone = self::stripSpaces($phone);

        return str_starts_with($phone, '+63') ? '0' . substr($phone, 3) : $phone;
    }

    /**
     * Whether another account already uses this mobile number, in any of the
     * ways it may have been saved before numbers were normalized.
     */
    public static function phoneTaken(string $phone, ?int $ignoreUserId = null): bool
    {
        $local = self::normalizePhone($phone);
        $digits = substr($local, 1);

        return DB::table('users')
            ->whereIn(DB::raw("REPLACE(phone, ' ', '')"), [$local, '+63' . $digits, '63' . $digits])
            ->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
            ->exists();
    }

    private static function stripSpaces(mixed $value): string
    {
        return preg_replace('/\s+/', '', (string) $value);
    }
}
