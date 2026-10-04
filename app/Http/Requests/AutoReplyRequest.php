<?php

namespace App\Http\Requests;

use App\Support\ChatAutomation;
use Illuminate\Foundation\Http\FormRequest;

/** A shop's chat auto-reply: on/off + its text. Who may send it is checked by the controller. */
class AutoReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auto_reply_enabled' => 'nullable|boolean',
            'auto_reply_message' => 'nullable|string|max:' . ChatAutomation::AUTO_REPLY_MAX,
        ];
    }
}
