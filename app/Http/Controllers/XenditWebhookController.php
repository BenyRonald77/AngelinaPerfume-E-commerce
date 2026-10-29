<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderWorkflow;
use App\Services\Xendit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi invoice dari Xendit. Status lunas tercatat otomatis tanpa konfirmasi manual (F6).
 * Aman dipanggil berulang: pesanan yang sudah lunas atau batal tidak diubah lagi.
 */
class XenditWebhookController extends Controller
{
    public function __invoke(Request $request, OrderWorkflow $workflow): JsonResponse
    {
        if (! Xendit::validCallbackToken($request->header('x-callback-token'))) {
            return response()->json(['message' => 'Token tidak sah'], 401);
        }

        $order = Order::where('code', (string) $request->input('external_id'))->first();
        if (! $order) {
            return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
        }

        $status = strtoupper((string) $request->input('status'));

        if (in_array($status, ['PAID', 'SETTLED'], true) && $order->isAwaitingPayment()) {
            $paid = (int) $request->input('paid_amount', $request->input('amount'));
            if ($paid < $order->total) {
                Log::warning("Xendit: pembayaran {$order->code} kurang ({$paid} < {$order->total})", $request->all());

                return response()->json(['message' => 'Jumlah tidak sesuai'], 422);
            }

            $workflow->transition($order, OrderStatus::Lunas, data: [
                'method' => trim($request->input('payment_method').' '.$request->input('payment_channel')),
            ]);
            $order->payment?->update(['xendit_invoice_id' => $request->input('id') ?? $order->payment->xendit_invoice_id]);
        } elseif (in_array($status, ['PAID', 'SETTLED'], true) && $order->status->releasesStock() && ! str_contains((string) $order->admin_note, 'Dibayar setelah batal')) {
            // Pembeli membayar tepat saat pesanan kedaluwarsa: stok sudah kembali, admin perlu menindaklanjuti
            Log::warning("Xendit: {$order->code} dibayar setelah {$order->status->label()}", $request->all());
            $order->update(['admin_note' => trim($order->admin_note."\nDibayar setelah batal (Xendit ".now()->format('d/m H.i').'): kembalikan dana atau buat ulang pesanan.')]);
        } elseif ($status === 'EXPIRED' && $order->isAwaitingPayment()) {
            $workflow->transition($order, OrderStatus::Kedaluwarsa);
        }

        return response()->json(['message' => 'OK']);
    }
}
