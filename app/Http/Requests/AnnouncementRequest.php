<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** An announcement shown to users. Who may send it is checked by the controller. */
class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:2000',
        ];
    }
}
