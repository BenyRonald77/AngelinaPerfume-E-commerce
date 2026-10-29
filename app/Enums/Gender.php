<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Gender: string implements HasLabel
{
    case Pria = 'pria';
    case Wanita = 'wanita';
    case Unisex = 'unisex';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Pria => 'Pria',
            self::Wanita => 'Wanita',
            self::Unisex => 'Unisex',
        };
    }
}
