<?php

namespace App\Support;

/**
 * Philippine provinces and their cities/municipalities (the same list the
 * registration forms use, public/js/data/psgc-data.js), plus a way to find
 * the town and province inside a free-text address like
 * "123 Rizal St., Poblacion, Santa Cruz, Laguna".
 */
class PhLocations
{
    public const NCR = 'Metro Manila (NCR)';

    private const VISAYAS = [
        'Aklan', 'Antique', 'Capiz', 'Guimaras', 'Iloilo', 'Negros Occidental', 'Bohol', 'Cebu',
        'Negros Oriental', 'Siquijor', 'Biliran', 'Eastern Samar', 'Leyte', 'Northern Samar', 'Samar',
        'Southern Leyte',
    ];

    private const MINDANAO = [
        'Zamboanga del Norte', 'Zamboanga del Sur', 'Zamboanga Sibugay', 'Bukidnon', 'Camiguin',
        'Lanao del Norte', 'Misamis Occidental', 'Misamis Oriental', 'Davao de Oro', 'Davao del Norte',
        'Davao del Sur', 'Davao Occidental', 'Davao Oriental', 'Cotabato', 'Sarangani', 'South Cotabato',
        'Sultan Kudarat', 'Agusan del Norte', 'Agusan del Sur', 'Dinagat Islands', 'Surigao del Norte',
        'Surigao del Sur', 'Basilan', 'Lanao del Sur', 'Maguindanao del Norte', 'Maguindanao del Sur',
        'Sulu', 'Tawi-Tawi',
    ];

    private static ?array $data = null;

    /** @return array<string, string[]> province => cities/municipalities */
    public static function all(): array
    {
        if (self::$data === null) {
            $js = (string) @file_get_contents(public_path('js/data/psgc-data.js'));
            $json = substr($js, (int) strpos($js, '{'));
            $json = substr($json, 0, (int) strrpos($json, '}') + 1);
            self::$data = json_decode($json, true) ?: [];
        }

        return self::$data;
    }

    public static function exists(string $province, string $city): bool
    {
        return in_array($city, self::all()[$province] ?? [], true);
    }

    /** "Makati City" and "Makati" are the same town. */
    public static function sameTown(?string $a, ?string $b): bool
    {
        if (!$a || !$b) {
            return false;
        }

        $canon = fn (string $town) => preg_replace('/ city$/', '', self::normalize($town));

        return $canon($a) === $canon($b);
    }

    /** Luzon, Visayas or Mindanao. */
    public static function islandGroup(?string $province): ?string
    {
        if (!$province) {
            return null;
        }

        return match (true) {
            in_array($province, self::VISAYAS, true) => 'Visayas',
            in_array($province, self::MINDANAO, true) => 'Mindanao',
            default => 'Luzon',
        };
    }

