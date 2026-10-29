<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderWorkflow;
use Illuminate\Console\Command;
use Throwable;

/** F7: pesanan yang tidak dibayar sampai batas waktu dibatalkan dan stoknya dikembalikan */
class ExpireOrders extends Command
{
    protected $signature = 'toko:batalkan-kedaluwarsa';

    protected $description = 'Batalkan pesanan yang melewati batas bayar dan kembalikan stoknya';

    public function handle(OrderWorkflow $workflow): int
    {
        $orders = Order::where('status', OrderStatus::MenungguPembayaran)
            ->where('payment_due_at', '<', now())
            ->get();

        foreach ($orders as $order) {
            try {
                $workflow->transition($order, OrderStatus::Kedaluwarsa);
                $this->line("{$order->code} kedaluwarsa, stok dikembalikan.");
            } catch (Throwable $e) {
                report($e);
                $this->error("{$order->code}: {$e->getMessage()}");
            }
        }

        $this->info($orders->count().' pesanan diproses.');

        return self::SUCCESS;
    }
}
