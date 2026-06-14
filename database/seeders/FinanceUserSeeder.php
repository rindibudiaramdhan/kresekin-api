<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FinanceUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            [
                'email' => 'finance@kresek.in',
                'role' => User::ROLE_FINANCE,
            ],
            [
                'name' => 'Finance Administrator',
                'email_verified_at' => now(),
                'type' => User::AUTH_TYPE_EMAIL,
                'password' => Hash::make('password123'),
            ],
        );
    }
}
