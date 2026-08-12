<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OwnerUserSeeder extends Seeder
{
    private const INITIAL_PROVISIONING_KEY = 'owner-initial-mvp';

    public function run(): void
    {
        [$owner, $created, $assignedSellerCount] = DB::transaction(function (): array {
            $owner = User::query()
                ->where('internal_provisioning_key', self::INITIAL_PROVISIONING_KEY)
                ->first();

            $created = false;

            if (! $owner) {
                $owner = User::query()->create([
                    'name' => 'Owner Awal',
                    'email' => $this->generateUniqueEmail(),
                    'phone' => null,
                    'type' => User::AUTH_TYPE_EMAIL,
                    'role' => User::ROLE_OWNER,
                    'internal_provisioning_key' => self::INITIAL_PROVISIONING_KEY,
                ]);
                $created = true;
            }

            $assignedSellerCount = User::query()
                ->where('role', User::ROLE_SELLER)
                ->whereNull('branch_owner_user_id')
                ->update(['branch_owner_user_id' => $owner->id]);

            Log::info('owner_initial_seller_assignment_completed', [
                'owner_user_id' => $owner->id,
                'assigned_seller_count' => $assignedSellerCount,
            ]);

            return [$owner, $created, $assignedSellerCount];
        });

        $message = $created ? 'Owner awal berhasil dibuat.' : 'Owner awal sudah tersedia; akun yang sama digunakan kembali.';
        $this->command?->info($message);
        $this->command?->table(
            ['ID', 'Nama', 'Email login', 'Seller baru di-assign'],
            [[$owner->id, $owner->name, $owner->email, $assignedSellerCount]],
        );
        $this->command?->warn('Akun awal memakai email development yang digenerate. Ubah email/nomor WhatsApp melalui proses administrasi sebelum penggunaan production.');
    }

    private function generateUniqueEmail(): string
    {
        do {
            $email = 'owner-'.Str::lower(Str::random(12)).'@example.test';
        } while (User::query()->where('role', User::ROLE_OWNER)->where('email', $email)->exists());

        return $email;
    }
}
