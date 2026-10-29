<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Actions\OrderActions;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Rupiah;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Pesanan';

    protected static ?string $modelLabel = 'pesanan';

    protected static ?string $pluralModelLabel = 'pesanan';

    protected static ?string $slug = 'pesanan';

    protected static ?string $recordTitleAttribute = 'code';

    public static function canCreate(): bool
    {
        return false;
    }

    /** Jumlah pesanan lunas yang belum dikemas: pekerjaan admin berikutnya */
    public static function getNavigationBadge(): ?string
    {
        $count = Order::where('status', OrderStatus::Lunas)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Lunas, belum diproses';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'shipment']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),
                TextColumn::make('created_at')
                    ->label('Dipesan')
                    ->dateTime('d M Y H.i')
                    ->sortable(),
                TextColumn::make('recipient_name')
                    ->label('Penerima')
                    ->description(fn (Order $record) => "{$record->city}, {$record->province}")
                    ->searchable(['recipient_name', 'phone'])
                    ->wrap(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('shipment.tracking_number')
                    ->label('Resi')
                    ->placeholder('-')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(OrderStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make(OrderActions::all()),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Pesanan')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('code')->label('Kode')->copyable()->weight('bold'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('created_at')->label('Dipesan')->dateTime('d M Y H.i'),
                        TextEntry::make('payment_due_at')->label('Batas bayar')->dateTime('d M Y H.i')
                            ->visible(fn (Order $record) => $record->isAwaitingPayment()),
                        TextEntry::make('paid_at')->label('Dibayar')->dateTime('d M Y H.i')->placeholder('-'),
                        TextEntry::make('payment.method')->label('Cara bayar')->placeholder('-'),
                        TextEntry::make('payment.note')->label('Catatan pembayaran')->placeholder('-'),
                        TextEntry::make('customer_note')->label('Catatan pembeli')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('cancel_reason')->label('Alasan batal')->visible(fn (Order $record) => filled($record->cancel_reason)),
                        TextEntry::make('admin_note')->label('Catatan admin')->visible(fn (Order $record) => filled($record->admin_note)),
                    ]),
                Section::make('Kirim ke')
                    ->schema([
                        TextEntry::make('recipient_name')->label('Penerima')->weight('bold'),
                        TextEntry::make('phone')->label('HP')->copyable(),
                        TextEntry::make('address')->label('Alamat')
                            ->state(fn (Order $record) => $record->addressOneLine()),
                        TextEntry::make('shipment.courier')->label('Kurir')
                            ->formatStateUsing(fn (Order $record) => trim($record->shipment?->courier.' '.($record->shipment?->service ? "({$record->shipment->service})" : ''))),
                        TextEntry::make('shipment.tracking_number')->label('Resi')->placeholder('Belum ada')->copyable(),
                        TextEntry::make('total_weight_grams')->label('Berat')->suffix(' gram'),
                    ]),
                Section::make('Barang')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('product_name')->label('Produk')
                                    ->formatStateUsing(fn (OrderItem $record) => "{$record->product_name} · {$record->aroma_name}".($record->size_label ? " · {$record->size_label}" : ''))
                                    ->columnSpan(2),
                                TextEntry::make('sku')->label('SKU')->fontFamily('mono')
                                    ->badge(fn (OrderItem $record) => $record->is_gift)
                                    ->formatStateUsing(fn (OrderItem $record) => $record->is_gift ? "{$record->sku} (hadiah)" : $record->sku),
                                TextEntry::make('quantity')->label('Jumlah × harga')
                                    ->formatStateUsing(fn (OrderItem $record) => "{$record->quantity} × ".Rupiah::format($record->price)),
                            ]),
                        TextEntry::make('totals')->label('Rincian')
                            ->state(fn (Order $record) => collect([
                                'Subtotal '.Rupiah::format($record->subtotal),
                                $record->packaging_fee ? 'Extra dus '.Rupiah::format($record->packaging_fee) : null,
                                'Ongkir '.Rupiah::format($record->shipping_cost),
                                'Total '.Rupiah::format($record->total),
                            ])->filter()->implode(' · ')),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
