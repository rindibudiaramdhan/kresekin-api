<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Validation\Rule;

class OwnerOrderMonitoringRequest extends OwnerMonitoringRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['nullable', Rule::in(Transaction::statusCodes())],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
