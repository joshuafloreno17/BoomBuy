<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A BoomBuy Sorting Center — one per city/municipality. */
class SortingCenter extends Model
{
    protected $fillable = [
        'name',
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

    /** "Santa Cruz, Laguna" */
    public function getTownAttribute(): string
    {
        return $this->city_municipality . ', ' . $this->province;
    }
}
