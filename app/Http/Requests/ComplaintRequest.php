<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** A complaint filed by any signed-in user. Who may send it is checked by the controller. */
class ComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:150',
            'description' => 'required|string|max:2000',
            'order_id' => 'nullable|integer',
            'against_user_id' => 'nullable|integer',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
