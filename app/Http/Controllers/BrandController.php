<?php

namespace App\Http\Controllers;

use App\Enums\BrandOrigin;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Halaman Merek: semua merek per asal, dengan logo (bila diunggah admin) atau foto produk terlarisnya */
class BrandController extends Controller
{
    public function index(): View
    {
        // Satu kueri untuk semua produk tampil: jumlah per merek dan foto produk terlaris
        $products = Product::visible()->with('cover')->get(['id', 'brand_id', 'sold_count'])->groupBy('brand_id');

        $brands = Brand::whereHas('products', fn (Builder $q) => $q->visible())
            ->orderBy('name')
            ->get()
            ->map(function (Brand $brand) use ($products) {
                $own = $products->get($brand->id, collect());
                $top = $own->sortByDesc('sold_count')->first(fn (Product $p) => $p->cover !== null);

                return [
                    'name' => $brand->name,
                    'origin' => $brand->origin,
                    'products' => $own->count(),
                    'logo' => $brand->logo ? Storage::disk('public')->url($brand->logo) : null,
                    'photo' => $top?->cover?->url(800),
                    'href' => route('produk.index', ['merek' => [$brand->slug]]),
                ];
            });

        return view('merek', [
            'groups' => collect(BrandOrigin::cases())->map(fn (BrandOrigin $origin) => [
                'origin' => $origin,
                'brands' => $brands->where('origin', $origin)->values(),
            ]),
            'total' => $brands->count(),
        ]);
    }
}
