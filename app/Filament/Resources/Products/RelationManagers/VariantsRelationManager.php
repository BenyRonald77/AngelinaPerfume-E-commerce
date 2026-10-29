<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\StockReason;
use App\Filament\Actions\AdjustStockAction;
use App\Models\Product;
use App\Models\ProductAroma;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Size;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'SKU (aroma × ukuran)';

    protected static ?string $modelLabel = 'SKU';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('product_aroma_id')
                    ->label('Aroma')
                    ->options(fn () => $this->getOwnerRecord()->aromas()->pluck('name', 'id'))
                    ->required()
                    ->disabledOn('edit'),
                Select::make('size_id')
                    ->label('Ukuran')
                    ->options(Size::orderBy('sort_order')->pluck('label', 'id'))
                    ->placeholder('Tanpa ukuran (aksesori)')
                    ->disabledOn('edit'),
                TextInput::make('price')
                    ->label('Harga')
                    ->prefix('Rp')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required(),
                TextInput::make('weight_grams')
                    ->label('Berat paket')
                    ->suffix('gram')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required(),
                TextInput::make('stock')
                    ->label('Stok awal')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->visibleOn('create')
                    ->helperText('Setelah dibuat, stok diubah lewat tombol "Ubah stok" supaya tercatat.'),
                TextInput::make('max_per_order')
                    ->label('Batas beli per pesanan')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->placeholder('Tanpa batas'),
                TextInput::make('note')
                    ->label('Keterangan')
                    ->placeholder('mis. Kemasan lama')
                    ->maxLength(60),
                Toggle::make('is_active')
                    ->label('Dijual')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        $lowStock = Setting::int('low_stock_threshold');

        return $table
            ->recordTitleAttribute('sku')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['aroma', 'size']))
            ->defaultSort('sku')
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
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
                TextColumn::make('max_per_order')
                    ->label('Batas beli')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('weight_grams')
                    ->label('Berat (g)')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('note')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Dijual')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('size_id')
                    ->label('Ukuran')
                    ->options(Size::orderBy('sort_order')->pluck('label', 'id')),
                SelectFilter::make('product_aroma_id')
                    ->label('Aroma')
                    ->options(fn () => $this->getOwnerRecord()->aromas()->pluck('name', 'id')),
                Filter::make('habis')
                    ->label('Stok habis')
                    ->query(fn (Builder $query) => $query->where('stock', 0)),
                Filter::make('menipis')
                    ->label("Stok menipis (≤ {$lowStock})")
                    ->query(fn (Builder $query) => $query->whereBetween('stock', [1, $lowStock])),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data): Model {
                        /** @var Product $product */
                        $product = $this->getOwnerRecord();
                        $aroma = ProductAroma::findOrFail($data['product_aroma_id']);
                        $size = isset($data['size_id']) ? Size::find($data['size_id']) : null;
                        $stock = (int) ($data['stock'] ?? 0);
                        unset($data['stock']);

                        $sku = ProductVariant::generateSku($product, $aroma, $size);
                        $variant = $product->variants()->create([...$data, 'sku' => $sku, 'stock' => 0]);
                        if ($stock > 0) {
                            $variant->adjustStock($stock, StockReason::Penyesuaian, auth()->user(), 'Stok awal SKU baru');
                        }

                        return $variant;
                    }),
            ])
            ->recordActions([
                AdjustStockAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // "Bisa diisi sekaligus per ukuran": saring ukuran, pilih semua, lalu ubah harga
                    BulkAction::make('setPrice')
                        ->label('Ubah harga terpilih')
                        ->schema([
                            TextInput::make('price')->label('Harga baru')->prefix('Rp')->numeric()->integer()->minValue(0)->required(),
                        ])
                        ->action(fn (Collection $records, array $data) => $records->each->update(['price' => (int) $data['price']]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Berhenti jual')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('activate')
                        ->label('Jual lagi')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
