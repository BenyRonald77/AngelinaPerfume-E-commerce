<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\StockReason;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Perpindahan status pesanan sesuai alur di PRD. Stok dikembalikan saat pesanan
 * kedaluwarsa atau dibatalkan sebelum dikirim; pembeli mendapat email di setiap tahap.
 */
class OrderWorkflow
{
    /**
     * @param  array{method?: string, note?: string, courier?: string, service?: string, tracking_number?: string, reason?: string}  $data
     */
    public function transition(Order $order, OrderStatus $to, ?User $by = null, array $data = []): Order
    {
        $order = DB::transaction(function () use ($order, $to, $by, $data) {
            /** @var Order $locked */
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canMoveTo($to)) {
                throw new InvalidArgumentException("Pesanan {$locked->code} tidak bisa diubah dari {$locked->status->label()} ke {$to->label()}.");
            }

            $now = now();
            $locked->status = $to;

            match ($to) {
                OrderStatus::Lunas => $this->markPaid($locked, $data, $now),
                OrderStatus::Diproses => $locked->processed_at = $now,
                OrderStatus::Dikirim => $this->markShipped($locked, $data, $now),
                OrderStatus::Selesai => $locked->completed_at = $now,
                OrderStatus::Kedaluwarsa, OrderStatus::Dibatalkan => $this->cancel($locked, $to, $data, $by, $now),
                OrderStatus::Retur => $locked->admin_note = trim(($locked->admin_note ? $locked->admin_note."\n" : '').'Retur: '.($data['note'] ?? '-')),
                default => null,
            };

            $locked->save();

            return $locked;
        });

        // Email dikirim setelah respons; SMTP bermasalah tidak boleh membatalkan perubahan status
        defer(function () use ($order) {
            try {
                $order->user->notify(new OrderStatusChanged($order));
            } catch (Throwable $e) {
                report($e);
            }
        });

        return $order;
    }

    private function markPaid(Order $order, array $data, $now): void
    {
        $order->paid_at = $now;
        $payment = $order->payment ?? $order->payment()->make(['amount' => $order->total]);
        $payment->fill([
            'status' => Payment::PAID,
            'paid_at' => $now,
            'method' => $data['method'] ?? $payment->method,
            'note' => $data['note'] ?? $payment->note,
        ])->save();
    }

    private function markShipped(Order $order, array $data, $now): void
    {
        if (blank($data['tracking_number'] ?? null)) {
            throw new InvalidArgumentException('Nomor resi wajib diisi.');
        }

        $order->shipped_at = $now;
        $shipment = $order->shipment ?? $order->shipment()->make(['cost' => $order->shipping_cost, 'courier' => $data['courier'] ?? '-']);
        $shipment->fill([
            'courier' => $data['courier'] ?? $shipment->courier,
            'service' => $data['service'] ?? $shipment->service,
            'tracking_number' => $data['tracking_number'],
            'shipped_at' => $now,
        ])->save();
    }

    private function cancel(Order $order, OrderStatus $to, array $data, ?User $by, $now): void
    {
        $order->cancelled_at = $now;
        $order->cancel_reason = $data['reason'] ?? ($to === OrderStatus::Kedaluwarsa ? 'Tidak dibayar sampai batas waktu' : null);

        // Stok kembali (aturan bisnis 8), termasuk hadiah
        foreach ($order->items()->with('variant')->get() as $item) {
            $item->variant?->adjustStock($item->quantity, StockReason::Batal, $by, "Pesanan {$order->code} {$to->label()}", $order->id);
        }

        $order->payment?->update(['status' => Payment::EXPIRED]);
    }
}
