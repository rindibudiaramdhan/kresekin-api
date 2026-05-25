<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImageStorage
{
    public function store(UploadedFile $image): string
    {
        return $image->store('products', [
            'disk' => Product::imageDisk(),
            'visibility' => 'public',
        ]);
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk(Product::imageDisk())->delete($path);
    }

    public function url(string $path): string
    {
        return Storage::disk(Product::imageDisk())->url($path);
    }
}
