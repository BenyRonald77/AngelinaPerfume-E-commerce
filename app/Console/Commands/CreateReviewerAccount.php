<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Akun uji untuk peninjau Shopee Open Platform (hanya panel /tinjau).
 * Password acak hanya tampil di terminal; menjalankan ulang berarti mengganti password.
 */
class CreateReviewerAccount extends Command
{
    private const EMAIL = 'peninjau@angelia.test';

    protected $signature = 'toko:akun-peninjau';

    protected $description = 'Buat akun peninjau hanya-baca, atau ganti passwordnya';

    public function handle(): int
    {
        $password = Str::password(20, symbols: false);

        $user = User::firstOrNew(['email' => self::EMAIL]);
        $user->name = 'Peninjau Shopee';
        $user->password = Hash::make($password);
        $user->role = Role::Peninjau;
        $user->email_verified_at ??= now();
        $user->save();

        $this->line('Halaman login : '.url('/tinjau/login'));
        $this->line('Email         : '.self::EMAIL);
        $this->line('Password      : '.$password);

        return self::SUCCESS;
    }
}
