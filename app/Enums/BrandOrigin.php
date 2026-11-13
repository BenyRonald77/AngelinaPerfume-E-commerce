<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Asal merek untuk Koleksi di Beranda dan filter katalog (permintaan klien) */
enum BrandOrigin: string implements HasLabel
{
    case Lokal = 'lokal';
    case Designer = 'designer';
    case Dubai = 'dubai';
    case Lainnya = 'lainnya';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Lokal => 'Lokal',
            self::Designer => 'Designer',
            self::Dubai => 'Dubai',
            self::Lainnya => 'Lainnya',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Lokal => 'Brand parfum Indonesia',
            self::Designer => 'Rumah mode dunia',
            self::Dubai => 'Parfum Timur Tengah',
            self::Lainnya => 'Merek lainnya',
        };
    }
}
