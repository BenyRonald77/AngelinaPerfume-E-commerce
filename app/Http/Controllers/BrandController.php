<?php

namespace App\Http\Controllers;

use App\Models\Brand;
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
                'products' => $brand->products_count,
                'logo' => $brand->logoUrl(),
                'href' => route('produk.index', ['merek' => [$brand->slug]]),
            ]);

        return view('merek', ['brands' => $brands]);
    }
}
