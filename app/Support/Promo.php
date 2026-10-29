<?php

namespace App\Support;

use App\Models\GiftRule;

final class Promo
{
    /**
     * Aturan hadiah yang aktif, termurah dulu. Promo hanya tampil di toko
     * setelah admin mengaktifkannya (aturan bisnis 9: data belum pasti tidak ditampilkan).
     */
    public static function giftRules()
    {
        return once(fn () => GiftRule::where('is_active', true)->orderBy('min_subtotal')->get());
    }

    public static function firstGiftThreshold(): ?int
    {
        return self::giftRules()->first()?->min_subtotal;
    }
}
