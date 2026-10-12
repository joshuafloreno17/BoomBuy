<?php

namespace App\Http\Requests;

use App\Support\RegistrationRules;
use App\Support\RejectedApplicant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A rider applying: personal details, vehicle, account and the five documents. */
class RiderApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // A rider whose application was rejected may apply again with the same email and phone.
        $reapplying = RejectedApplicant::find((string) $this->input('email'), 'rider');

        return [
            'last_name' => RegistrationRules::name(),
            'first_name' => RegistrationRules::name(),
            'middle_initial' => ['nullable', 'string', 'max:5', 'regex:/^[\p{L}. ]*$/u'],
            'sex' => ['required', 'in:Male,Female'],
            // Riders are 18 or older.
            'birthdate' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString(), 'after:' . now()->subYears(120)->toDateString()],
            'phone' => [...RegistrationRules::phone(), function ($attribute, $value, $fail) use ($reapplying) {
                if (RegistrationRules::phoneTaken((string) $value, $reapplying?->id)) {
                    $fail('This phone number is already registered.');
                }
            }],

            'province' => ['required', 'string', 'max:255'],
            'city_municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:255'],

            'vehicle_type' => ['required', 'in:Motorcycle,Car,Van'],
            'vehicle_model' => ['required', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:50', function ($attribute, $value, $fail) use ($reapplying) {
                if (RegistrationRules::plateTaken((string) $value, $reapplying?->id)) {
                    $fail('This plate number is already registered.');
                }
            }],
            'drivers_license_no' => [...RegistrationRules::licenseNo(), function ($attribute, $value, $fail) use ($reapplying) {
                if (RegistrationRules::licenseTaken((string) $value, $reapplying?->id)) {
                    $fail("This driver's license number is already registered.");
                }
            }],
            'license_expiry' => RegistrationRules::licenseExpiry(),

            'email' => ['required', 'email:filter', 'max:255', Rule::unique('users', 'email')->ignore($reapplying?->id)],
            'password' => RegistrationRules::password(),

            'national_id' => RegistrationRules::document(),
            'drivers_license' => RegistrationRules::document(),
            'profile_selfie' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:' . RegistrationRules::DOCUMENT_MAX_KB],
            'proof_of_address' => RegistrationRules::document(),
            'or_cr' => RegistrationRules::document(),

            'terms' => ['accepted'],
        ];
    }

    /** Emails are compared and saved in lowercase. */
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    /** One stored form of the mobile number (09XXXXXXXXX). */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated();
        $data['phone'] = RegistrationRules::normalizePhone($data['phone']);
        $data['drivers_license_no'] = RegistrationRules::normalizeLicenseNo($data['drivers_license_no']);
        $data['plate_number'] = strtoupper(trim($data['plate_number']));

        return data_get($data, $key, $default);
    }

    public function messages(): array
    {
        return RegistrationRules::messages() + [
            'terms.accepted' => 'Please agree to the Terms & Conditions and Privacy Policy.',
            'birthdate.before_or_equal' => 'You must be at least 18 years old to apply as a rider.',
            'birthdate.after' => 'Please enter a valid birthday.',
        ];
    }
}
