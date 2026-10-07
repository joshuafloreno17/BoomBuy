<?php

namespace App\Http\Requests;

use App\Support\RejectedApplicant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A rider applying: personal details, vehicle, account and the five documents. */
class RiderApplicationRequest extends FormRequest
{
    private const DOCUMENT = ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // A rider whose application was rejected may apply again with the same email and phone.
        $reapplying = RejectedApplicant::find((string) $this->input('email'), 'rider');

        return [
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:5'],
            'sex' => ['required', 'in:Male,Female'],
            // Riders are 18 or older.
            'birthdate' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString(), 'after:' . now()->subYears(120)->toDateString()],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($reapplying?->id)],

            'province' => ['required', 'string', 'max:255'],
            'city_municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:255'],

            'vehicle_type' => ['required', 'in:Motorcycle,Car,Van'],
            'vehicle_model' => ['required', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:50'],

            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($reapplying?->id)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            'national_id' => self::DOCUMENT,
            'drivers_license' => self::DOCUMENT,
            'profile_selfie' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'proof_of_address' => self::DOCUMENT,
            'or_cr' => self::DOCUMENT,

            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'Please agree to the Terms & Conditions and Privacy Policy.',
            'birthdate.before_or_equal' => 'You must be at least 18 years old to apply as a rider.',
            'birthdate.after' => 'Please enter a valid birthday.',
        ];
    }
}
