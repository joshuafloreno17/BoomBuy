<?php

namespace App\Http\Requests;

use App\Support\RegistrationRules;
use Illuminate\Foundation\Http\FormRequest;

/** The seller's Store Settings: name, description, contact number, logo and banner. Who may send it is checked by the controller. */
class UpdateShopRequest extends FormRequest
{
    /** Logo and banner: JPG or PNG (checked by content), at most 5 MB. */
    public const IMAGE_RULES = ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // "  RV Gadget Hub  " and "     " are judged without the spaces.
        $this->merge([
            'business_name' => is_string($this->business_name) ? trim($this->business_name) : $this->business_name,
            'shop_phone' => is_string($this->shop_phone) ? preg_replace('/\s+/', '', $this->shop_phone) : $this->shop_phone,
        ]);
    }

    public function rules(): array
    {
        return [
            'business_name' => RegistrationRules::shopName(),
            'shop_description' => 'nullable|string|max:500',
            'shop_phone' => RegistrationRules::phone(),
            'shop_logo' => self::IMAGE_RULES,
            'shop_banner' => self::IMAGE_RULES,
        ];
    }

    public function messages(): array
    {
        return [
            'business_name.required' => 'Please enter your shop name.',
            'business_name.min' => 'Your shop name must be 3 to 30 characters.',
            'business_name.max' => 'Your shop name must be 3 to 30 characters.',
            'shop_description.max' => 'The description can have at most 500 characters.',
            'shop_phone.required' => 'Please enter your shop\'s contact number.',
            'shop_logo.mimes' => 'The logo must be a JPG or PNG image of at most 5 MB.',
            'shop_logo.max' => 'The logo must be a JPG or PNG image of at most 5 MB.',
            'shop_logo.file' => 'The logo must be a JPG or PNG image of at most 5 MB.',
            'shop_logo.uploaded' => 'The logo must be a JPG or PNG image of at most 5 MB.',
            'shop_banner.mimes' => 'The banner must be a JPG or PNG image of at most 5 MB.',
            'shop_banner.max' => 'The banner must be a JPG or PNG image of at most 5 MB.',
            'shop_banner.file' => 'The banner must be a JPG or PNG image of at most 5 MB.',
            'shop_banner.uploaded' => 'The banner must be a JPG or PNG image of at most 5 MB.',
        ];
    }
}
