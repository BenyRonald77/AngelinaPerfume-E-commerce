<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductAroma;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AromasRelationManager extends RelationManager
{
    protected static string $relationship = 'aromas';

    protected static ?string $title = 'Aroma';

    protected static ?string $modelLabel = 'aroma';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nama aroma')
                    ->required()
                    ->maxLength(255),
                TextInput::make('note')
                    ->label('Catatan')
                    ->placeholder('mis. Exp 09/26')
                    ->maxLength(60),
                Section::make('Notes')
                    ->description('Kotak notes di halaman produk baru tampil bila salah satu diisi.')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make('notes_top')->label('Top notes'),
                        TextInput::make('notes_middle')->label('Middle notes'),
                        TextInput::make('notes_base')->label('Base notes'),
                        TextInput::make('notes_general')
                            ->label('Notes tanpa lapisan')
                            ->helperText('Isi bila sumbernya hanya menulis "Notes" tanpa top/middle/base.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->state(fn (ProductAroma $record) => $record->imageUrl())
                    ->circular()
                    ->imageSize(40),
                TextColumn::make('number')
                    ->label('No.'),
                TextColumn::make('name')
                    ->label('Aroma')
                    ->searchable(),
                TextColumn::make('note')
                    ->label('Catatan')
                    ->placeholder('-'),
                TextColumn::make('notes_top')
                    ->label('Notes')
                    ->state(fn (ProductAroma $record) => $record->hasNotes()
                        ? collect($record->only(ProductAroma::NOTE_FIELDS))->filter()->implode(' / ')
                        : null)
                    ->placeholder('Belum diisi')
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('variants_sum_stock')
                    ->label('Stok')
                    ->sum('variants', 'stock'),
            ])
            ->filters([
                TernaryFilter::make('has_notes')
                    ->label('Notes')
                    ->trueLabel('Sudah diisi')
                    ->falseLabel('Belum diisi')
                    ->queries(
                        true: fn (Builder $query) => $query->where(fn ($w) => $w->whereNotNull('notes_top')->orWhereNotNull('notes_middle')->orWhereNotNull('notes_base')->orWhereNotNull('notes_general')),
                        false: fn (Builder $query) => $query->whereNull('notes_top')->whereNull('notes_middle')->whereNull('notes_base')->whereNull('notes_general'),
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data) {
                        $product = $this->getOwnerRecord();
                        $data['number'] = (int) $product->aromas()->max('number') + 1;
                        $data['sort_order'] = (int) $product->aromas()->max('sort_order') + 1;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Semua SKU aroma ini ikut terhapus. Untuk sekadar menyembunyikan, nonaktifkan SKU-nya.'),
            ]);
    }
}
