<?php

namespace App\Http\Requests;

use App\Models\CancellationReasonCategory;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSellerOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status_code' => [
                'required',
                'string',
                Rule::in([
                    Transaction::STATUS_CODE_ACCEPTED_BY_STORE,
                    Transaction::STATUS_CODE_PROCESSING,
                    Transaction::STATUS_CODE_ON_THE_WAY,
                    Transaction::STATUS_CODE_READY_FOR_PICKUP,
                    Transaction::STATUS_CODE_COMPLETED,
                    Transaction::STATUS_CODE_CANCELED,
                ]),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'cancellation_reason_category_id' => [
                'nullable',
                'uuid',
                Rule::exists('cancellation_reason_categories', 'id')->where('is_active', true),
            ],
            'cancellation_reason_text' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status_code') !== Transaction::STATUS_CODE_CANCELED) {
                return;
            }

            if (! $this->filled('cancellation_reason_category_id')) {
                $validator->errors()->add('cancellation_reason_category_id', 'Kategori alasan pembatalan wajib dipilih.');

                return;
            }

            $category = CancellationReasonCategory::query()
                ->where('is_active', true)
                ->find($this->input('cancellation_reason_category_id'));

            if ($category?->allows_free_text && ! $this->filled('cancellation_reason_text')) {
                $validator->errors()->add('cancellation_reason_text', 'Alasan pembatalan wajib diisi untuk kategori Alasan Lainnya.');
            }
        });
    }
}
