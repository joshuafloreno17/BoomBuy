<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Hub relay: the Sorting Centers a parcel's truck passes through between the
 * seller's center and the buyer's center, picked automatically.
 *
 * Each leg is at most LEG_KM (straight-line, between the centers' towns). The
 * route is the shortest one made of such legs; a longer leg (a sea crossing,
 * an island with no center close enough) is allowed only when there is no
 * other way, and costs much more so it is never chosen over a land route.
 */
class HubRoute
{
    /** The longest normal leg between two hubs. */
    public const LEG_KM = 150;

    /** A longer leg is only a last resort. */
    private const LONG_LEG_FACTOR = 4;

    /** @var array<string, int[]> */
    private static array $paths = [];

    /** @var array{0: array<int, array{0: float, 1: float}>}|null */
    private static ?array $graph = null;

    /**
     * Every center on the way, both ends included: [origin, hub, hub, …, destination].
     *
     * @return int[]
     */
    public static function path(?int $fromCenterId, ?int $toCenterId): array
    {
        if (!$fromCenterId || !$toCenterId) {
            return array_values(array_filter([$fromCenterId, $toCenterId]));
        }

        if ($fromCenterId === $toCenterId) {
            return [$fromCenterId];
        }

        $key = $fromCenterId . '-' . $toCenterId;

        return self::$paths[$key] ??= self::shortest($fromCenterId, $toCenterId);
    }

    /** The hub after this one on the way to the destination (the destination itself on the last leg). */
    public static function nextHop(int $hereCenterId, int $destinationCenterId): int
    {
        $path = self::path($hereCenterId, $destinationCenterId);

        return (int) ($path[1] ?? $destinationCenterId);
    }

    /** The hubs in between only (not the two ends). */
    public static function relays(?int $fromCenterId, ?int $toCenterId): array
    {
        $path = self::path($fromCenterId, $toCenterId);

        return count($path) > 2 ? array_slice($path, 1, -1) : [];
    }

    /**
     * The hubs to show for one order (route bar, track map). An order that
     * already went straight to the buyer's center before hub relay existed
     * shows none — only parcels still on the way, or ones that really
     * stopped at a hub, list them.
     *
     * @return int[]
     */
    public static function relaysFor(object $order): array
    {
        $relays = self::relays(
            !empty($order->origin_center_id) ? (int) $order->origin_center_id : null,
            !empty($order->destination_center_id) ? (int) $order->destination_center_id : null
        );

        if (!$relays) {
            return [];
        }

        $stillOnTheWay = in_array($order->status ?? '', ['Pending', 'Confirmed', 'Preparing', 'Processing', 'Ready for Pickup', 'Pickup Assigned', 'Picked Up', 'Dropped Off', 'Received at Branch', 'To Sorting Center', 'In Transit'], true)
            || (($order->status ?? '') === 'At Sorting Center' && (int) ($order->current_center_id ?? 0) !== (int) $order->destination_center_id);

        return $stillOnTheWay || DB::table('order_events')->where('order_id', $order->id)->where('title', 'like', '%(hub)%')->exists()
            ? $relays
            : [];
    }

    /** Straight-line km between two centers, or null when one has no position. */
    public static function legKm(int $a, int $b): ?int
    {
        $points = self::points();

        return isset($points[$a], $points[$b]) ? (int) round(self::km($points[$a], $points[$b])) : null;
    }

    /** Forget the computed routes (tests, or after centers change). */
    public static function flush(): void
    {
        self::$paths = [];
        self::$graph = null;
        Cache::forget('hub_route_points');
    }

    /** @return int[] */
    private static function shortest(int $from, int $to): array
    {
        $points = self::points();

        if (!isset($points[$from], $points[$to])) {
            return [$from, $to];
        }

        // Dijkstra over every center (about 80, so a plain array is enough).
        $dist = [$from => 0.0];
        $prev = [];
        $open = array_fill_keys(array_keys($points), true);

        while ($open) {
            $u = null;

            foreach ($open as $id => $_) {
                if (isset($dist[$id]) && ($u === null || $dist[$id] < $dist[$u])) {
                    $u = $id;
                }
            }

            if ($u === null || $u === $to) {
                break;
            }

            unset($open[$u]);

            foreach ($open as $v => $_) {
                $km = self::km($points[$u], $points[$v]);
                $cost = $km <= self::LEG_KM ? $km : $km * self::LONG_LEG_FACTOR;
                $alt = $dist[$u] + $cost;

                if (!isset($dist[$v]) || $alt < $dist[$v]) {
                    $dist[$v] = $alt;
                    $prev[$v] = $u;
                }
            }
        }

        $path = [$to];

        while (end($path) !== $from && isset($prev[end($path)])) {
            $path[] = $prev[end($path)];
        }

        return end($path) === $from ? array_reverse($path) : [$from, $to];
    }

    /** @return array<int, array{0: float, 1: float}> center id => [lat, lng] (saved positions only, no web lookups) */
    private static function points(): array
    {
        if (self::$graph !== null) {
            return self::$graph[0];
        }

        $points = Cache::remember('hub_route_points', now()->addHours(6), function () {
            $out = [];

            foreach (DB::table('sorting_centers')->whereNull('parent_id')->where('is_active', 1)->get(['id', 'province', 'city_municipality']) as $c) {
                $p = PlaceCoordinates::of($c->city_municipality, $c->province, false);

                if ($p) {
                    $out[(int) $c->id] = [(float) $p[0], (float) $p[1]];
                }
            }

            return $out;
        });

        self::$graph = [$points];

        return $points;
    }

    /** Great-circle km. */
    private static function km(array $a, array $b): float
    {
        $rad = M_PI / 180;
        $dLat = ($b[0] - $a[0]) * $rad;
        $dLng = ($b[1] - $a[1]) * $rad;
        $h = sin($dLat / 2) ** 2 + cos($a[0] * $rad) * cos($b[0] * $rad) * sin($dLng / 2) ** 2;

        return 6371 * 2 * asin(min(1, sqrt($h)));
    }
}
