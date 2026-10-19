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
            // JPG or PNG (checked by content), up to 5 MB (A16).
            'profile_photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        $message = 'The profile photo must be a JPG or PNG image of at most 5 MB.';

        return ['profile_photo.image' => $message, 'profile_photo.mimes' => $message, 'profile_photo.max' => $message, 'profile_photo.uploaded' => $message];
    }
}
