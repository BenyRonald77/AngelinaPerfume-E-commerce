<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductImage;
use App\Support\Images\WebpConverter;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Foto produk';

    protected static ?string $modelLabel = 'foto';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('upload')
                ->label('Foto')
                ->image()
                ->disk('local')
                ->directory('unggahan-foto')
                ->maxSize(8192)
                ->required()
                ->visibleOn('create')
                ->helperText('Diubah otomatis ke WebP 800 px dan 1600 px.'),
            TextInput::make('alt')
                ->label('Teks alternatif')
                ->helperText('Dibacakan pembaca layar; kosongkan bila sama dengan nama produk.')
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->description('Foto paling atas menjadi sampul.')
            ->columns([
                ImageColumn::make('path')
                    ->label('')
                    ->state(fn (ProductImage $record) => $record->url())
                    ->imageSize(72),
                TextColumn::make('sort_order')
                    ->label('Urutan'),
                TextColumn::make('alt')
                    ->label('Teks alternatif')
                    ->placeholder('-'),
                TextColumn::make('width')
                    ->label('Ukuran')
                    ->state(fn (ProductImage $record) => $record->width ? "{$record->width} × {$record->height}" : null),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah foto')
                    ->using(function (array $data): Model {
                        $product = $this->getOwnerRecord();
                        $local = Storage::disk('local');
                        $dest = sprintf('produk/%04d/admin-%s', $product->number, Str::lower(Str::random(10)));

                        $dims = app(WebpConverter::class)->convert($local->path($data['upload']), Storage::disk('public')->path($dest));
                        $local->delete($data['upload']);

                        return $product->images()->create([
                            'path' => $dest,
                            'width' => $dims['width'],
                            'height' => $dims['height'],
                            'alt' => $data['alt'] ?? null,
                            'sort_order' => (int) $product->images()->max('sort_order') + 1,
                        ]);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->after(fn (ProductImage $record) => Storage::disk('public')->delete(array_map(
                        fn (int $w) => "{$record->path}-{$w}.webp",
                        WebpConverter::WIDTHS,
                    ))),
            ]);
    }
}
