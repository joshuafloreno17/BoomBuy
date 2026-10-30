<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** The prohibited-items word list. Who may send it is checked by the controller. */
class ProhibitedWordsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prohibited_words' => 'required|string|max:5000',
        ];
    }
}
