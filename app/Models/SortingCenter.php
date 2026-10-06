<?php

namespace App\Models;

use App\Support\PhLocations;
use Illuminate\Database\Eloquent\Model;

/**
 * A BoomBuy Sorting Center — one per province ("BoomBuy Sorting Center –
 * Cavite"), serving every town in it. city_municipality is where the
 * building is; region groups centers on the admin page.
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

    /** "Imus City, Cavite" — where the building is. */
    public function getTownAttribute(): string
    {
        return $this->city_municipality . ', ' . PhLocations::provinceLabel($this->province);
    }

    /** "Cavite" — the province it serves. */
    public function getAreaAttribute(): string
    {
        return PhLocations::provinceLabel($this->province);
    }

    /** @return string[] the provinces it serves (its own) */
    public function getProvincesAttribute(): array
    {
        return [$this->province];
    }

    public static function nameFor(string $province): string
    {
        return 'BoomBuy Sorting Center – ' . PhLocations::provinceLabel($province);
    }
}
