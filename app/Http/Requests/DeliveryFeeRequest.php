<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** The four delivery fees, one per distance tier (see App\Support\DeliveryFee::ZONE_SETTINGS). */
class DeliveryFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_fee' => 'required|numeric|min:0|max:10000',
            'delivery_fee_province' => 'required|numeric|min:0|max:10000',
            'delivery_fee_island' => 'required|numeric|min:0|max:10000',
            'delivery_fee_far' => 'required|numeric|min:0|max:10000',
        ];
    }
}
