<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\HousingArea;

class UpdateUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\+?[0-9]{8,15}$/',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'housing_area_id' => ['required', 'integer', Rule::exists('housing_areas', 'id')],
            'address' => ['required', 'string', 'max:1000'],
            'landmark' => ['nullable', 'string', 'max:255'],
        ];
    }
}
