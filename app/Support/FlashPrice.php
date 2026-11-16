<?php

namespace App\Support;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Harga flash sale yang sedang berlangsung. Satu sumber untuk halaman produk, keranjang,
 * checkout, dan pesanan, supaya harga yang ditampilkan sama dengan yang dibayar.
 */
final class FlashPrice
{
    /** @var array{key: string, items: Collection<int, FlashSaleItem>}|null */
    private static ?array $memo = null;

    /** Item flash sale yang sedang berlangsung, per product_variant_id (diingat per menit) */
    public static function running(): Collection
    {
        $key = now()->format('YmdHi');
        if (self::$memo === null || self::$memo['key'] !== $key) {
            self::$memo = [
                'key' => $key,
                'items' => FlashSaleItem::query()
                    ->whereHas('flashSale', fn ($q) => $q->running())
                    ->with('flashSale:id,name,ends_at')
                    ->get()
                    ->keyBy('product_variant_id'),
            ];
        }

        return self::$memo['items'];
    }

    /** Item flash yang berlaku untuk SKU ini, hanya bila memang lebih murah dari harga normal */
    public static function for(ProductVariant $variant): ?FlashSaleItem
    {
        $item = self::running()->get($variant->id);

        return $item && $item->sale_price < $variant->price ? $item : null;
    }

    public static function flush(): void
    {
        self::$memo = null;
    }

    /** Flash sale akan datang (dengan hitung mundur) yang memuat SKU ini, untuk catatan di halaman produk */
    public static function upcomingFor(Collection $variantIds): ?FlashSale
    {
        $sale = FlashSale::forHome();

        return $sale && ! $sale->isRunning() && $sale->items()->whereIn('product_variant_id', $variantIds)->exists() ? $sale : null;
    }
}
