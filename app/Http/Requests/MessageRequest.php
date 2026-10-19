<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A chat message (Seller/Buyer Modules A16, A25): 1 to 1,000 characters
 * and/or one photo (JPG or PNG by content, up to 5 MB). An empty or
 * spaces-only message is not sent. Who may send it is checked by the controller.
 */
class MessageRequest extends FormRequest
{
    public const MAX = 1000;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'nullable|string|max:' . self::MAX . '|required_without:attachment',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            // The product the message is about ("Chat" from a product page).
            'product_id' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'message.required_without' => 'Type a message or attach a photo.',
            'message.max' => 'A message can have at most ' . number_format(self::MAX) . ' characters.',
            'attachment.mimes' => 'Attach a JPG or PNG photo of at most 5 MB.',
            'attachment.max' => 'Attach a JPG or PNG photo of at most 5 MB.',
            'attachment.file' => 'Attach a JPG or PNG photo of at most 5 MB.',
            'attachment.uploaded' => 'Attach a JPG or PNG photo of at most 5 MB.',
        ];
    }
}
