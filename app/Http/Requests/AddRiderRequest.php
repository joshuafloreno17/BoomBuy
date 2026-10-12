<?php

namespace App\Http\Requests;

use App\Support\RegistrationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A logistics company adding a rider: personal details, license, vehicle and two documents. */
class AddRiderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Emails are compared and saved in lowercase. */
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'first_name' => RegistrationRules::name(),
            'last_name' => RegistrationRules::name(),
            'middle_initial' => ['nullable', 'string', 'max:5', 'regex:/^[\p{L}. ]*$/u'],
            'sex' => ['required', 'in:Male,Female'],
            'birthdate' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString(), 'after:' . now()->subYears(120)->toDateString()],
            'email' => ['required', 'email:filter', 'max:255', Rule::unique('users', 'email')],
            'phone' => [...RegistrationRules::phone(), function ($attribute, $value, $fail) {
                if (RegistrationRules::phoneTaken((string) $value)) {
                    $fail('This mobile number is already in use.');
                }
            }],

            'province' => ['required', 'string', 'max:255'],
            'city_municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:255'],

            'drivers_license_no' => [...RegistrationRules::licenseNo(), function ($attribute, $value, $fail) {
                if (RegistrationRules::licenseTaken((string) $value)) {
                    $fail("This driver's license number is already registered.");
                }
            }],
            'license_expiry' => RegistrationRules::licenseExpiry(),
            'vehicle_type' => ['required', 'in:Motorcycle,Car,Van'],
            'vehicle_model' => ['nullable', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:50', function ($attribute, $value, $fail) {
                if (RegistrationRules::plateTaken((string) $value)) {
                    $fail('This plate number is already registered.');
                }
            }],

            'drivers_license' => RegistrationRules::document(),
            'or_cr' => RegistrationRules::document(),
        ];
    }

    public function messages(): array
    {
        return RegistrationRules::messages() + [
            'birthdate.before_or_equal' => 'The rider must be at least 18 years old.',
            'birthdate.after' => 'Please enter a valid date of birth.',
            'email.unique' => 'This email is already in use.',
        ];
    }

    public function attributes(): array
    {
        return [
            'birthdate' => 'date of birth',
            'phone' => 'mobile number',
            'drivers_license_no' => "driver's license number",
            'license_expiry' => 'license expiry date',
            'drivers_license' => "driver's license",
            'or_cr' => 'vehicle OR/CR',
        ];
    }
}
