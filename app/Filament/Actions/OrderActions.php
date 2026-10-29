<?php

namespace App\Filament\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderWorkflow;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

/**
 * Aksi status pesanan untuk tabel dan halaman detail. Semua perubahan lewat
 * OrderWorkflow supaya alur status, stok, dan email pembeli tetap konsisten.
 */
class OrderActions
{
    /** @return list<Action> */
    public static function all(): array
    {
        return [
            self::markPaid(),
            self::process(),
            self::ship(),
            self::complete(),
            self::cancel(),
            self::markReturned(),
            self::print(),
        ];
    }

    public static function markPaid(): Action
    {
        return Action::make('markPaid')
            ->label('Tandai lunas')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->visible(fn (Order $record) => $record->status === OrderStatus::MenungguPembayaran)
            ->modalDescription(fn (Order $record) => "Pastikan dana {$record->code} sudah masuk. Pembeli mendapat email konfirmasi.")
            ->schema([
                Select::make('method')
                    ->label('Cara bayar')
                    ->options(['Transfer bank' => 'Transfer bank', 'QRIS' => 'QRIS', 'E-wallet' => 'E-wallet', 'Tunai' => 'Tunai', 'Lainnya' => 'Lainnya'])
                    ->required(),
                TextInput::make('note')
                    ->label('Catatan')
                    ->placeholder('mis. transfer BCA a.n. pembeli, jam 14.10')
                    ->maxLength(255),
            ])
            ->action(fn (Order $record, array $data) => self::run($record, OrderStatus::Lunas, $data, 'ditandai lunas'));
    }

    public static function process(): Action
    {
        return Action::make('process')
            ->label('Proses')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('info')
            ->visible(fn (Order $record) => $record->status === OrderStatus::Lunas)
            ->requiresConfirmation()
            ->modalDescription('Pesanan mulai dikemas.')
            ->action(fn (Order $record) => self::run($record, OrderStatus::Diproses, [], 'diproses'));
    }

    public static function ship(): Action
    {
        return Action::make('ship')
            ->label('Kirim')
            ->icon(Heroicon::OutlinedTruck)
            ->color('primary')
            ->visible(fn (Order $record) => $record->status === OrderStatus::Diproses)
            ->fillForm(fn (Order $record) => [
                'courier' => $record->shipment?->courier,
                'service' => $record->shipment?->service,
            ])
            ->schema([
                TextInput::make('courier')->label('Kurir')->required()->maxLength(40),
                TextInput::make('service')->label('Layanan')->maxLength(60),
                TextInput::make('tracking_number')->label('Nomor resi')->required()->maxLength(60),
            ])
            ->modalDescription('Pembeli mendapat email berisi nomor resi dan tautan lacak.')
            ->action(fn (Order $record, array $data) => self::run($record, OrderStatus::Dikirim, $data, 'dikirim'));
    }

    public static function complete(): Action
    {
        return Action::make('complete')
            ->label('Selesai')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Order $record) => $record->status === OrderStatus::Dikirim)
            ->requiresConfirmation()
            ->modalDescription('Tandai selesai bila paket sudah sampai. Pembeli juga bisa menekan "Pesanan diterima" sendiri.')
            ->action(fn (Order $record) => self::run($record, OrderStatus::Selesai, [], 'selesai'));
    }

    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label('Batalkan')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Order $record) => $record->status->canMoveTo(OrderStatus::Dibatalkan))
            ->modalDescription('Stok semua barang dikembalikan. Bila pembeli sudah membayar, kembalikan dananya di luar sistem.')
            ->schema([
                TextInput::make('reason')
                    ->label('Alasan (dikirim ke pembeli)')
                    ->placeholder('mis. stok aroma ini ternyata habis')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(fn (Order $record, array $data) => self::run($record, OrderStatus::Dibatalkan, $data, 'dibatalkan'));
    }

    public static function markReturned(): Action
    {
        return Action::make('markReturned')
            ->label('Retur')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('warning')
            ->visible(fn (Order $record) => $record->status === OrderStatus::Dikirim)
            ->modalDescription('Stok tidak otomatis kembali; bila barang retur layak jual, tambahkan lewat menu Stok.')
            ->schema([
                TextInput::make('note')->label('Catatan retur')->required()->maxLength(255),
            ])
            ->action(fn (Order $record, array $data) => self::run($record, OrderStatus::Retur, $data, 'dicatat retur'));
    }

    public static function print(): Action
    {
        return Action::make('print')
            ->label('Cetak label')
            ->icon(Heroicon::OutlinedPrinter)
            ->color('gray')
            ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::Lunas, OrderStatus::Diproses, OrderStatus::Dikirim], true))
            ->url(fn (Order $record) => route('admin.pesanan.cetak', $record))
            ->openUrlInNewTab();
    }

    private static function run(Order $order, OrderStatus $to, array $data, string $done): void
    {
        try {
            app(OrderWorkflow::class)->transition($order, $to, auth()->user(), $data);
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return;
        }

        $order->refresh();
        Notification::make()->title("Pesanan {$order->code} {$done}.")->success()->send();
    }
}
