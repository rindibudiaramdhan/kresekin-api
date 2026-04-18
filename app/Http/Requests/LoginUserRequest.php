<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('type') !== User::AUTH_TYPE_PHONE) {
            return;
        }

        $phone = (string) $this->input('phone', '');
        $normalizedPhone = preg_replace('/\s+/', '', $phone) ?? '';

        if (str_starts_with($normalizedPhone, '0')) {
            $normalizedPhone = '+62'.substr($normalizedPhone, 1);
        }

        $this->merge([
            'phone' => $normalizedPhone,
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([User::AUTH_TYPE_EMAIL, User::AUTH_TYPE_PHONE])],
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