    /**
     * The province and town written in an address, or null when it names no
     * province we know and no town that only one province has.
     *
     * @return array{province: string, city: ?string}|null
     */
    public static function locate(?string $address): ?array
    {
        // Drop a trailing country and ZIP code: "..., Laguna 4009, Philippines".
        $text = ' ' . self::normalize((string) $address) . ' ';
        $text = preg_replace('/( philippines| ph| \d{4})+ $/', ' ', $text);

        if (trim($text) === '') {
            return null;
        }

        // "Quezon City" must not count as the province "Quezon", "San Pablo City" etc. likewise.
        $provinceText = $text;
        foreach (self::all() as $cities) {
            foreach ($cities as $city) {
                $name = self::normalize($city);
                if (str_ends_with($name, ' city')) {
                    $provinceText = str_replace(' ' . $name . ' ', ' ', $provinceText);
                }
            }
        }

        $provincesNamed = [];
        foreach (array_keys(self::all()) as $province) {
            foreach (self::provinceAliases($province) as $alias) {
                if (str_contains($provinceText, ' ' . $alias . ' ')) {
                    $provincesNamed[$province] = true;
                }
            }
        }

        // "Metro Manila" would otherwise also match the city "Manila", and the
        // province written last ("Lucena City, Quezon") is not a town.
        $cityText = str_replace([' metro manila ', ' national capital region '], ' ', $text);
        foreach (array_keys($provincesNamed) as $province) {
            foreach (self::provinceAliases($province) as $alias) {
                if (str_ends_with($cityText, ' ' . $alias . ' ')) {
                    $cityText = substr($cityText, 0, -strlen($alias) - 1);
                    break 2; // only the one province at the end ("Rizal, Laguna" keeps the town Rizal)
                }
            }
        }

        $best = null;
        $bestScore = 0;
        $tied = false;

        foreach (self::all() as $province => $cities) {
            foreach ($cities as $city) {
                foreach (self::cityAliases($city) as $alias) {
                    $at = strrpos($cityText, ' ' . $alias . ' ');

                    if ($at === false) {
                        continue;
                    }

                    // Its province written too wins; then the town written last
                    // ("Rizal St., Santa Cruz" → Santa Cruz), then the longer name.
                    $score = (isset($provincesNamed[$province]) ? 100000 : 0) + ($at + strlen($alias)) * 100 + strlen($alias);

                    if ($score > $bestScore) {
                        [$best, $bestScore, $tied] = [['province' => $province, 'city' => $city], $score, false];
                    } elseif ($score === $bestScore && $best['province'] !== $province) {
                        $tied = true;
                    }
                }
            }
        }

        if ($best && !$tied) {
            return $best;
        }

        // No town we can pin down, but the province is clear.
        if (count($provincesNamed) === 1) {
            return ['province' => array_key_first($provincesNamed), 'city' => null];
        }

        return null;
    }

    /**
     * An address split for the Province / City / street fields:
     * "15 Rizal St, Poblacion, Santa Cruz, Laguna" →
     * street "15 Rizal St, Poblacion", city "Santa Cruz", province "Laguna".
     *
     * @return array{street: string, province: ?string, city: ?string}
     */
    public static function split(?string $address): array
    {
        $address = trim((string) $address);
        $found = self::locate($address);
        $parts = array_values(array_filter(array_map('trim', explode(',', $address)), fn ($p) => $p !== ''));

        if ($found) {
            $tail = array_filter([
                ...($found['city'] ? self::cityAliases($found['city']) : []),
                $found['city'] ? preg_replace('/ city$/', '', self::normalize($found['city'])) : null,
                ...self::provinceAliases($found['province']),
                'metro manila', 'philippines', 'ph',
            ]);

            // Drop the town / province / country pieces from the end.
            while ($parts) {
                $last = preg_replace('/ \d{4}$/', '', self::normalize(end($parts)));

                if (!in_array($last, $tail, true) && !preg_match('/^\d{4}$/', $last)) {
                    break;
                }

                array_pop($parts);
            }
        }

        return [
            'street' => implode(', ', $parts),
            'province' => $found['province'] ?? null,
            'city' => $found['city'] ?? null,
        ];
    }

    private static function provinceAliases(string $province): array
    {
        if ($province === self::NCR) {
            return ['metro manila', 'ncr', 'national capital region'];
        }

        return [self::normalize($province)];
    }

    private static function cityAliases(string $city): array
    {
        $name = self::normalize($city);
        $aliases = [$name];

        // "Calamba City" is usually written "Calamba" — but keep "Quezon City" whole.
        if (str_ends_with($name, ' city')) {
            $short = substr($name, 0, -5);
            if (!isset(self::all()[ucwords($short)]) && $short !== 'quezon') {
                $aliases[] = $short;
            }
        }

        foreach ($aliases as $alias) {
            foreach (['santa ' => 'sta ', 'santo ' => 'sto ', 'general ' => 'gen '] as $long => $short) {
                if (str_starts_with($alias, $long)) {
                    $aliases[] = $short . substr($alias, strlen($long));
                }
            }
        }

        return array_unique($aliases);
    }

    private static function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = strtr($value, ['ñ' => 'n', 'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }
}
