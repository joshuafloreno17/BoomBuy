<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** A new profile picture, for any role. Who may upload is checked by the controller. */
class ProfilePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}
