<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** A seller creating a voucher for their shop. Who may send it is checked by the controller. */
class StoreVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:30|unique:vouchers,code',
            'discount_type' => 'required|in:percentage,fixed',
            // A percentage over 100 would discount more than the seller's
            // items are worth and eat into the rest of the buyer's order.
            'discount_value' => 'required|numeric|min:0.01' . ($this->input('discount_type') === 'percentage' ? '|max:100' : ''),
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'per_buyer_limit' => 'nullable|integer|min:1',
            // A date already passed would make a voucher nobody can use.
            'expires_at' => 'nullable|date|after_or_equal:today',
        ];
    }
}
