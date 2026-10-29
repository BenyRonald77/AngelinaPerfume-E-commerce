<?php

namespace App\Filament\Resources\Customers;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Daftar pembeli (hanya dibaca): kontak dan riwayat belanja */
class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Pembeli';

    protected static ?string $modelLabel = 'pembeli';

    protected static ?string $pluralModelLabel = 'pembeli';

    protected static ?string $slug = 'pembeli';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        // Belanja dihitung dari pesanan yang sudah dibayar
        $paid = [OrderStatus::Lunas, OrderStatus::Diproses, OrderStatus::Dikirim, OrderStatus::Selesai];

        return parent::getEloquentQuery()
            ->where('role', Role::Pembeli)
            ->withCount(['orders as paid_orders_count' => fn (Builder $query) => $query->whereIn('status', $paid)])
            ->withSum(['orders as paid_total' => fn (Builder $query) => $query->whereIn('status', $paid)], 'total');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->description(fn (User $record) => $record->email),
                TextColumn::make('phone')
                    ->label('HP')
                    ->placeholder('-')
                    ->searchable()
                    ->copyable(),
                IconColumn::make('email_verified_at')
                    ->label('Email terverifikasi')
                    ->boolean()
                    ->state(fn (User $record) => $record->hasVerifiedEmail()),
                TextColumn::make('paid_orders_count')
                    ->label('Pesanan dibayar')
                    ->sortable(),
                TextColumn::make('paid_total')
                    ->label('Total belanja')
                    ->money('IDR', locale: 'id')
                    ->placeholder('Rp0')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Daftar')
                    ->dateTime('d M Y')
                    ->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCustomers::route('/'),
        ];
    }
}
