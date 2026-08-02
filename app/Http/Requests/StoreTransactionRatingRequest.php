<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('comment') && blank($this->input('comment'))) {
            $this->merge(['comment' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Rating wajib diisi.',
            'rating.integer' => 'Rating harus berupa angka bulat.',
            'rating.between' => 'Rating harus bernilai antara 1 sampai 5.',
            'comment.max' => 'Komentar tidak boleh lebih dari 1000 karakter.',
        ];
    }
}
