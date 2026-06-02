<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSellerTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $ownerPhone = $this->input('owner_phone');

        if (is_string($ownerPhone)) {
            $ownerPhone = preg_replace('/\s+/', '', $ownerPhone) ?? '';

            if (str_starts_with($ownerPhone, '0')) {
                $ownerPhone = '+62'.substr($ownerPhone, 1);
            }
        }

        $this->merge([
            'owner_phone' => $ownerPhone,
            'agent_code' => is_string($this->input('agent_code')) ? strtoupper($this->input('agent_code')) : $this->input('agent_code'),
        ]);
    }

    public function rules(): array
    {
        return [
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\+?[0-9]{8,15}$/',
                Rule::unique('users', 'phone')->where('role', User::ROLE_SELLER)->ignore($this->user()?->id),
            ],
            'owner_email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where('role', User::ROLE_SELLER)->ignore($this->user()?->id),
            ],
            'agent_code' => [
                'nullable',
                'string',
                Rule::exists('users', 'agent_code')->where('role', 'agent'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'profile_picture_url' => ['nullable', 'url', 'max:255'],
            'category_id' => ['required', 'uuid', Rule::exists('product_categories', 'id')],
            'location' => ['required', 'string', 'max:1000'],
            'housing_area_ids' => ['required', 'array', 'min:1', 'max:3'],
            'housing_area_ids.*' => ['required', 'uuid', 'distinct', Rule::exists('housing_areas', 'id')],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'open_time' => ['nullable', 'date_format:H:i'],
            'close_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
