<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'owner_user_id',
    'name',
    'profile_picture_url',
    'rating',
    'category',
    'latitude',
    'longitude',
    'open_time',
    'close_time',
])]
class Tenant extends Model
{
    public const CATEGORY_VEGETABLES = 'Sayur';
    public const CATEGORY_FRUITS = 'Buah';
    public const CATEGORY_MEAT = 'Daging';
    public const CATEGORY_TOILETRIES = 'Toiletries';
    public const CATEGORY_BEVERAGES = 'Minuman';
    public const CATEGORY_MEDICINE = 'Obat';
    public const CATEGORY_FOOD = 'Makanan';
    public const CATEGORY_FROZEN_FOOD = 'Frozen Food';
    public const CATEGORY_BABY = 'Bayi & Anak';
    public const CATEGORY_HOME_CARE = 'Home Care';
    public const CATEGORY_STATIONERY = 'Alat Tulis';
    public const CATEGORY_SPICES = 'Bumbu Dapur';
    public const CATEGORY_PERSONAL_CARE = 'Personal Care';
    public const CATEGORY_HOME_EQUIPMENT = 'Peralatan Rumah';
    public const CATEGORY_GROCERIES = 'Sembako';

    public const CATEGORIES = [
        self::CATEGORY_VEGETABLES,
        self::CATEGORY_FRUITS,
        self::CATEGORY_MEAT,
        self::CATEGORY_TOILETRIES,
        self::CATEGORY_BEVERAGES,
        self::CATEGORY_MEDICINE,
        self::CATEGORY_FOOD,
        self::CATEGORY_FROZEN_FOOD,
        self::CATEGORY_BABY,
        self::CATEGORY_HOME_CARE,
        self::CATEGORY_STATIONERY,
        self::CATEGORY_SPICES,
        self::CATEGORY_PERSONAL_CARE,
        self::CATEGORY_HOME_EQUIPMENT,
        self::CATEGORY_GROCERIES,
    ];

    public const CATEGORY_UI_METADATA = [
        self::CATEGORY_VEGETABLES => [
            'icon_key' => 'vegetables',
            'background_color' => '#E7F6EB',
            'icon_color' => '#67B97A',
        ],
        self::CATEGORY_FRUITS => [
            'icon_key' => 'fruits',
            'background_color' => '#FFEAE5',
            'icon_color' => '#FF7A59',
        ],
        self::CATEGORY_MEAT => [
            'icon_key' => 'meat',
            'background_color' => '#EFE8FF',
            'icon_color' => '#9C7CF7',
        ],
        self::CATEGORY_TOILETRIES => [
            'icon_key' => 'toiletries',
            'background_color' => '#FFF5DF',
            'icon_color' => '#F4B544',
        ],
        self::CATEGORY_BEVERAGES => [
            'icon_key' => 'beverages',
            'background_color' => '#DDEEFF',
            'icon_color' => '#4A90E2',
        ],
        self::CATEGORY_MEDICINE => [
            'icon_key' => 'medicine',
            'background_color' => '#E7F6EB',
            'icon_color' => '#67B97A',
        ],
        self::CATEGORY_FOOD => [
            'icon_key' => 'food',
            'background_color' => '#FFEAE5',
            'icon_color' => '#FF7A59',
        ],
        self::CATEGORY_FROZEN_FOOD => [
            'icon_key' => 'frozen_food',
            'background_color' => '#EFE8FF',
            'icon_color' => '#9C7CF7',
        ],
        self::CATEGORY_BABY => [
            'icon_key' => 'baby_kids',
            'background_color' => '#FFF5DF',
            'icon_color' => '#F4B544',
        ],
        self::CATEGORY_HOME_CARE => [
            'icon_key' => 'home_care',
            'background_color' => '#DDEEFF',
            'icon_color' => '#4A90E2',
        ],
        self::CATEGORY_STATIONERY => [
            'icon_key' => 'stationery',
            'background_color' => '#E7F6EB',
            'icon_color' => '#67B97A',
        ],
        self::CATEGORY_SPICES => [
            'icon_key' => 'kitchen_spices',
            'background_color' => '#FFEAE5',
            'icon_color' => '#FF7A59',
        ],
        self::CATEGORY_PERSONAL_CARE => [
            'icon_key' => 'personal_care',
            'background_color' => '#EFE8FF',
            'icon_color' => '#9C7CF7',
        ],
        self::CATEGORY_HOME_EQUIPMENT => [
            'icon_key' => 'home_equipment',
            'background_color' => '#FFF5DF',
            'icon_color' => '#F4B544',
        ],
        self::CATEGORY_GROCERIES => [
            'icon_key' => 'groceries',
            'background_color' => '#DDEEFF',
            'icon_color' => '#4A90E2',
        ],
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function isOpenAt(string $currentTime): bool
    {
        if (! $this->open_time || ! $this->close_time) {
            return false;
        }

        if ($this->open_time <= $this->close_time) {
            return $currentTime >= $this->open_time && $currentTime <= $this->close_time;
        }

        return $currentTime >= $this->open_time || $currentTime <= $this->close_time;
    }

    public function operatingHoursLabel(): ?string
    {
        if (! $this->open_time || ! $this->close_time) {
            return null;
        }

        return sprintf('Buka %s sd %s', $this->open_time, $this->close_time);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public static function categoryUiMetadata(string $category): array
    {
        return self::CATEGORY_UI_METADATA[$category] ?? [
            'icon_key' => str($category)->slug('_')->toString(),
            'background_color' => '#F3F4F6',
            'icon_color' => '#6B7280',
        ];
    }
}
