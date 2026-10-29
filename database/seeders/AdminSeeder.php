<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Membuat akun admin dari ADMIN_EMAIL dan ADMIN_PASSWORD di .env.
     */
    public function run(): void
    {
        $config = config('toko.admin');

        if (blank($config['email']) || blank($config['password'])) {
            $this->command?->warn('ADMIN_EMAIL atau ADMIN_PASSWORD kosong di .env, akun admin tidak dibuat.');

            return;
        }

        $admin = User::firstOrNew(['email' => $config['email']]);
        $admin->name = $config['name'];
        $admin->password = $config['password'];
        $admin->role = Role::Admin;
        $admin->email_verified_at ??= now();
        $admin->save();

        $this->command?->info("Akun admin siap: {$admin->email}");
    }
}
