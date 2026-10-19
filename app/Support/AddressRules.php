<?php

namespace App\Support;

/**
 * An address-book entry in parts (Buyer Modules B2): recipient, phone,
 * region → province → city → barangay, street. Each blank part gets its
 * own message; a city must be in its province and a barangay in its city.
 */
class AddressRules
{
    /** @return array{0: array<string, string>, 1: array<string, string>} [clean parts, errors] */
    public static function check(array $input): array
    {
        $errors = [];
        $p = fn (string $key) => trim((string) ($input[$key] ?? ''));

        $recipient = preg_replace('/\s+/', ' ', $p('recipient_name'));
        $phone = preg_replace('/\s+/', '', $p('phone'));
        $region = $p('region');
        $province = $p('province');
        $city = $p('city_municipality');
        $barangay = $p('barangay');
        $street = preg_replace('/\s+/', ' ', $p('street'));

        if ($recipient === '') {
            $errors['recipient_name'] = 'Please enter the recipient\'s name.';
        } elseif (mb_strlen($recipient) < 2 || mb_strlen($recipient) > 100 || !preg_match(RegistrationRules::NAME_PATTERN, $recipient)) {
            $errors['recipient_name'] = 'Enter the recipient\'s name (letters only, 2 to 100 characters).';
        }

        if ($phone === '') {
            $errors['phone'] = 'Please enter the phone number.';
        } elseif (!preg_match(RegistrationRules::PHONE_PATTERN, $phone)) {
            $errors['phone'] = 'Please enter a valid Philippine mobile number (09XXXXXXXXX or +639XXXXXXXXX).';
        }

        $regions = PhLocations::provincesByRegionName();

        if ($region === '') {
            $errors['region'] = 'Please choose the region.';
        } elseif (!isset($regions[$region])) {
            $errors['region'] = 'Please choose a region from the list.';
        }

        if ($province === '') {
            $errors['province'] = 'Please choose the province.';
        } elseif (isset($regions[$region]) && !in_array($province, $regions[$region], true)) {
            $errors['province'] = 'That province is not in ' . $region . '.';
        }

        if ($city === '') {
            $errors['city_municipality'] = 'Please choose the city / municipality.';
        } elseif ($province !== '' && !PhLocations::exists($province, $city)) {
            $errors['city_municipality'] = 'That city / municipality is not in ' . $province . '.';
        }

        $barangays = self::barangays($province, $city);

        if ($barangay === '') {
            $errors['barangay'] = 'Please choose the barangay.';
        } elseif ($barangays && !in_array($barangay, $barangays, true)) {
            $errors['barangay'] = 'That barangay is not in ' . $city . '.';
        }

        if ($street === '') {
            $errors['street'] = 'Please enter the house no., building and street.';
        } elseif (mb_strlen($street) > 180) {
            $errors['street'] = 'The street address can have at most 180 characters.';
        }

        $clean = [
            'recipient_name' => $recipient,
            'phone' => $phone !== '' ? RegistrationRules::normalizePhone($phone) : '',
            'region' => $region,
            'province' => $province,
            'city_municipality' => $city,
            'barangay' => $barangay,
            'street' => $street,
            // What checkout and the order keep: one line, town and province last.
            'address' => implode(', ', array_filter([$street, $barangay === '' || preg_match('/^(Brgy|Barangay)\b/i', $barangay) ? $barangay : 'Brgy. ' . $barangay, $city, $province])),
        ];

        return [$clean, $errors];
    }

    /** The barangays of a town (empty when the list doesn't have that town). */
    public static function barangays(string $province, string $city): array
    {
        static $all = null;

        $all ??= json_decode((string) @file_get_contents(public_path('js/data/barangays.json')), true) ?: [];

        return $all[$province][$city] ?? [];
    }
}
