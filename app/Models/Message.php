<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'message',
        'product_id',
        'kind',
        'order_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public const TEXT = 'text';
    public const AUTO_REPLY = 'auto_reply';
    public const ORDER_UPDATE = 'order_update';

    /** Typed by a person (not an auto-reply or order update). */
    public function isText(): bool
    {
        return ($this->kind ?? self::TEXT) === self::TEXT;
    }

    /** The product the message asks about ("Chat" from a product page), if any. */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
