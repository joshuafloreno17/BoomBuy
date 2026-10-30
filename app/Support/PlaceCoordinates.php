<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Where a town is on the map ("Calamba City, Laguna" → [14.206, 121.155]),
 * looked up once from OpenStreetMap's Nominatim and saved in
 * place_coordinates. Unknown or failed lookups fall back to the province's
 * capital (PhCoordinates).
 */
class PlaceCoordinates
{
    private const NOMINATIM = 'https://nominatim.openstreetmap.org/search';

    /** A failed lookup is tried again after this long. */
    private const RETRY_MINUTES = 10;

    /**
     * @param  bool  $lookup  false = saved positions only (no web request)
     * @return array{0: float, 1: float}|null  [lat, lng]
     */
    public static function of(?string $town, ?string $province, bool $lookup = true): ?array
    {
        $town = trim((string) $town);

        if ($town === '' || !$province) {
            return PhCoordinates::forProvince($province);
        }

        $saved = DB::table('place_coordinates')->where('town', $town)->where('province', $province)->first();

        if ($saved && $saved->lat !== null) {
            return [(float) $saved->lat, (float) $saved->lng];
        }

        $recentlyFailed = $saved && $saved->fetched_at
            && now()->subMinutes(self::RETRY_MINUTES)->lt($saved->fetched_at);

        if (!$lookup || $recentlyFailed) {
            return PhCoordinates::forProvince($province);
        }

        $point = self::fetch($town, $province);

        DB::table('place_coordinates')->updateOrInsert(
            ['town' => $town, 'province' => $province],
            ['lat' => $point[0] ?? null, 'lng' => $point[1] ?? null, 'fetched_at' => now()]
        );

        return $point ?? PhCoordinates::forProvince($province);
    }

    /** @return array{0: float, 1: float}|null */
    private static function fetch(string $town, string $province): ?array
    {
        try {
            // IPv4 + one retry, like RoadRoute: the live PHP's SSL handshake now and then stalls.
            $response = Http::withOptions(['force_ip_resolve' => 'v4'])
                ->withUserAgent('BoomBuy-capstone/1.0 (boombuy.store)')
                ->connectTimeout(8)->timeout(15)->retry(2, 300, throw: false)
                ->get(self::NOMINATIM, [
                    'q' => $town . ', ' . PhLocations::provinceLabel($province) . ', Philippines',
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'ph',
                ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        $lat = $response->json('0.lat');
        $lng = $response->json('0.lon');

        return $response->ok() && is_numeric($lat) && is_numeric($lng)
            ? [round((float) $lat, 5), round((float) $lng, 5)]
            : null;
    }
}
