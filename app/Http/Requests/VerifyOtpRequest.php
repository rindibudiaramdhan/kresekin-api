<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['email', 'phone'])],
            'otp' => ['required', 'digits:6'],
            'email' => [
                'nullable',
                'email',
                'required_if:type,email',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'required_if:type,phone',
                'regex:/^\+?[0-9]{8,15}$/',
            ],
        ];
    }
}
