<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasLabel
{
    case Pembeli = 'pembeli';
    case Admin = 'admin';
    // Disiapkan untuk karyawan pengemas; belum dipakai di MVP
    case Staf = 'staf';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Pembeli => 'Pembeli',
            self::Admin => 'Admin',
            self::Staf => 'Staf gudang',
        };
    }
}
