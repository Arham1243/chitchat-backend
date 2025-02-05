<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFriendRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'recipient_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_id.required' => 'The recipient ID is required.',
            'recipient_id.integer' => 'The recipient ID must be an integer.',
            'recipient_id.exists' => 'The recipient does not exist.',
        ];
    }
}
