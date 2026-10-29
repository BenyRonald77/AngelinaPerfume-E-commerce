<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StockReason: string implements HasLabel
{
    case Impor = 'impor';
    case Penyesuaian = 'penyesuaian';
    case Pesanan = 'pesanan';
    case Batal = 'batal';
    case Retur = 'retur';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Impor => 'Impor Shopee',
            self::Penyesuaian => 'Penyesuaian admin',
            self::Pesanan => 'Pesanan',
            self::Batal => 'Pesanan batal',
            self::Retur => 'Retur',
        };
    }
}
