<?php

namespace App\Queries;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Query kartu produk untuk Beranda, Katalog, dan produk terkait:
 * harga termurah yang stoknya ada, rentang ukuran, dan jumlah aroma tanpa memuat ribuan SKU.
 */
class ProductCards
{
    public static function query(): Builder
    {
        $activeVariants = fn () => ProductVariant::query()
            ->whereColumn('product_variants.product_id', 'products.id')
            ->where('product_variants.is_active', true);

        return Product::query()
            ->visible()
            ->select('products.*')
            ->with(['brand', 'category', 'cover'])
            ->withCount('aromas')
            ->addSelect([
                // "mulai Rp…" dari SKU termurah yang stoknya ada (PRD, Harga & stok)
                'min_available_price' => $activeVariants()->where('stock', '>', 0)->selectRaw('min(price)'),
                'min_price' => $activeVariants()->selectRaw('min(price)'),
                'variant_count' => $activeVariants()->selectRaw('count(*)'),
                'min_ml' => $activeVariants()->join('sizes', 'sizes.id', '=', 'product_variants.size_id')->selectRaw('min(sizes.ml)'),
                'max_ml' => $activeVariants()->join('sizes', 'sizes.id', '=', 'product_variants.size_id')->selectRaw('max(sizes.ml)'),
            ]);
    }

    /**
     * Produk dengan botol terjual terbanyak di Shopee (Juli–Oktober 2026) mendapat label "Terlaris".
     *
     * @return Collection<int, int>
     */
    public static function bestsellerIds(): Collection
    {
        return once(fn () => Product::visible()
            ->where('sold_count', '>', 0)
            ->orderByDesc('sold_count')
            ->limit(Product::BESTSELLER_COUNT)
            ->pluck('id'));
    }
}
