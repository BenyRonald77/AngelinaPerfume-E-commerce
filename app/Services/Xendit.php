<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Xendit Invoice API: halaman bayar (VA, QRIS, e-wallet) dari Xendit; toko tidak menyimpan data kartu */
class Xendit
{
    public static function enabled(): bool
    {
        return filled(config('services.xendit.secret_key'));
    }

    /**
     * @return array{id: string, invoice_url: string}
     */
    public function createInvoice(Order $order): array
    {
        $order->loadMissing(['items', 'user']);
        $seconds = max(300, (int) now()->diffInSeconds($order->payment_due_at, false));

        $response = Http::withBasicAuth((string) config('services.xendit.secret_key'), '')
            ->acceptJson()
            ->timeout(20)
            ->post(rtrim(config('services.xendit.base_url'), '/').'/v2/invoices', [
                'external_id' => $order->code,
                'amount' => $order->total,
                'currency' => 'IDR',
                'description' => "Pesanan {$order->code} di Angelia Perfume",
                'invoice_duration' => $seconds,
                'payer_email' => $order->user->email,
                'customer' => ['given_names' => $order->recipient_name, 'mobile_number' => $order->phone],
                'success_redirect_url' => route('pesanan.bayar', $order),
                'failure_redirect_url' => route('pesanan.bayar', $order),
                'items' => $order->items->where('is_gift', false)->map(fn (OrderItem $i) => [
                    'name' => mb_strimwidth("{$i->product_name} {$i->aroma_name} {$i->size_label}", 0, 250),
                    'quantity' => $i->quantity,
                    'price' => $i->price,
                ])->values()->all(),
                'fees' => array_values(array_filter([
                    $order->shipping_cost ? ['type' => 'Ongkir', 'value' => $order->shipping_cost] : null,
                    $order->packaging_fee ? ['type' => 'Extra dus/bubble wrap', 'value' => $order->packaging_fee] : null,
                ])),
            ]);

        if (! $response->successful() || ! $response->json('invoice_url')) {
            throw new RuntimeException('Xendit menolak pembuatan invoice: '.$response->status().' '.$response->body());
        }

        return ['id' => (string) $response->json('id'), 'invoice_url' => (string) $response->json('invoice_url')];
    }

    /** Webhook dianggap sah bila header x-callback-token sama dengan token di dashboard Xendit */
    public static function validCallbackToken(?string $token): bool
    {
        $expected = (string) config('services.xendit.callback_token');

        return $expected !== '' && $token !== null && hash_equals($expected, $token);
    }
}
