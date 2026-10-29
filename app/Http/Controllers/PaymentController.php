<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\Xendit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Halaman status bayar: menunggu pembayaran, lunas, atau kedaluwarsa */
class PaymentController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return view('pesanan.bayar', [
            'order' => $order->load(['items', 'payment', 'shipment']),
            'xenditEnabled' => Xendit::enabled(),
        ]);
    }

    /** Membuka halaman bayar Xendit; invoice dibuat ulang bila sebelumnya gagal */
    public function pay(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        if (! $order->isAwaitingPayment()) {
            return redirect()->route('pesanan.bayar', $order);
        }

        $orders->createInvoice($order);
        $url = $order->payment()->value('invoice_url');

        return $url
            ? redirect()->away($url)
            : redirect()->route('pesanan.bayar', $order)->with('flash_error', 'Halaman pembayaran belum bisa dibuka. Coba lagi beberapa saat lagi.');
    }
}
