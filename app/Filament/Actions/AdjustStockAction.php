<?php

namespace App\Filament\Actions;

use App\Enums\StockReason;
use App\Models\ProductVariant;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

/**
 * Mengubah stok satu SKU lewat ProductVariant::adjustStock, sehingga
 * setiap perubahan tercatat di riwayat stok dan stok tidak bisa minus.
 */
class AdjustStockAction
{
    public static function make(): Action
    {
        return Action::make('adjustStock')
            ->label('Ubah stok')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->modalHeading(fn (ProductVariant $record) => "Ubah stok {$record->sku}")
            ->modalDescription(fn (ProductVariant $record) => "Stok sekarang: {$record->stock} botol.")
            ->fillForm(fn (ProductVariant $record) => ['stock' => $record->stock])
            ->schema([
                TextInput::make('stock')
                    ->label('Stok baru')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required(),
                TextInput::make('note')
                    ->label('Alasan')
                    ->placeholder('mis. hitung ulang gudang, botol pecah')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (ProductVariant $record, array $data) {
                $change = (int) $data['stock'] - $record->stock;

                if ($change === 0) {
                    return;
                }

                try {
                    $record->adjustStock($change, StockReason::Penyesuaian, auth()->user(), $data['note']);
                } catch (InvalidArgumentException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title("Stok {$record->sku} menjadi {$record->stock}.")->success()->send();
            });
    }
}
