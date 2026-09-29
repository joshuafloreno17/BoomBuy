<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'reference_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * User who owns this notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Bootstrap Icons class for this notification's type. */
    public function iconClass(): string
    {
        return match ($this->type) {
            'order', 'order_status' => 'bi-box-seam-fill',
            'delivery', 'delivery_status', 'parcel' => 'bi-truck',
            'payment' => 'bi-credit-card-fill',
            'return_refund' => 'bi-arrow-return-left',
            'account_status', 'buyer' => 'bi-person-badge-fill',
            'complaint' => 'bi-exclamation-triangle-fill',
            'compliance_warning' => 'bi-shield-exclamation',
            'seller' => 'bi-shop',
            'rider' => 'bi-bicycle',
            default => 'bi-bell-fill',
        };
    }
}