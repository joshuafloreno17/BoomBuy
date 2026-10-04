<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** What a rider earns per delivery. Who may send it is checked by the controller. */
class DeliveryFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_fee' => 'required|numeric|min:0',
        ];
    }
}
