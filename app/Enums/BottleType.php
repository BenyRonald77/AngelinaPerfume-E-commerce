<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BottleType: string implements HasLabel
{
    case VialOles = 'vial_oles';
    case Spray = 'spray';
    case Travel = 'travel';
    case BotolAsli = 'botol_asli';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::VialOles => 'Vial oles',
            self::Spray => 'Spray',
            self::Travel => 'Travel size',
            self::BotolAsli => 'Botol asli',
        };
    }
}
