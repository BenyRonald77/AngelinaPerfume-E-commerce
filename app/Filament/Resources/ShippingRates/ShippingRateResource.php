<?php

namespace App\Filament\Resources\ShippingRates;

use App\Filament\Resources\ShippingRates\Pages\ManageShippingRates;
use App\Models\ShippingRate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

/**
 * Tabel ongkir manual (driver ongkir awal). Provinsi tanpa tarif aktif tidak bisa
 * checkout; pembeli diarahkan bertanya lewat WhatsApp.
 */
class ShippingRateResource extends Resource
{
    protected static ?string $model = ShippingRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Tarif ongkir';

    protected static ?string $modelLabel = 'tarif ongkir';

    protected static ?string $pluralModelLabel = 'tarif ongkir';

    protected static ?string $slug = 'ongkir';

    public static function form(Schema $schema): Schema
    {
        $provinces = array_combine(config('provinsi'), config('provinsi'));

        return $schema
            ->columns(2)
            ->components([
                Select::make('province')
                    ->label('Provinsi tujuan')
                    ->options($provinces)
                    ->searchable()
                    ->required(),
                TextInput::make('courier')
                    ->label('Kurir dan layanan')
                    ->placeholder('mis. J&T EZ, JNE REG')
                    ->required()
                    ->maxLength(60)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, callable $get) => $rule->where('province', $get('province')))
                    ->validationMessages(['unique' => 'Kurir ini sudah punya tarif untuk provinsi tersebut.']),
                TextInput::make('first_kg_price')
                    ->label('Tarif kg pertama')
                    ->prefix('Rp')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required(),
                TextInput::make('next_kg_price')
                    ->label('Tarif per kg berikutnya')
                    ->prefix('Rp')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required()
                    ->helperText('Berat paket dibulatkan ke atas per kg.'),
                TextInput::make('estimate')
                    ->label('Estimasi sampai')
                    ->placeholder('mis. 2-3 hari')
                    ->maxLength(40),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('province')
            ->columns([
                TextColumn::make('province')
                    ->label('Provinsi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('courier')
                    ->label('Kurir')
                    ->searchable(),
                TextColumn::make('first_kg_price')
                    ->label('Kg pertama')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('next_kg_price')
                    ->label('Kg berikutnya')
                    ->money('IDR', locale: 'id'),
                TextColumn::make('estimate')
                    ->label('Estimasi')
                    ->placeholder('-'),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                SelectFilter::make('province')
                    ->label('Provinsi')
                    ->options(array_combine(config('provinsi'), config('provinsi'))),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Salin')
                    ->modalHeading('Salin tarif ke provinsi lain')
                    ->schema(fn (Schema $schema) => static::form($schema))
                    ->successNotificationTitle('Tarif disalin.'),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Belum ada tarif ongkir')
            ->emptyStateDescription('Tambahkan tarif per provinsi dan kurir. Selama kosong, pembeli tidak bisa checkout dan diarahkan bertanya lewat WhatsApp.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageShippingRates::route('/'),
        ];
    }
}
