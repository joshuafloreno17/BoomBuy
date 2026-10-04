<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** The terms, privacy and return policy texts. Who may send it is checked by the controller. */
class PoliciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'terms_policy' => 'nullable|string|max:20000',
            'privacy_policy' => 'nullable|string|max:20000',
            'return_policy' => 'nullable|string|max:20000',
        ];
    }
}
