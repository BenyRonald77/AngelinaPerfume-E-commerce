<?php

namespace App\Filament\Resources\GiftRules;

use App\Filament\Resources\GiftRules\Pages\ManageGiftRules;
use App\Models\Category;
use App\Models\GiftRule;
use App\Models\ProductVariant;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class GiftRuleResource extends Resource
{
    protected static ?string $model = GiftRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Promo';

    protected static ?string $navigationLabel = 'Hadiah otomatis';

    protected static ?string $modelLabel = 'aturan hadiah';

    protected static ?string $pluralModelLabel = 'aturan hadiah';

    protected static ?string $slug = 'hadiah';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nama aturan')
                    ->required()
                    ->maxLength(255),
                TextInput::make('min_subtotal')
                    ->label('Minimum belanja')
                    ->prefix('Rp')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required(),
                Select::make('gift_variant_id')
                    ->label('Hadiah (SKU)')
                    ->options(fn () => ProductVariant::query()
                        ->whereHas('product.category', fn ($q) => $q->where('slug', Category::HADIAH))
                        ->with('product')
                        ->get()
                        ->mapWithKeys(fn (ProductVariant $v) => [$v->id => "{$v->product->name} (stok {$v->stock})"]))
                    ->helperText('Hadiah diambil dari produk berjenis Hadiah supaya stoknya ikut berkurang.')
                    ->required(),
                Select::make('products')
                    ->label('Berlaku untuk produk')
                    ->relationship('products', 'name')
                    ->multiple()
                    ->searchable()
                    ->placeholder('Semua produk')
                    ->helperText('Kosongkan bila berlaku untuk semua produk.'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Aktifkan setelah pemilik toko memastikan hadiah dan produk yang ikut promo.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('min_subtotal')
            ->columns([
                TextColumn::make('name')
                    ->label('Aturan'),
                TextColumn::make('min_subtotal')
                    ->label('Minimum belanja')
                    ->money('IDR', locale: 'id'),
                TextColumn::make('giftVariant.product.name')
                    ->label('Hadiah')
                    ->placeholder('Belum dipilih'),
                TextColumn::make('products_count')
                    ->label('Produk')
                    ->counts('products')
                    ->formatStateUsing(fn (int $state) => $state === 0 ? 'Semua' : (string) $state),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageGiftRules::route('/'),
        ];
    }
}
