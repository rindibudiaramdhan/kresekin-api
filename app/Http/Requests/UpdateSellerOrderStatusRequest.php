<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                    Transaction::STATUS_CODE_COMPLETED,
                    Transaction::STATUS_CODE_CANCELED,
                ]),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
