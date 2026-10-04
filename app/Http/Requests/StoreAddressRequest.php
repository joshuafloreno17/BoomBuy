<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** A delivery address for the buyer's address book. Who may send it is checked by the controller. */
class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => 'nullable|string|max:40',
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{7,20}$/'],
            'address' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid phone number (numbers only).',
        ];
    }
}
