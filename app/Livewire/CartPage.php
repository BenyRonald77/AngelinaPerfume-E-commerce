<?php

namespace App\Livewire;

use App\Models\CartItem;
use App\Services\CartService;
use App\Services\OrderService;
use App\Support\Promo;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/** Halaman keranjang (F3): ubah jumlah, hapus, ringkasan dan hadiah */
class CartPage extends Component
{
    #[Computed]
    public function lines(): Collection
    {
        return app(CartService::class)->lines();
    }

    #[Computed]
    public function subtotal(): int
    {
        return $this->lines->sum(fn ($line) => $line['item']->lineTotal());
    }

    /** Hadiah yang sudah didapat dan target hadiah berikutnya */
    #[Computed]
    public function gift(): array
    {
        $items = $this->lines->pluck('item');

        return [
            'earned' => $items->isEmpty() ? collect() : app(OrderService::class)->gifts($items, $this->subtotal),
            'next' => Promo::giftRules()->first(fn ($rule) => $rule->min_subtotal > $this->subtotal),
        ];
    }

    public function increment(int $itemId): void
    {
        $item = $this->item($itemId);
        app(CartService::class)->setQuantity($item, $item->quantity + 1);
        $this->refresh();
    }

    public function decrement(int $itemId): void
    {
        $item = $this->item($itemId);
        app(CartService::class)->setQuantity($item, max(1, $item->quantity - 1));
        $this->refresh();
    }

    public function remove(int $itemId): void
    {
        app(CartService::class)->remove($this->item($itemId));
        $this->refresh();
        $this->dispatch('toko-pesan', pesan: 'Barang dihapus dari keranjang.');
    }

    public function render()
    {
        return view('livewire.cart-page');
    }

    private function item(int $id): CartItem
    {
        $item = app(CartService::class)->current()?->items()->with('variant')->find($id);
        abort_unless($item, 404);

        return $item;
    }

    private function refresh(): void
    {
        unset($this->lines, $this->subtotal, $this->gift);
        $this->dispatch('cart-count', count: app(CartService::class)->count());
    }
}
