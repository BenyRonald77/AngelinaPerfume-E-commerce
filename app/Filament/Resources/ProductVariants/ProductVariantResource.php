<?php

namespace App\Filament\Resources\ProductVariants;

use App\Filament\Actions\AdjustStockAction;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\ProductVariants\Pages\ManageProductVariants;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Size;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Daftar stok semua SKU; perubahan stok hanya lewat aksi "Ubah stok" */
class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Stok';

    protected static ?string $modelLabel = 'SKU';

    protected static ?string $pluralModelLabel = 'stok';

    protected static ?string $slug = 'stok';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ProductVariant::where('is_active', true)
            ->whereBetween('stock', [1, Setting::int('low_stock_threshold')])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'SKU dengan stok menipis';
    }

    public static function table(Table $table): Table
    {
        $lowStock = Setting::int('low_stock_threshold');

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product', 'aroma', 'size'])
                ->whereHas('product.category', fn ($c) => $c->where('slug', '!=', Category::HADIAH)))
            ->defaultSort('stock')
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('aroma.name')
                    ->label('Aroma')
                    ->searchable(),
                TextColumn::make('size.label')
                    ->label('Ukuran')
                    ->placeholder('-'),
                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Stok')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state) => match (true) {
                        $state === 0 => 'danger',
                        $state <= $lowStock => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Filter::make('menipis')
                    ->label("Stok menipis (≤ {$lowStock})")
                    ->query(fn (Builder $query) => $query->whereBetween('stock', [1, $lowStock])),
                Filter::make('habis')
                    ->label('Stok habis')
                    ->query(fn (Builder $query) => $query->where('stock', 0)),
                Filter::make('dijual')
                    ->label('Hanya yang dijual')
                    ->default()
                    ->query(fn (Builder $query) => $query->where('is_active', true)),
                SelectFilter::make('size_id')
                    ->label('Ukuran')
                    ->options(Size::orderBy('sort_order')->pluck('label', 'id')),
                SelectFilter::make('category')
                    ->label('Jenis')
                    ->options(Category::where('slug', '!=', Category::HADIAH)->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->whereHas('product', fn ($p) => $p->where('category_id', $data['value']))
                        : $query),
            ])
            ->recordActions([
                AdjustStockAction::make(),
                Action::make('openProduct')
                    ->label('Produk')
                    ->icon(Heroicon::OutlinedTag)
                    ->url(fn (ProductVariant $record) => ProductResource::getUrl('edit', ['record' => $record->product])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProductVariants::route('/'),
        ];
    }
}
