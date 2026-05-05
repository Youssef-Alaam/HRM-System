<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('chat.send');
    }

    public function rules(): array
    {
        return [
            'recipient_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('org_id', $this->user()->org_id),
            ],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'parent_message_id' => [
                'nullable',
                'integer',
                Rule::exists('messages', 'id')->where('org_id', $this->user()->org_id),
            ],
        ];
    }
}
