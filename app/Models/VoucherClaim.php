<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A voucher in a buyer's wallet (My Vouchers). */
class VoucherClaim extends Model
{
    protected $fillable = ['voucher_id', 'user_id'];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}
