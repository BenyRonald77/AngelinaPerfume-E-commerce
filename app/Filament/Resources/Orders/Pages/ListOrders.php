<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    /** Tab mengikuti urutan kerja admin: bayar, kemas, kirim */
    public function getTabs(): array
    {
        $tab = fn (string $label, array $statuses) => Tab::make($label)
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $statuses))
            ->badge(Order::whereIn('status', $statuses)->count() ?: null);

        return [
            'semua' => Tab::make('Semua'),
            'bayar' => $tab('Menunggu bayar', [OrderStatus::MenungguPembayaran]),
            'kemas' => $tab('Perlu dikemas', [OrderStatus::Lunas, OrderStatus::Diproses]),
            'kirim' => $tab('Dikirim', [OrderStatus::Dikirim]),
            'selesai' => Tab::make('Selesai')->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Selesai)),
            'batal' => Tab::make('Batal & retur')->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [OrderStatus::Kedaluwarsa, OrderStatus::Dibatalkan, OrderStatus::Retur])),
        ];
    }
}
