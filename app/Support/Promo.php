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

    /** "Free decant (min. belanja Rp100.000)" menjadi "free decant" */
    public static function giftLabel(GiftRule $rule): string
    {
        return lcfirst(trim(preg_replace('/\s*\(.*\)\s*$/', '', $rule->name)));
    }

    /**
     * Kalimat bar pengumuman: hadiah dari aturan yang aktif dulu (ditebalkan), lalu jaminan toko.
     *
     * @return list<array{text: string, promo: bool}>
     */
    public static function announcements(): array
    {
        $promos = self::giftRules()->map(fn (GiftRule $rule) => [
            'text' => 'Belanja min. '.Rupiah::format($rule->min_subtotal).' dapat '.self::giftLabel($rule),
            'promo' => true,
        ]);
        $facts = collect(['100% original', 'Botol kaca disegel sebelum dikirim', 'Toko Shopee sejak 2023'])
            ->map(fn (string $text) => ['text' => $text, 'promo' => false]);

        return $promos->concat($facts)->values()->all();
    }
}
