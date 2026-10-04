<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** The platform commission on delivered sales, in percent. Who may send it is checked by the controller. */
class CommissionRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commission_rate' => 'required|numeric|min:0|max:100',
        ];
    }
}
