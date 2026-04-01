<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'profile_picture_url',
    'rating',
    'category',
    'latitude',
    'longitude',
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

    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
