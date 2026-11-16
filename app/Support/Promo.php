<?php

namespace App\Support;

use App\Models\CartItem;
use App\Models\GiftRule;
use App\Models\Product;
use Illuminate\Support\Collection;

final class Promo
{
    /**
     * Aturan hadiah yang aktif, termurah dulu. Promo hanya tampil di toko
     * setelah admin mengaktifkannya (aturan bisnis 9: data belum pasti tidak ditampilkan),
     * dan hanya selama stok hadiahnya ada, sama dengan syarat di checkout.
     */
    public static function giftRules()
    {
        return once(fn () => GiftRule::where('is_active', true)
            ->whereNotNull('gift_variant_id')
            ->whereHas('giftVariant', fn ($query) => $query->where('stock', '>', 0))
            ->with(['category', 'products:id'])
            ->orderBy('min_subtotal')
            ->get());
    }

    /**
     * Progres menuju tiap hadiah untuk isi keranjang. Aturan yang dibatasi jenis (misalnya decant)
     * hanya tampil bila keranjang memuat produk jenis itu.
     *
     * @param  Collection<int, CartItem>  $items
     * @return Collection<int, array{rule: GiftRule, current: int, target: int, reached: bool}>
     */
    public static function progress(Collection $items): Collection
    {
        return self::giftRules()
            ->filter(fn (GiftRule $rule) => $rule->category_id === null || $items->contains(fn (CartItem $i) => $rule->appliesTo($i->variant)))
            ->map(function (GiftRule $rule) use ($items) {
                $current = $rule->eligibleSubtotal($items);

                return ['rule' => $rule, 'current' => $current, 'target' => $rule->min_subtotal, 'reached' => $current >= $rule->min_subtotal];
            })
            ->values();
    }

    /** Aturan hadiah termurah yang berlaku untuk produk ini (untuk bar progres di halaman produk) */
    public static function ruleFor(Product $product): ?GiftRule
    {
        return self::giftRules()->first(fn (GiftRule $rule) => ($rule->category_id === null || (int) $rule->category_id === (int) $product->category_id)
            && ($rule->products->isEmpty() || $rule->products->contains('id', $product->id)));
    }
}
