<?php

namespace App\Http\Requests;

use App\Models\PromoBanner;
use App\Support\Categories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A promo banner from Admin → Promo Banners. Who may send it is checked by the controller. */
class PromoBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('link_type');

        return [
            'placement' => ['required', Rule::in(array_keys(PromoBanner::PLACEMENTS))],
            'eyebrow' => 'nullable|string|max:60',
            'title' => 'required|string|max:80',
            'subtitle' => 'nullable|string|max:200',
            'big_text' => 'nullable|string|max:12',
            'button_label' => 'nullable|string|max:30',
            'link_type' => ['required', Rule::in(array_keys(PromoBanner::LINK_TYPES))],
            'link_category' => [Rule::requiredIf($type === 'category'), 'nullable', Rule::in(array_keys(Categories::LIST))],
            'link_product' => [Rule::requiredIf($type === 'product'), 'nullable', 'integer', 'exists:products,id'],
            'link_shop' => [Rule::requiredIf($type === 'shop'), 'nullable', 'integer', Rule::exists('users', 'id')->where('role', 'seller')],
            'style' => ['required', Rule::in(array_keys(PromoBanner::STYLES))],
            // Content checked by Laravel (real image), not the file name.
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_image' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ];
    }

    public function messages(): array
    {
        return [
            'link_category.required' => 'Choose the category the banner links to.',
            'link_product.required' => 'Choose the product the banner links to.',
            'link_shop.required' => 'Choose the shop the banner links to.',
            'ends_at.after_or_equal' => 'The end date cannot be before the start date.',
        ];
    }

    /** The validated fields as stored in promo_banners (without the image). */
    public function bannerData(): array
    {
        $data = $this->validated();
        $type = $data['link_type'];

        return [
            'placement' => $data['placement'],
            'eyebrow' => $data['eyebrow'] ?? null,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'big_text' => $data['big_text'] ?? null,
            'button_label' => $data['button_label'] ?? null,
            'link_type' => $type,
            'link_value' => match ($type) {
                'category' => $data['link_category'],
                'product' => (string) $data['link_product'],
                'shop' => (string) $data['link_shop'],
                default => null,
            },
            'style' => $data['style'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
