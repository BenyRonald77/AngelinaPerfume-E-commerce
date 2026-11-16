<?php

namespace App\Filament\Resources\FlashSales;

use App\Filament\Resources\FlashSales\Pages\CreateFlashSale;
use App\Filament\Resources\FlashSales\Pages\EditFlashSale;
use App\Filament\Resources\FlashSales\Pages\ListFlashSales;
use App\Models\FlashSale;
use App\Models\ProductVariant;
use App\Support\Rupiah;
use BackedEnum;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Flash sale tanggal kembar (permintaan klien): jadwal + daftar SKU dengan harga khusus */
class FlashSaleResource extends Resource
{
    protected static ?string $model = FlashSale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'Promo';

    protected static ?string $navigationLabel = 'Flash sale';

    protected static ?string $modelLabel = 'flash sale';

    protected static ?string $pluralModelLabel = 'flash sale';

    protected static ?string $slug = 'flash-sale';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Jadwal')
                    ->columns(2)
                    ->schema([
                        Select::make('tanggal_kembar')
                            ->label('Isi cepat: tanggal kembar')
                            ->options(fn () => FlashSale::twinDates())
                            ->placeholder('Pilih tanggal kembar berikutnya')
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set) {
                                if (! $state) {
                                    return;
                                }
                                $date = CarbonImmutable::parse($state);
                                $set('name', "Flash Sale {$date->month}.{$date->day}");
                                $set('starts_at', $date->startOfDay()->toDateTimeString());
                                $set('ends_at', $date->setTime(23, 59)->toDateTimeString());
                            })
                            ->helperText('Nama dan jam terisi otomatis (00.00–23.59 WIB); masih bisa diubah.')
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Nama')
                            ->placeholder('Flash Sale 12.12')
                            ->required()
                            ->maxLength(60),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Matikan untuk menyembunyikan dari toko tanpa menghapus.'),
                        DateTimePicker::make('starts_at')
                            ->label('Mulai')
                            ->seconds(false)
                            ->required(),
                        DateTimePicker::make('ends_at')
                            ->label('Berakhir')
                            ->seconds(false)
                            ->after('starts_at')
                            ->required(),
                        Toggle::make('show_teaser')
                            ->label('Tampilkan di Beranda sebelum mulai (hitung mundur)')
                            ->default(true)
                            ->helperText('Pembeli melihat produk dan harga flash lebih dulu; harga baru berlaku saat mulai.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Produk flash sale')
                    ->description('Harga flash berlaku di keranjang dan checkout hanya selama jadwal di atas. Stok mengikuti stok SKU.')
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->addActionLabel('Tambah produk')
                            ->defaultItems(0)
                            ->minItems(1)
                            ->columns(3)
                            ->itemLabel(fn (array $state) => ($v = ProductVariant::with(['product', 'aroma'])->find($state['product_variant_id'] ?? null))
                                ? "{$v->product->name} · {$v->aroma->name}" : null)
                            ->schema([
                                Select::make('product_variant_id')
                                    ->label('Produk (SKU)')
                                    ->searchable()
                                    ->getSearchResultsUsing(fn (string $search) => self::searchVariants($search))
                                    ->getOptionLabelUsing(fn ($value) => ($v = ProductVariant::with(['product', 'aroma', 'size'])->find($value)) ? self::variantLabel($v) : null)
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->live()
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('sale_price')
                                    ->label('Harga flash sale')
                                    ->prefix('Rp')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->live(onBlur: true)
                                    ->required()
                                    ->rules([
                                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                            $variant = ProductVariant::find($get('product_variant_id'));
                                            if ($variant && (int) $value >= $variant->price) {
                                                $fail('Harus lebih murah dari harga normal '.Rupiah::format($variant->price).'.');
                                            }
                                        },
                                    ])
                                    ->helperText(function (Get $get) {
                                        $variant = ProductVariant::find($get('product_variant_id'));
                                        if (! $variant) {
                                            return null;
                                        }
                                        $sale = (int) $get('sale_price');

                                        return 'Normal '.Rupiah::format($variant->price)
                                            .($sale > 0 && $sale < $variant->price ? ' · potongan '.intdiv(($variant->price - $sale) * 100, $variant->price).'%' : '');
                                    }),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->dateTime('j M Y, H.i')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Berakhir')
                    ->dateTime('j M Y, H.i'),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (FlashSale $record) => $record->status())
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Berlangsung' => 'success',
                        'Akan datang' => 'info',
                        'Nonaktif' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('items_count')
                    ->label('Produk')
                    ->counts('items'),
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
            'index' => ListFlashSales::route('/'),
            'create' => CreateFlashSale::route('/create'),
            'edit' => EditFlashSale::route('/{record}/edit'),
        ];
    }

    public static function variantLabel(ProductVariant $variant): string
    {
        return $variant->product->name.' · '.$variant->aroma->name
            .($variant->size ? ' · '.$variant->size->label : '')
            .' · '.Rupiah::format($variant->price)." (stok {$variant->stock})";
    }

    /** @return array<int, string> SKU yang dijual, dicari dari nama produk, aroma, atau SKU */
    private static function searchVariants(string $search): array
    {
        $like = '%'.trim($search).'%';

        return ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->visible())
            ->where(fn ($q) => $q->where('sku', 'like', $like)
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $like))
                ->orWhereHas('aroma', fn ($a) => $a->where('name', 'like', $like)))
            ->with(['product', 'aroma', 'size'])
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (ProductVariant $v) => [$v->id => self::variantLabel($v)])
            ->all();
    }
}
