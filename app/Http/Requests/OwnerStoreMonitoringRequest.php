<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class OwnerStoreMonitoringRequest extends OwnerMonitoringRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'sort' => ['nullable', Rule::in(['sales_amount', 'order_count', 'item_quantity', 'last_order_at', 'store_name'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
