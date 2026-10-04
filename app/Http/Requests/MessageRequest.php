<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** A chat message. Who may send it is checked by the controller. */
class MessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:2000',
            // The product the message is about ("Chat" from a product page).
            'product_id' => 'nullable|integer',
        ];
    }
}
