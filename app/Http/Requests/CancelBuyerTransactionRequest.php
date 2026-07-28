<?php

namespace App\Http\Requests;

use App\Models\CancellationReasonCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CancelBuyerTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason_category_id' => [
                'required',
                'uuid',
                Rule::exists('cancellation_reason_categories', 'id')->where('is_active', true),
            ],
            'cancellation_reason_text' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('cancellation_reason_category_id')) {
                return;
            }

            $category = CancellationReasonCategory::query()
                ->where('is_active', true)
                ->find($this->input('cancellation_reason_category_id'));

            if ($category?->allows_free_text && ! $this->filled('cancellation_reason_text')) {
                $validator->errors()->add(
                    'cancellation_reason_text',
                    'Alasan pembatalan wajib diisi untuk kategori Alasan Lainnya.',
                );
            }
        });
    }
}
