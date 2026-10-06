<?php

namespace App\Support;

/**
 * Rough map position of every province (its capital), for the Sorting
 * Center map. Good enough to place a pin — not for routing.
 */
class PhCoordinates
{
    public const PROVINCES = [
        'Metro Manila (NCR)' => [14.676, 121.044], 'Abra' => [17.596, 120.618], 'Apayao' => [18.023, 121.184],
        'Benguet' => [16.455, 120.587], 'Ifugao' => [16.802, 121.122], 'Kalinga' => [17.414, 121.444],
        'Mountain Province' => [17.089, 120.977], 'Ilocos Norte' => [18.198, 120.594], 'Ilocos Sur' => [17.574, 120.387],
        'La Union' => [16.616, 120.316], 'Pangasinan' => [16.021, 120.231], 'Batanes' => [20.449, 121.970],
        'Cagayan' => [17.613, 121.727], 'Isabela' => [17.148, 121.889], 'Nueva Vizcaya' => [16.484, 121.149],
        'Quirino' => [16.511, 121.522], 'Aurora' => [15.759, 121.562], 'Bataan' => [14.676, 120.536],
        'Bulacan' => [14.844, 120.811], 'Nueva Ecija' => [15.542, 121.084], 'Pampanga' => [15.034, 120.684],
        'Tarlac' => [15.486, 120.590], 'Zambales' => [15.327, 119.978], 'Batangas' => [13.757, 121.058],
        'Cavite' => [14.430, 120.937], 'Laguna' => [14.279, 121.416], 'Quezon' => [13.931, 121.617],
        'Rizal' => [14.586, 121.176], 'Marinduque' => [13.447, 121.840], 'Occidental Mindoro' => [13.223, 120.596],
        'Oriental Mindoro' => [13.411, 121.180], 'Palawan' => [9.740, 118.735], 'Romblon' => [12.575, 122.270],
        'Albay' => [13.139, 123.735], 'Camarines Norte' => [14.112, 122.955], 'Camarines Sur' => [13.584, 123.274],
        'Catanduanes' => [13.581, 124.231], 'Masbate' => [12.368, 123.620], 'Sorsogon' => [12.971, 124.006],
        'Aklan' => [11.708, 122.364], 'Antique' => [10.744, 121.941], 'Capiz' => [11.585, 122.751],
        'Guimaras' => [10.658, 122.596], 'Iloilo' => [10.720, 122.562], 'Negros Occidental' => [10.676, 122.951],
        'Bohol' => [9.648, 123.855], 'Cebu' => [10.316, 123.885], 'Negros Oriental' => [9.307, 123.308],
        'Siquijor' => [9.214, 123.515], 'Biliran' => [11.561, 124.396], 'Eastern Samar' => [11.608, 125.432],
        'Leyte' => [11.244, 125.004], 'Northern Samar' => [12.499, 124.637], 'Samar' => [11.775, 124.886],
        'Southern Leyte' => [10.133, 124.845], 'Zamboanga del Norte' => [8.589, 123.341],
        'Zamboanga del Sur' => [7.826, 123.437], 'Zamboanga Sibugay' => [7.782, 122.587], 'Bukidnon' => [8.157, 125.127],
        'Camiguin' => [9.250, 124.718], 'Lanao del Norte' => [8.054, 123.791], 'Misamis Occidental' => [8.486, 123.805],
        'Misamis Oriental' => [8.454, 124.632], 'Davao de Oro' => [7.601, 125.966], 'Davao del Norte' => [7.448, 125.809],
        'Davao del Sur' => [7.190, 125.455], 'Davao Occidental' => [6.404, 125.614], 'Davao Oriental' => [6.955, 126.217],
        'Cotabato' => [7.008, 125.089], 'Sarangani' => [6.102, 125.290], 'South Cotabato' => [6.502, 124.847],
        'Sultan Kudarat' => [6.629, 124.605], 'Agusan del Norte' => [8.948, 125.543], 'Agusan del Sur' => [8.606, 125.915],
        'Dinagat Islands' => [10.007, 125.572], 'Surigao del Norte' => [9.784, 125.489], 'Surigao del Sur' => [9.078, 126.199],
        'Basilan' => [6.703, 121.971], 'Lanao del Sur' => [8.000, 124.288], 'Maguindanao del Norte' => [7.017, 124.217],
        'Maguindanao del Sur' => [6.716, 124.786], 'Sulu' => [6.052, 121.002], 'Tawi-Tawi' => [5.029, 119.773],
    ];

    /** [lat, lng] of a province, or null when it isn't known. */
    public static function forProvince(?string $province): ?array
    {
        return self::PROVINCES[$province] ?? null;
    }
}
