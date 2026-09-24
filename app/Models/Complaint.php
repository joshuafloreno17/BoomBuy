<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'complainant_id',
        'complainant_role',
        'against_user_id',
        'order_id',
        'subject',
        'description',
        'evidence',
        'status',
        'admin_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function complainant()
    {
        return $this->belongsTo(User::class, 'complainant_id');
    }

    public function against()
    {
        return $this->belongsTo(User::class, 'against_user_id');
    }
}
