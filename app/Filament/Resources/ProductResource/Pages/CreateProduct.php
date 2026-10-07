<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Tenant;
use App\Support\ProductCategoryWriter;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenantId = $data['tenant_id'] ?? null;
        if (! is_string($tenantId) || ! Str::isUuid($tenantId) || ! Tenant::query()->whereKey($tenantId)->where('owner_user_id', auth()->id())->exists()) {
            throw ValidationException::withMessages(['tenant_id' => 'Pilih tenant yang Anda miliki.']);
        }

        return app(ProductCategoryWriter::class)->attributes($data);
    }
}
