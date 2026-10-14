<?php

namespace App\Models;

use App\Support\PhLocations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A BoomBuy Sorting Center — one per province ("BoomBuy Sorting Center –
 * Cavite"), serving every town in it. city_municipality is where the
 * building is; region groups centers on the admin page.
 *
 * A row with parent_id is a delivery branch of that center ("BoomBuy
 * Branch – Santa Cruz"): the center trucks parcels for its town there, and
 * the branch's riders take them to the door.
 */
class SortingCenter extends Model
{
    protected $fillable = [
        'parent_id',
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

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function branches()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Province Sorting Centers only (not their branches). */
    public function scopeCenters(Builder $query): Builder
    {
        // Older migrations route parcels before branches existed.
        // (Checked again until the column is there, so a migration run doesn't keep the old answer.)
        static $hasBranches = false;
        $hasBranches = $hasBranches || \Illuminate\Support\Facades\Schema::hasColumn('sorting_centers', 'parent_id');

        return $hasBranches ? $query->whereNull('parent_id') : $query;
    }

    public function isBranch(): bool
    {
        return !empty($this->parent_id);
    }

    /** "Imus City, Cavite" — where the building is. */
    public function getTownAttribute(): string
    {
        return $this->city_municipality . ', ' . PhLocations::provinceLabel($this->province);
    }

    /** "Cavite" — the province it serves (a branch: its town). */
    public function getAreaAttribute(): string
    {
        return $this->isBranch() ? $this->town : PhLocations::provinceLabel($this->province);
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

    public static function branchNameFor(string $city): string
    {
        return 'BoomBuy Branch – ' . $city;
    }
}
