<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** The seller's shop name and description. Who may send it is checked by the controller. */
class UpdateShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_name' => 'required|string|min:3|max:60',
            'shop_description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'business_name.required' => 'Please enter your shop name.',
            'business_name.min' => 'Your shop name needs at least 3 characters.',
        ];
    }
}
