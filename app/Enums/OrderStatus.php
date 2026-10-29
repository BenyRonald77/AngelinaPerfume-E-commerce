<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Alur status pesanan (PRD, Status pesanan). Status hanya maju; mundur hanya lewat
 * pembatalan atau retur yang dicatat admin.
 */
enum OrderStatus: string implements HasColor, HasLabel
{
    case MenungguPembayaran = 'menunggu_pembayaran';
    case Lunas = 'lunas';
    case Diproses = 'diproses';
    case Dikirim = 'dikirim';
    case Selesai = 'selesai';
    case Kedaluwarsa = 'kedaluwarsa';
    case Dibatalkan = 'dibatalkan';
    case Retur = 'retur';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::MenungguPembayaran => 'Menunggu pembayaran',
            self::Lunas => 'Lunas',
            self::Diproses => 'Diproses',
            self::Dikirim => 'Dikirim',
            self::Selesai => 'Selesai',
            self::Kedaluwarsa => 'Kedaluwarsa',
            self::Dibatalkan => 'Dibatalkan',
            self::Retur => 'Retur',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MenungguPembayaran => 'warning',
            self::Lunas, self::Diproses => 'info',
            self::Dikirim => 'primary',
            self::Selesai => 'success',
            self::Kedaluwarsa, self::Dibatalkan, self::Retur => 'danger',
        };
    }

    /** @return list<self> */
    public function next(): array
    {
        return match ($this) {
            self::MenungguPembayaran => [self::Lunas, self::Kedaluwarsa, self::Dibatalkan],
            self::Lunas => [self::Diproses, self::Dibatalkan],
            self::Diproses => [self::Dikirim, self::Dibatalkan],
            self::Dikirim => [self::Selesai, self::Retur],
            default => [],
        };
    }

    public function canMoveTo(self $status): bool
    {
        return in_array($status, $this->next(), true);
    }

    /** Stok dikembalikan bila pesanan berhenti sebelum dikirim */
    public function releasesStock(): bool
    {
        return in_array($this, [self::Kedaluwarsa, self::Dibatalkan], true);
    }

    public function isFinal(): bool
    {
        return $this->next() === [];
    }
}
