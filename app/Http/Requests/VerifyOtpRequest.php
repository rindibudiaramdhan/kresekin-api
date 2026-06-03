<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('role')) {
            $this->merge([
                'role' => User::ROLE_BUYER,
            ]);
        }

        if ($this->input('type') !== User::AUTH_TYPE_PHONE) {
            return;
        }

        $phone = (string) $this->input('phone', '');
        $normalizedPhone = preg_replace('/\s+/', '', $phone) ?? '';

        $this->merge([
            'phone' => $normalizedPhone,
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([User::AUTH_TYPE_EMAIL, User::AUTH_TYPE_PHONE])],
            'role' => ['required', Rule::in(User::roles())],
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
