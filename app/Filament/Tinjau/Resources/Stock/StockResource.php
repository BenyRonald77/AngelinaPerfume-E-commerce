<?php

namespace App\Filament\Tinjau\Resources\Stock;

use App\Filament\Tinjau\Resources\Stock\Pages\ListStock;
use App\Models\ProductVariant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Daftar stok per SKU beserta kode variasi Shopee; hanya dibaca */
class StockResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Stok';

    protected static ?string $modelLabel = 'stok';

    protected static ?string $pluralModelLabel = 'stok';

    protected static ?string $slug = 'stok';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product', 'aroma', 'size']))
            ->defaultSort('sku')
            ->description('Tampilan hanya baca. Sinkron stok dengan Shopee sedang dikembangkan.')
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->description(fn (ProductVariant $record) => $record->aroma?->name)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('size.label')
                    ->label('Ukuran')
                    ->placeholder('-'),
                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Stok')
                    ->sortable(),
                TextColumn::make('shopee_variation_id')
                    ->label('Kode variasi Shopee')
                    ->placeholder('-')
                    ->searchable()
                    ->fontFamily('mono'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStock::route('/'),
        ];
    }
}
