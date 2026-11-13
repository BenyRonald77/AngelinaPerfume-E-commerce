<?php

namespace App\Filament\Resources\Brands;

use App\Enums\BrandOrigin;
use App\Filament\Resources\Brands\Pages\ManageBrands;
use App\Models\Brand;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmark;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Merek';

    protected static ?string $modelLabel = 'merek';

    protected static ?string $pluralModelLabel = 'merek';

    protected static ?string $slug = 'merek';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                Select::make('origin')
                    ->label('Asal merek')
                    ->options(BrandOrigin::class)
                    ->default(BrandOrigin::Lainnya)
                    ->required()
                    ->helperText('Menentukan kelompok Koleksi di Beranda dan filter Asal di katalog.'),
                FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->disk('public')
                    ->directory('merek')
                    ->maxSize(1024)
                    ->helperText('Opsional. Tampil di halaman Merek; tanpa logo dipakai foto produk terlaris merek ini.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('origin')
                    ->label('Asal')
                    ->badge()
                    ->sortable(),
                TextColumn::make('products_count')
                    ->label('Produk')
                    ->counts('products')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('origin')
                    ->label('Asal')
                    ->options(BrandOrigin::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Brand $record) => $record->products()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBrands::route('/'),
        ];
    }
}
