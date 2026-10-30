<?php

namespace App\Support;

use App\Models\GiftRule;

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
            ->whereHas('giftVariant', fn ($query) => $query->where('stock', '>', 0))
            ->orderBy('min_subtotal')
            ->get());
    }

    public static function firstGiftThreshold(): ?int
    {
        return self::giftRules()->first()?->min_subtotal;
    }
}
