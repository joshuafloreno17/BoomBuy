<?php

namespace App\Models;

use App\Support\PhLocations;
use Illuminate\Database\Eloquent\Model;

/**
 * A BoomBuy Sorting Center — one per region ("BoomBuy Sorting Center –
 * CALABARZON"), covering every province in it. province/city is where the
 * building is.
 */
class SortingCenter extends Model
{
    protected $fillable = [
        'name',
        'region',
        'province',
        'city_municipality',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function staff()
    {
        return $this->hasMany(User::class, 'sorting_center_id');
    }

    /** "Calamba City, Laguna" — where the building is. */
    public function getTownAttribute(): string
    {
        return $this->city_municipality . ', ' . str_replace(' (NCR)', '', $this->province);
    }

    /** "CALABARZON" — the region it serves. */
    public function getAreaAttribute(): string
    {
        return PhLocations::regionLabel($this->region) ?? $this->town;
    }

    /** @return string[] the provinces it serves */
    public function getProvincesAttribute(): array
    {
        return PhLocations::REGIONS[$this->region]['provinces'] ?? [$this->province];
    }

    public static function nameFor(string $region): string
    {
        return 'BoomBuy Sorting Center – ' . (PhLocations::regionLabel($region) ?? $region);
    }
}
