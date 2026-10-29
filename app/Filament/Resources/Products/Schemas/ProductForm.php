<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\Gender;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Produk')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set, ?Product $record) {
                                if ($record === null) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Alamat halaman (slug)')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->helperText('Dipakai di URL /produk/slug.'),
                        Select::make('brand_id')
                            ->label('Merek')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->label('Nama merek')->required(),
                                TextInput::make('slug')->required()->alphaDash()->unique('brands', 'slug'),
                            ]),
                        Select::make('category_id')
                            ->label('Jenis')
                            ->relationship('category', 'name')
                            ->required(),
                        TextInput::make('concentration')
                            ->label('Konsentrasi')
                            ->placeholder('mis. Extrait de Parfum')
                            ->maxLength(40),
                        Select::make('gender')
                            ->label('Gender')
                            ->options(Gender::class)
                            ->placeholder('Belum diisi')
                            ->helperText('Filter gender di toko baru muncul setelah produk diisi.'),
                    ]),
                Section::make('Status')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_visible')
                            ->label('Tampil di toko')
                            ->default(true),
                        TextInput::make('number')
                            ->label('Nomor produk (untuk kode SKU)')
                            ->numeric()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => (int) Product::max('number') + 1)
                            ->disabledOn('edit')
                            ->dehydrated(fn (string $operation) => $operation === 'create'),
                        TextInput::make('source_folder')
                            ->label('Folder ekspor Shopee')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                    ]),
                Section::make('Deskripsi')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('description')
                            ->label('Deskripsi di halaman produk')
                            ->rows(10)
                            ->helperText('Untuk decant biarkan kosong: aturan toko tampil di halaman Bantuan.'),
                        Textarea::make('shopee_description')
                            ->label('Deskripsi asli Shopee (rujukan)')
                            ->rows(8)
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                    ]),
            ]);
    }
}
