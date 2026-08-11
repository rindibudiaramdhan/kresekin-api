<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class OwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        $input = config('api.owner');
        $this->validateConfiguration($input);

        DB::transaction(function () use ($input): void {
            $owner = User::query()->find($input['initial_user_id']);

            if ($owner && $owner->role !== User::ROLE_OWNER) {
                throw new RuntimeException('OWNER_INITIAL_USER_ID sudah digunakan oleh user dengan role selain owner.');
            }

            $this->assertContactIsAvailable($input, $owner);

            if (! $owner) {
                $owner = new User;
                $owner->forceFill(['id' => $input['initial_user_id']]);
            }
            $owner->forceFill([
                'name' => $input['name'],
                'email' => $input['email'] ?: null,
                'phone' => $input['phone'] ?: null,
                'type' => $input['login_type'],
                'role' => User::ROLE_OWNER,
            ])->save();

            $assignedSellerCount = User::query()
                ->where('role', User::ROLE_SELLER)
                ->update(['branch_owner_user_id' => $owner->id]);

            Log::info('owner_initial_seller_assignment_completed', [
                'owner_user_id' => $owner->id,
                'assigned_seller_count' => $assignedSellerCount,
            ]);
        });
    }

    private function validateConfiguration(array $input): void
    {
        if (! Str::isUuid((string) ($input['initial_user_id'] ?? ''))) {
            throw new RuntimeException('OWNER_INITIAL_USER_ID wajib berupa UUID yang valid.');
        }

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9]{8,15}$/', 'required_without:email'],
            'login_type' => ['required', 'in:email,phone'],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException('Konfigurasi owner awal tidak valid: '.$validator->errors()->first());
        }

        if (empty($input[$input['login_type']] ?? null)) {
            throw new RuntimeException('Kontak untuk OWNER_INITIAL_LOGIN_TYPE wajib tersedia.');
        }
    }

    private function assertContactIsAvailable(array $input, ?User $owner): void
    {
        foreach (['email', 'phone'] as $field) {
            if (empty($input[$field])) {
                continue;
            }

            $exists = User::query()
                ->where('role', User::ROLE_OWNER)
                ->where($field, $input[$field])
                ->when($owner, fn ($query) => $query->whereKeyNot($owner->id))
                ->exists();

            if ($exists) {
                throw new RuntimeException(sprintf('%s owner sudah digunakan oleh akun lain.', ucfirst($field)));
            }
        }
    }
}
