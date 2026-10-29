<?php

namespace App\Filament\Resources\StockMovements;

use App\Enums\StockReason;
use App\Filament\Resources\StockMovements\Pages\ManageStockMovements;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Jejak setiap perubahan stok; hanya dibaca, diisi otomatis */
class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Riwayat stok';

    protected static ?string $modelLabel = 'riwayat stok';

    protected static ?string $pluralModelLabel = 'riwayat stok';

    protected static ?string $slug = 'riwayat-stok';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['variant.product', 'variant.aroma', 'user']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H.i')
                    ->sortable(),
                TextColumn::make('variant.sku')
                    ->label('SKU')
                    ->searchable()
                    ->fontFamily('mono'),
                TextColumn::make('variant.product.name')
                    ->label('Produk')
                    ->description(fn (StockMovement $record) => $record->variant?->aroma?->name)
                    ->wrap(),
                TextColumn::make('change')
                    ->label('Perubahan')
                    ->formatStateUsing(fn (int $state) => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn (int $state) => $state < 0 ? 'danger' : 'success'),
                TextColumn::make('stock_after')
                    ->label('Stok akhir'),
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->badge(),
                TextColumn::make('note')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('Oleh')
                    ->placeholder('Sistem'),
            ])
            ->filters([
                SelectFilter::make('reason')
                    ->label('Alasan')
                    ->options(StockReason::class),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStockMovements::route('/'),
        ];
    }
}
