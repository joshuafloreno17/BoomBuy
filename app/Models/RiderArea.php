<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderArea extends Model
{
    protected $fillable = [
        'rider_id',
        'province',
        'city_municipality',
    ];

    public function rider()
    {
        return $this->belongsTo(User::class, 'rider_id');
    }
}
