<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * The line a parcel travels on the buyer's Track package map: the road
 * through its stops (seller's town → Sorting Centers → buyer's town) from
 * OpenStreetMap routing (OSRM), saved in parcel_routes so each trip is
 * fetched once. When the lookup fails the map gets plain curves instead.
 */
class RoadRoute
{
    private const OSRM = 'https://router.project-osrm.org/route/v1/driving/';

    /** A failed lookup is tried again after this long. */
    private const RETRY_MINUTES = 10;

    /**
     * @param  array<int, array{0: float, 1: float}>  $waypoints  [lat, lng] stops, in travel order
     * @return array{points: array, road: bool, km: ?int, stops: int[]}|null
     *         points [lat, lng]; stops = the index in points nearest each waypoint
     */
    public static function through(array $waypoints): ?array
    {
        if (!$waypoints) {
            return null;
        }

        // Every stop in one spot (seller, center and buyer in the same town): no line to draw.
        $first = $waypoints[0];
        if (collect($waypoints)->every(fn ($p) => abs($p[0] - $first[0]) < 0.0005 && abs($p[1] - $first[1]) < 0.0005)) {
            return ['points' => [$first], 'road' => false, 'km' => null, 'stops' => array_fill(0, count($waypoints), 0)];
        }

        $key = implode(';', array_map(fn ($p) => round($p[0], 4) . ',' . round($p[1], 4), $waypoints));
        $saved = DB::table('parcel_routes')->where('waypoints', $key)->first();

        if ($saved && $saved->points) {
            $points = json_decode($saved->points, true);

            return ['points' => $points, 'road' => true, 'km' => $saved->distance_km, 'stops' => self::stopIndexes($points, $waypoints)];
        }

        $recentlyFailed = $saved && $saved->fetched_at
            && now()->subMinutes(self::RETRY_MINUTES)->lt($saved->fetched_at);

        $road = $recentlyFailed ? null : self::fetch($waypoints);

        DB::table('parcel_routes')->updateOrInsert(
            ['waypoints' => $key],
            [
                'points' => $road ? json_encode($road['points']) : null,
                'distance_km' => $road['km'] ?? null,
                'fetched_at' => now(),
            ]
        );

        $points = $road['points'] ?? self::curves($waypoints);

        return ['points' => $points, 'road' => (bool) $road, 'km' => $road['km'] ?? null, 'stops' => self::stopIndexes($points, $waypoints)];
    }

    /** @return array{points: array, km: int}|null */
    private static function fetch(array $waypoints): ?array
    {
        try {
            // IPv4 + one retry: the live PHP's SSL handshake with OSRM now and
            // then stalls on the first try.
            $response = Http::withOptions(['force_ip_resolve' => 'v4'])
                ->connectTimeout(8)->timeout(15)->retry(2, 300, throw: false)
                ->get(
                    self::OSRM . implode(';', array_map(fn ($p) => "{$p[1]},{$p[0]}", $waypoints)),
                    ['overview' => 'simplified', 'geometries' => 'geojson']
                );
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        $coordinates = $response->json('routes.0.geometry.coordinates');

        if (!$response->ok() || $response->json('code') !== 'Ok' || !is_array($coordinates) || count($coordinates) < 2) {
            return null;
        }

        // OSRM gives [lng, lat]; the map wants [lat, lng].
        return [
            'points' => array_map(fn ($c) => [round($c[1], 5), round($c[0], 5)], $coordinates),
            'km' => (int) round(($response->json('routes.0.distance') ?? 0) / 1000),
        ];
    }

    /** For each waypoint, the nearest point on the line (never going backwards). */
    private static function stopIndexes(array $points, array $waypoints): array
    {
        $from = 0;
        $indexes = [];

        foreach ($waypoints as $waypoint) {
            $best = $from;
            $bestDistance = INF;

            for ($i = $from; $i < count($points); $i++) {
                $distance = ($points[$i][0] - $waypoint[0]) ** 2 + ($points[$i][1] - $waypoint[1]) ** 2;
                if ($distance < $bestDistance) {
                    [$best, $bestDistance] = [$i, $distance];
                }
            }

            $indexes[] = $from = $best;
        }

        return $indexes;
    }

    /** Gentle arcs from stop to stop, for when there is no road to draw. */
    private static function curves(array $waypoints, int $segments = 24): array
    {
        $points = [$waypoints[0]];

        for ($w = 1; $w < count($waypoints); $w++) {
            [$lat1, $lng1] = $waypoints[$w - 1];
            [$lat2, $lng2] = $waypoints[$w];

            // Control point: off to one side of the midpoint by a fifth of the distance.
            $controlLat = ($lat1 + $lat2) / 2 - ($lng2 - $lng1) * 0.2;
            $controlLng = ($lng1 + $lng2) / 2 + ($lat2 - $lat1) * 0.2;

            for ($i = 1; $i <= $segments; $i++) {
                $t = $i / $segments;
                $points[] = [
                    round((1 - $t) ** 2 * $lat1 + 2 * (1 - $t) * $t * $controlLat + $t ** 2 * $lat2, 5),
                    round((1 - $t) ** 2 * $lng1 + 2 * (1 - $t) * $t * $controlLng + $t ** 2 * $lng2, 5),
                ];
            }
        }

        return $points;
    }
}
