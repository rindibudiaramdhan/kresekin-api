<?php

namespace Database\Seeders;

// use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            // FinanceUserSeeder::class,
            HousingAreaSeeder::class,
            ProductCategorySeeder::class,
            ProductUnitSeeder::class,
            // TenantSeeder::class,
            // PromoProductSeeder::class,
            // PromoCodeSeeder::class,
            OrderTimeOptionSeeder::class,
            PaymentMethodSeeder::class,
            DeliveryMethodSeeder::class,
        ]);

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
