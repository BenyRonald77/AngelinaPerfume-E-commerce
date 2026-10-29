<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\StockReason;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\GiftRule;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use App\Shipping\ShippingOption;
use App\Shipping\ShippingProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Membuat pesanan dari keranjang (checkout). Aturan bisnis yang dijaga kode:
 * stok dikunci dan dikurangi saat pesanan dibuat, harga dan alamat disalin,
 * batas bayar 24 jam, hadiah otomatis bernilai Rp0.
 */
class OrderService
{
    public function __construct(
        private CartService $cart,
        private ShippingProvider $shipping,
        private Xendit $xendit,
    ) {}

    /**
     * Ringkasan untuk halaman checkout, dihitung dari isi keranjang saat ini.
     *
     * @return array{lines: Collection, subtotal: int, weight: int, packaging_fee: int, gifts: Collection, options: list<ShippingOption>}
     */
    public function quote(?Address $address, bool $packaging): array
    {
        $lines = $this->cart->lines();
        $subtotal = $lines->sum(fn ($line) => $line['item']->lineTotal());
        $weight = $lines->sum(fn ($line) => $line['item']->variant->weight_grams * $line['item']->quantity)
            + ($packaging ? Setting::int('packaging_weight_grams') : 0);

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'weight' => $weight,
            'packaging_fee' => $packaging ? Setting::int('packaging_fee') : 0,
            'gifts' => $this->gifts($lines->pluck('item'), $subtotal),
            'options' => $address ? $this->shipping->options($address->province, $weight) : [],
        ];
    }

    public function place(User $user, Address $address, string $shippingKey, bool $packaging, ?string $note): Order
    {
        if ($address->user_id !== $user->id) {
            abort(403);
        }

        $order = DB::transaction(function () use ($user, $address, $shippingKey, $packaging, $note) {
            $items = $this->cart->current()?->items()->get() ?? collect();
            if ($items->isEmpty()) {
                throw new CartException('Keranjang kosong.');
            }

            // Kunci SKU supaya dua pembeli tidak mengambil stok terakhir bersamaan
            $variants = ProductVariant::whereIn('id', $items->pluck('product_variant_id'))
                ->with(['product', 'aroma', 'size'])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $problems = [];
            foreach ($items as $item) {
                $variant = $variants[$item->product_variant_id];
                $max = CartService::maxQuantity($variant);
                if (! $variant->is_active || ! $variant->product->is_visible) {
                    $problems[] = "{$variant->product->name} {$variant->aroma->name} tidak dijual lagi.";
                } elseif ($item->quantity > $max) {
                    $problems[] = "{$variant->product->name} {$variant->aroma->name}: tersedia {$max}.";
                }
            }
            if ($problems) {
                throw new CartException('Periksa keranjang: '.implode(' ', $problems));
            }

            $subtotal = $items->sum(fn (CartItem $i) => $variants[$i->product_variant_id]->price * $i->quantity);
            $packagingFee = $packaging ? Setting::int('packaging_fee') : 0;
            $weight = $items->sum(fn (CartItem $i) => $variants[$i->product_variant_id]->weight_grams * $i->quantity)
                + ($packaging ? Setting::int('packaging_weight_grams') : 0);

            // Ongkir dihitung ulang di server; pilihan dari browser hanya kuncinya
            $option = collect($this->shipping->options($address->province, $weight))->firstWhere('key', $shippingKey);
            if (! $option) {
                throw new CartException('Pilihan pengiriman tidak tersedia untuk alamat ini. Pilih ulang pengiriman.');
            }

            $order = Order::create([
                'code' => Order::newCode(),
                'user_id' => $user->id,
                ...$address->snapshot(),
                'subtotal' => $subtotal,
                'packaging_fee' => $packagingFee,
                'shipping_cost' => $option->cost,
                'total' => $subtotal + $packagingFee + $option->cost,
                'total_weight_grams' => $weight,
                'status' => OrderStatus::MenungguPembayaran,
                'payment_due_at' => now()->addHours(Setting::int('payment_window_hours')),
                'customer_note' => filled($note) ? mb_substr(trim($note), 0, 500) : null,
            ]);

            foreach ($items as $item) {
                $variant = $variants[$item->product_variant_id];
                $this->addItem($order, $variant, $item->quantity, $variant->price, false);
            }

            foreach ($this->gifts($items->map(fn (CartItem $i) => $i->setRelation('variant', $variants[$i->product_variant_id])), $subtotal) as $rule) {
                $gift = ProductVariant::whereKey($rule->gift_variant_id)->with(['product', 'aroma', 'size'])->lockForUpdate()->first();
                if ($gift && $gift->stock > 0) {
                    $this->addItem($order, $gift, 1, 0, true);
                }
            }

            $order->shipment()->create(['courier' => $option->label(), 'service' => $option->estimate, 'cost' => $option->cost]);
            $order->payment()->create(['amount' => $order->total, 'status' => Payment::PENDING]);

            $this->cart->clear();

            return $order;
        });

        $this->createInvoice($order);
        $this->notify($order);

        return $order;
    }

    /** Membuat (atau membuat ulang) invoice Xendit; gagal tidak membatalkan pesanan */
    public function createInvoice(Order $order): void
    {
        if (! Xendit::enabled() || ! $order->isAwaitingPayment() || $order->payment?->invoice_url) {
            return;
        }

        try {
            $invoice = $this->xendit->createInvoice($order);
            $order->payment->update(['xendit_invoice_id' => $invoice['id'], 'invoice_url' => $invoice['invoice_url']]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Aturan hadiah aktif yang terpenuhi (F11). Kosongnya daftar produk berarti berlaku untuk semua.
     *
     * @param  Collection<int, CartItem>  $items
     * @return Collection<int, GiftRule>
     */
    public function gifts(Collection $items, int $subtotal): Collection
    {
        $productIds = $items->map(fn (CartItem $i) => $i->variant->product_id)->unique();

        return GiftRule::where('is_active', true)
            ->where('min_subtotal', '<=', $subtotal)
            ->whereNotNull('gift_variant_id')
            ->with(['products:id', 'giftVariant.product'])
            ->orderBy('min_subtotal')
            ->get()
            ->filter(fn (GiftRule $rule) => $rule->products->isEmpty() || $rule->products->pluck('id')->intersect($productIds)->isNotEmpty())
            ->filter(fn (GiftRule $rule) => $rule->giftVariant?->stock > 0)
            ->values();
    }

    private function addItem(Order $order, ProductVariant $variant, int $quantity, int $price, bool $gift): void
    {
        $order->items()->create([
            'product_variant_id' => $variant->id,
            'sku' => $variant->sku,
            'product_name' => $variant->product->name,
            'aroma_name' => $variant->aroma->name,
            'size_label' => $variant->size?->label,
            'price' => $price,
            'quantity' => $quantity,
            'weight_grams' => $variant->weight_grams,
            'is_gift' => $gift,
        ]);

        $variant->adjustStock(-$quantity, StockReason::Pesanan, null, "Pesanan {$order->code}", $order->id);
    }

    /** Email dikirim setelah respons sampai ke pembeli, supaya checkout tidak menunggu SMTP */
    private function notify(Order $order): void
    {
        defer(function () use ($order) {
            try {
                $order->user->notify(new OrderStatusChanged($order));
            } catch (Throwable $e) {
                report($e);
            }
        });
    }
}
