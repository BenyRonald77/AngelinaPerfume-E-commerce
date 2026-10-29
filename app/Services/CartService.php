<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Keranjang (F3): tersimpan di akun pembeli; untuk tamu memakai token di sesi
 * yang digabung ke akun saat masuk.
 */
class CartService
{
    private const SESSION_KEY = 'cart_token';

    public function current(bool $create = false): ?Cart
    {
        if ($user = auth()->user()) {
            return $create ? Cart::firstOrCreate(['user_id' => $user->id]) : $user->cart;
        }

        $token = session(self::SESSION_KEY);
        if (! $token && $create) {
            $token = Str::random(40);
            session([self::SESSION_KEY => $token]);
        }

        if (! $token) {
            return null;
        }

        return $create
            ? Cart::firstOrCreate(['session_id' => $token, 'user_id' => null])
            : Cart::where('session_id', $token)->whereNull('user_id')->first();
    }

    /** Batas jumlah satu SKU: stok, lalu batas beli per pesanan bila ada */
    public static function maxQuantity(ProductVariant $variant): int
    {
        return max(0, min($variant->stock, $variant->max_per_order ?? $variant->stock));
    }

    /**
     * @return string pesan untuk pembeli
     */
    public function add(ProductVariant $variant, int $quantity): string
    {
        $this->assertSellable($variant);

        $cart = $this->current(create: true);
        $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->id]);
        $wanted = ($item->exists ? $item->quantity : 0) + max(1, $quantity);
        $max = self::maxQuantity($variant);

        $item->quantity = min($wanted, $max);
        $item->save();

        return $wanted > $max
            ? "Jumlah disesuaikan menjadi {$max}: ".($variant->max_per_order && $variant->max_per_order < $variant->stock
                ? "maksimal {$variant->max_per_order} per pesanan."
                : "stok tersisa {$variant->stock}.")
            : 'Ditambahkan ke keranjang.';
    }

    public function setQuantity(CartItem $item, int $quantity): void
    {
        $this->assertOwned($item);

        if ($quantity < 1) {
            $item->delete();

            return;
        }

        $item->update(['quantity' => min($quantity, max(1, self::maxQuantity($item->variant)))]);
    }

    public function remove(CartItem $item): void
    {
        $this->assertOwned($item);
        $item->delete();
    }

    /**
     * Isi keranjang dengan status tiap baris.
     *
     * @return Collection<int, array{item: CartItem, problem: ?string}>
     */
    public function lines(): Collection
    {
        $cart = $this->current();
        if (! $cart) {
            return collect();
        }

        return $cart->items()
            ->with(['variant.product.category', 'variant.product.cover', 'variant.aroma', 'variant.size'])
            ->orderBy('id')
            ->get()
            ->map(fn (CartItem $item) => ['item' => $item, 'problem' => $this->problem($item)]);
    }

    public function count(): int
    {
        return (int) $this->current()?->items()->sum('quantity');
    }

    public function clear(): void
    {
        $this->current()?->items()->delete();
    }

    /** Dipanggil saat masuk: isi keranjang tamu dipindah ke keranjang akun */
    public function mergeGuestCart(User $user): void
    {
        $token = session(self::SESSION_KEY);
        $guest = $token ? Cart::where('session_id', $token)->whereNull('user_id')->with('items.variant')->first() : null;
        if (! $guest) {
            return;
        }

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        foreach ($guest->items as $guestItem) {
            $item = $cart->items()->firstOrNew(['product_variant_id' => $guestItem->product_variant_id]);
            $item->quantity = min(($item->exists ? $item->quantity : 0) + $guestItem->quantity, max(1, self::maxQuantity($guestItem->variant)));
            $item->save();
        }

        $guest->delete();
        session()->forget(self::SESSION_KEY);
    }

    private function problem(CartItem $item): ?string
    {
        $variant = $item->variant;

        return match (true) {
            ! $variant->is_active || ! $variant->product->is_visible => 'Tidak dijual lagi. Hapus dari keranjang.',
            $variant->stock === 0 => 'Stok habis.',
            $item->quantity > self::maxQuantity($variant) => 'Melebihi stok atau batas beli (maks. '.self::maxQuantity($variant).').',
            default => null,
        };
    }

    private function assertSellable(ProductVariant $variant): void
    {
        $product = $variant->product;
        if (! $variant->is_active || ! $product->is_visible || $product->category->slug === Category::HADIAH) {
            throw new CartException('Produk ini tidak dijual.');
        }
        if ($variant->stock < 1) {
            throw new CartException('Stok habis.');
        }
    }

    private function assertOwned(CartItem $item): void
    {
        if ($item->cart_id !== $this->current()?->id) {
            abort(403);
        }
    }
}
