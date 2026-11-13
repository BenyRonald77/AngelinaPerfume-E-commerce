<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Support\BrandMenu;
use Illuminate\Database\Eloquent\Builder;
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
                'letter' => BrandMenu::letter($brand->name),
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
}
