<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterAgentWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = (string) $this->input('phone', '');
        $normalizedPhone = preg_replace('/\s+/', '', $phone) ?? '';

        $this->merge([
            'phone' => $normalizedPhone,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where('role', User::ROLE_AGENT),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^\+?[0-9]{8,15}$/',
                Rule::unique('users', 'phone')->where('role', User::ROLE_AGENT),
            ],
            'identity_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar sebagai agent.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'phone.regex' => 'Nomor WhatsApp harus berisi 8 sampai 15 digit dan boleh diawali tanda +.',
            'phone.unique' => 'Nomor WhatsApp sudah terdaftar sebagai agent.',
            'identity_document.required' => 'Dokumen identitas wajib diunggah.',
            'identity_document.mimes' => 'Dokumen identitas harus berupa JPG, PNG, atau PDF.',
            'identity_document.max' => 'Ukuran dokumen identitas maksimal 5MB.',
            'consent.accepted' => 'Persetujuan pemrosesan data wajib dicentang.',
        ];
    }
}
