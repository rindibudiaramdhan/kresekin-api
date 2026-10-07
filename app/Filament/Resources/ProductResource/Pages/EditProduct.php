<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Tenant;
use App\Support\ProductCategoryWriter;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tenantId = $data['tenant_id'] ?? null;
        if (! is_string($tenantId) || ! Str::isUuid($tenantId) || ! Tenant::query()->whereKey($tenantId)->where('owner_user_id', auth()->id())->exists()) {
            throw ValidationException::withMessages(['tenant_id' => 'Pilih tenant yang Anda miliki.']);
        }

        return app(ProductCategoryWriter::class)->attributes($data);
    }
}
