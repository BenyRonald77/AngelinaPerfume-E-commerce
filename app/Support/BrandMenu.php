<?php

namespace App\Support;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Data menu dan filter merek (dropdown Merek di navbar dan filter Merek di katalog):
 * merek yang punya produk tampil, dengan huruf awal dan asalnya. Disimpan di cache 10 menit
 * karena dipakai di header setiap halaman; dihapus saat data merek berubah.
 */
final class BrandMenu
{
    private const CACHE_KEY = 'menu-merek';

    /** @return list<array{name: string, slug: string, letter: string, origin: string}> */
    public static function brands(): array
    {
        return Cache::remember(self::CACHE_KEY, 600, fn () => Brand::whereHas('products', fn (Builder $q) => $q->visible())
            ->orderBy('name')
            ->get(['name', 'slug', 'origin'])
            ->map(fn (Brand $brand) => [
                'name' => $brand->name,
                'slug' => $brand->slug,
                'letter' => self::letter($brand->name),
                'origin' => $brand->origin->value,
            ])
            ->all());
    }

    /** Huruf awal untuk indeks A-Z: LÉNOTES masuk L; nama yang diawali angka/simbol masuk "#" */
    public static function letter(string $name): string
    {
        $first = strtoupper(Str::ascii(mb_substr(trim($name), 0, 1)));

        return preg_match('/^[A-Z]$/', $first) ? $first : '#';
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
