<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Rupiah;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email otomatis F9: pesanan dibuat, lunas, dikirim (dengan resi), selesai, dan batal */
class OrderStatusChanged extends Notification
{
    private OrderStatus $status;

    public function __construct(private Order $order)
    {
        $this->status = $order->status;
    }

    public function via(object $notifiable): array
    {
        // "Diproses" dan "Retur" tidak dikirimi email; pembeli melihatnya di Pesanan saya
        return in_array($this->status, [OrderStatus::Diproses, OrderStatus::Retur], true) ? [] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing(['items', 'shipment']);
        $code = $order->code;
        $total = Rupiah::format($order->total);
        $mail = (new MailMessage)->greeting("Halo {$order->recipient_name},");

        return match ($this->status) {
            OrderStatus::MenungguPembayaran => $mail
                ->subject("Pesanan {$code} menunggu pembayaran")
                ->line("Terima kasih, pesanan {$code} sudah kami terima dengan total {$total}.")
                ->line('Selesaikan pembayaran sebelum '.$order->payment_due_at->translatedFormat('j F Y, H.i').' WIB. Setelah itu pesanan batal otomatis dan stok dikembalikan.')
                ->action('Bayar sekarang', route('pesanan.bayar', $order)),
            OrderStatus::Lunas => $mail
                ->subject("Pembayaran pesanan {$code} diterima")
                ->line("Pembayaran {$total} untuk pesanan {$code} sudah kami terima.")
                ->line('Pesananmu segera kami kemas. Nomor resi dikirim lewat email begitu paket diserahkan ke kurir.')
                ->action('Lihat pesanan', route('akun.pesanan.show', $order)),
            OrderStatus::Dikirim => $mail
                ->subject("Pesanan {$code} sudah dikirim")
                ->line("Pesanan {$code} sudah diserahkan ke kurir {$order->shipment?->courier}.")
                ->line('Nomor resi: '.$order->shipment?->tracking_number)
                ->action('Lacak paket', $order->shipment?->trackingUrl() ?? route('akun.pesanan.show', $order))
                ->line('Saat paket tiba, rekam video unboxing dari awal sampai akhir. Komplain diterima maksimal 1×24 jam dengan video tersebut.'),
            OrderStatus::Selesai => $mail
                ->subject("Pesanan {$code} selesai")
                ->line("Pesanan {$code} sudah selesai. Terima kasih sudah berbelanja di Angelia Perfume.")
                ->action('Belanja lagi', route('produk.index')),
            OrderStatus::Kedaluwarsa, OrderStatus::Dibatalkan => $mail
                ->subject("Pesanan {$code} dibatalkan")
                ->line("Pesanan {$code} dibatalkan".($order->cancel_reason ? ": {$order->cancel_reason}." : '.'))
                ->line('Bila kamu sudah membayar, hubungi kami lewat WhatsApp untuk pengembalian dana.')
                ->action('Lihat pesanan', route('akun.pesanan.show', $order)),
            default => $mail->subject("Pesanan {$code}: {$this->status->label()}")->action('Lihat pesanan', route('akun.pesanan.show', $order)),
        };
    }
}
