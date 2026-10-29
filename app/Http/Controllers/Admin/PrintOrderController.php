<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Label pengiriman dan daftar isi paket untuk dicetak (A6 atau kertas biasa) */
class PrintOrderController extends Controller
{
    public function __invoke(Request $request, Order $order): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.cetak-pesanan', ['order' => $order->load(['items', 'shipment'])]);
    }
}
