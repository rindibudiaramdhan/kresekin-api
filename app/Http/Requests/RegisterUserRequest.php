<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'type' => ['required', Rule::in([User::AUTH_TYPE_EMAIL, User::AUTH_TYPE_PHONE])],
            'email' => [
                'nullable',
                'email',
                'required_if:type,email',
                Rule::unique('users', 'email')->where('role', $role),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'required_if:type,phone',
                'regex:/^\+?[0-9]{8,15}$/',
                Rule::unique('users', 'phone')->where('role', $role),
            ],
        ];
    }
}
