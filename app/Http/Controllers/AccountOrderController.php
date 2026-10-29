<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartException;
use App\Services\CartService;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pesanan saya (F8): riwayat, detail, lacak resi, konfirmasi diterima, beli lagi */
class AccountOrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('akun.pesanan.index', [
            'orders' => $request->user()->orders()->with(['items', 'shipment'])->paginate(10),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorizeOwner($request, $order);

        return view('akun.pesanan.show', ['order' => $order->load(['items.variant.product', 'payment', 'shipment'])]);
    }

    public function received(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        if ($order->status === OrderStatus::Dikirim) {
            $workflow->transition($order, OrderStatus::Selesai, $request->user());
        }

        return back()->with('flash', 'Terima kasih. Pesanan ditandai selesai.');
    }

    /** Barang pesanan lama dimasukkan lagi ke keranjang dengan harga dan stok terbaru */
    public function reorder(Request $request, Order $order, CartService $cart): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        $skipped = [];
        foreach ($order->items->where('is_gift', false) as $item) {
            /** @var OrderItem $item */
            if (! $item->variant) {
                $skipped[] = "{$item->product_name} {$item->aroma_name}";

                continue;
            }
            try {
                $cart->add($item->variant, $item->quantity);
            } catch (CartException) {
                $skipped[] = "{$item->product_name} {$item->aroma_name}";
            }
        }

        return redirect()->route('keranjang')->with('flash', $skipped
            ? 'Sebagian barang dimasukkan ke keranjang. Tidak tersedia: '.implode(', ', $skipped).'.'
            : 'Barang pesanan dimasukkan ke keranjang dengan harga terbaru.');
    }

    private function authorizeOwner(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 404);
    }
}
