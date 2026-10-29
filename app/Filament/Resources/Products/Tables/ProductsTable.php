<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['cover', 'brand', 'category'])->withSum('variants as total_stock', 'stock'))
            ->defaultSort('number')
            ->columns([
                ImageColumn::make('cover')
                    ->label('')
                    ->state(fn (Product $record) => $record->cover?->url())
                    ->square()
                    ->imageSize(48),
                TextColumn::make('number')
                    ->label('No.')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('brand.name')
                    ->label('Merek')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('category.name')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('aromas_count')
                    ->label('Aroma')
                    ->counts('aromas')
                    ->sortable(),
                TextColumn::make('total_stock')
                    ->label('Stok')
                    ->numeric(thousandsSeparator: '.')
                    ->sortable()
                    ->color(fn ($state) => (int) $state === 0 ? 'danger' : null),
                TextColumn::make('gender')
                    ->label('Gender')
                    ->placeholder('Belum diisi')
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_visible')
                    ->label('Tampil'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Jenis')
                    ->relationship('category', 'name'),
                SelectFilter::make('brand')
                    ->label('Merek')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_visible')
                    ->label('Tampil di toko'),
                TernaryFilter::make('gender')
                    ->label('Gender')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah diisi')
                    ->falseLabel('Belum diisi')
                    ->nullable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
