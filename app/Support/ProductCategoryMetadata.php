<?php

namespace App\Support;

class ProductCategoryMetadata
{
    private const BY_SLUG = [
        'sayur' => [
            'icon_key' => 'vegetables',
            'background_color' => '#E7F6EB',
            'icon_color' => '#67B97A',
        ],
        'buah' => [
            'icon_key' => 'fruits',
            'background_color' => '#FFEAE5',
            'icon_color' => '#FF7A59',
        ],
        'daging' => [
            'icon_key' => 'meat',
            'background_color' => '#EFE8FF',
            'icon_color' => '#9C7CF7',
        ],
        'toiletries' => [
            'icon_key' => 'toiletries',
            'background_color' => '#FFF5DF',
            'icon_color' => '#F4B544',
        ],
        'minuman' => [
            'icon_key' => 'beverages',
            'background_color' => '#DDEEFF',
            'icon_color' => '#4A90E2',
        ],
        'obat' => [
            'icon_key' => 'medicine',
            'background_color' => '#E7F6EB',
            'icon_color' => '#67B97A',
        ],
        'makanan' => [
            'icon_key' => 'food',
            'background_color' => '#FFEAE5',
            'icon_color' => '#FF7A59',
        ],
        'frozen-food' => [
            'icon_key' => 'frozen_food',
            'background_color' => '#EFE8FF',
            'icon_color' => '#9C7CF7',
        ],
        'bayi-anak' => [
            'icon_key' => 'baby_kids',
            'background_color' => '#FFF5DF',
            'icon_color' => '#F4B544',
        ],
        'home-care' => [
            'icon_key' => 'home_care',
            'background_color' => '#DDEEFF',
            'icon_color' => '#4A90E2',
        ],
        'alat-tulis' => [
            'icon_key' => 'stationery',
            'background_color' => '#E7F6EB',
            'icon_color' => '#67B97A',
        ],
        'bumbu-dapur' => [
            'icon_key' => 'kitchen_spices',
            'background_color' => '#FFEAE5',
            'icon_color' => '#FF7A59',
        ],
        'personal-care' => [
            'icon_key' => 'personal_care',
            'background_color' => '#EFE8FF',
            'icon_color' => '#9C7CF7',
        ],
        'peralatan-rumah' => [
            'icon_key' => 'home_equipment',
            'background_color' => '#FFF5DF',
            'icon_color' => '#F4B544',
        ],
        'sembako' => [
            'icon_key' => 'groceries',
            'background_color' => '#DDEEFF',
            'icon_color' => '#4A90E2',
        ],
    ];

    public static function forSlug(?string $slug): array
    {
        return self::BY_SLUG[$slug ?? ''] ?? [
            'icon_key' => 'category',
            'background_color' => '#F3F4F6',
            'icon_color' => '#6B7280',
        ];
    }
}
