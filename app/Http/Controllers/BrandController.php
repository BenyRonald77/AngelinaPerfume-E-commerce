<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Halaman Merek: semua merek A-Z dengan logo resminya (permintaan klien: tanpa pengelompokan asal) */
class BrandController extends Controller
{
    public function index(): View
    {
        $visible = fn (Builder $q) => $q->visible();

        $brands = Brand::whereHas('products', $visible)
            ->withCount(['products' => $visible])
            ->orderBy('name')
            ->get()
            ->map(fn (Brand $brand) => [
                'name' => $brand->name,
                'letter' => self::letter($brand->name),
                'products' => $brand->products_count,
                'logo' => $brand->logoUrl(),
                'href' => route('produk.index', ['merek' => [$brand->slug]]),
            ]);

        return view('merek', [
            'brands' => $brands,
            // Jumlah merek per huruf awal untuk filter A-Z; huruf tanpa merek tetap tampil tapi nonaktif
            'letters' => collect(range('A', 'Z'))->mapWithKeys(fn (string $l) => [$l => $brands->where('letter', $l)->count()])
                ->when($brands->contains('letter', '#'), fn ($c) => $c->put('#', $brands->where('letter', '#')->count())),
        ]);
    }

    /** Huruf awal untuk filter: LÉNOTES masuk L; nama yang diawali angka/simbol masuk "#" */
    private static function letter(string $name): string
    {
        $first = strtoupper(Str::ascii(mb_substr(trim($name), 0, 1)));

        return preg_match('/^[A-Z]$/', $first) ? $first : '#';
    }
}
