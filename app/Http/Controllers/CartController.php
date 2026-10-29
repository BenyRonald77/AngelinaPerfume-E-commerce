<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Services\CartException;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        return view('keranjang');
    }

    /** Tombol "Tambah ke keranjang" dan "Beli sekarang" di halaman produk */
    public function store(Request $request, CartService $cart): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $variant = ProductVariant::with('product.category')->findOrFail($data['variant_id']);

        try {
            $message = $cart->add($variant, (int) $data['quantity']);
        } catch (CartException $e) {
            return back()->with('flash_error', $e->getMessage());
        }

        if ($request->boolean('buy_now')) {
            return redirect()->route('checkout');
        }

        return back()->with('flash', $message)->with('flash_cart', true);
    }
}
