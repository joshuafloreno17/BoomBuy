<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What the buyer's Track package map shows for one order: the road from the
 * seller's town through the Sorting Centers to the buyer's town, a landmark
 * per center, and where the truck is.
 */
class TrackMap
{
    /** Timeline stop (OrderTimeline rank) → the order column naming its center. */
    private const CENTER_STOPS = [5 => 'origin_branch_id', 6 => 'origin_center_id', 8 => 'destination_center_id', 9 => 'branch_id'];

    /**
     * @param  Collection  $centers  SortingCenter rows on the trip, by id
     * @return array{route: ?array, truck: ?array, landmarks: array, from: ?string, to: ?string}
     */
    public static function build(object $order, ?Collection $plan, Collection $centers): array
    {
        $seller = ParcelRoute::sellerLocation((int) DB::table('order_items')->where('order_id', $order->id)->value('seller_id'));
        $pickup = ($order->fulfillment ?? 'delivery') === 'pickup';

        // The stops in travel order: [rank or 'seller'/'buyer', [lat, lng]].
        $stops = [['seller', PlaceCoordinates::of($seller['city'], $seller['province'])]];
        $stopCenter = [];
        foreach (self::CENTER_STOPS as $rank => $column) {
            $center = $centers->get($order->$column ?? 0);
            // The buyer's center is the seller's when the parcel stays in the province.
            if (!$center || ($rank === 8 && (int) $order->destination_center_id === (int) ($order->origin_center_id ?? 0))) {
                continue;
            }
            $stops[] = [$rank, PlaceCoordinates::of($center->city_municipality, $center->province)];
            $stopCenter[$rank] = $center;
        }
        if (!$pickup) {
            $stops[] = ['buyer', PlaceCoordinates::of($order->shipping_city ?? null, $order->shipping_province ?? null)];
        }

        $stops = array_values(array_filter($stops, fn ($stop) => $stop[1] !== null));
        $route = RoadRoute::through(array_column($stops, 1));

        $sellerTown = implode(', ', array_filter([$seller['city'], $seller['province'] ? PhLocations::provinceLabel($seller['province']) : null]));
        $buyerTown = implode(', ', array_filter([$order->shipping_city ?? null, !empty($order->shipping_province) ? PhLocations::provinceLabel($order->shipping_province) : null]));
        $collectAt = $pickup ? $centers->get(ParcelRoute::collectionPointId($order) ?? 0) : null;

        if (!$route) {
            return ['route' => null, 'truck' => null, 'landmarks' => [], 'from' => $sellerTown ?: null, 'to' => $buyerTown ?: null];
        }

        // Where each stop is on the line.
        $at = [];
        foreach ($stops as $i => [$name]) {
            $at[$name] = $route['stops'][$i];
        }

        $landmarks = [];
        foreach ($plan ?? [] as $stop) {
            $center = $stopCenter[$stop->rank] ?? null;
            $point = $center ? collect($stops)->first(fn ($s) => $s[0] === $stop->rank)[1] ?? null : null;

            if ($point) {
                $landmarks[$stop->rank] = [
                    'name' => $center->name,
                    'place' => $center->town,
                    'address' => $center->address,
                    'lat' => $point[0],
                    'lng' => $point[1],
                    'reached' => $stop->done,
                    'here' => $stop->current,
                    'at' => $stop->at ? Carbon::parse($stop->at)->format('M j · g:i A') : null,
                ];
            }
        }

        return [
            'route' => $route,
            'truck' => $plan ? self::truck($route, $at, (int) optional($plan->firstWhere('current', true))->rank, $pickup) : null,
            'landmarks' => $landmarks,
            'from' => $sellerTown ?: null,
            'to' => $collectAt ? $collectAt->name : ($buyerTown ?: null),
        ];
    }

    /**
     * The truck's point on the line for the stop the order last reached:
     * none while the seller still has the parcel, then stop by stop, halfway
     * between centers while in transit and on the last leg while out for delivery.
     */
    private static function truck(array $route, array $at, int $rank, bool $pickup): ?array
    {
        if ($rank < 4) {
            return null;
        }

        $last = count($route['points']) - 1;
        $index = function (array $names) use ($at) {
            foreach ($names as $name) {
                if (isset($at[$name])) {
                    return $at[$name];
                }
            }

            return 0;
        };
        $between = fn (int $a, int $b) => intdiv($a + $b, 2);

        $point = match (true) {
            $rank === 4 => $index(['seller']),
            $rank === 5 => $index([5, 'seller']),
            $rank === 6 => $index([6, 5, 'seller']),
            $rank === 7 => $between($index([6, 5, 'seller']), $index([8, 9, 'buyer'])),
            $rank === 8 => $index([8, 6]),
            $rank === 9 => $index([9, 8, 6]),
            $rank === 10 => $pickup ? $index([9, 8, 6]) : $between($index([9, 8, 6]), $last),
            default => $pickup ? $index([9, 8, 6]) : $last,
        };

        return $route['points'][$point] ?? null;
    }
}
