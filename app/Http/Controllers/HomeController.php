<?php

namespace App\Http\Controllers;

use App\Enums\BrandOrigin;
use App\Models\Brand;
use App\Models\Product;
use App\Queries\ProductCards;
use App\Support\Promo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // Hero: koleksi travel size 15 ml, penyumbang omzet terbesar Agustus 2026 (PRD, Penjualan Shopee)
        $travel = ProductCards::query()
            ->whereHas('variants.size', fn (Builder $q) => $q->where('ml', 15))
            ->orderByDesc('aromas_count')
            ->first();

        return view('home', [
            'travel' => $travel,
            // Strip Terlaris: 9 produk, 3 per halaman
            'bestsellers' => ProductCards::query()->where('sold_count', '>', 0)->orderByDesc('sold_count')->take(9)->get(),
            'collections' => $this->collections(),
            'brandCount' => Brand::whereHas('products', fn (Builder $q) => $q->visible())->count(),
            'giftRules' => Promo::giftRules(),
        ]);
    }

    /**
     * Koleksi per asal merek (permintaan klien): foto dari produk terlaris di kelompok itu.
     * Kelompok tanpa produk tidak ditampilkan (aturan bisnis 9).
     *
     * @return list<array{origin: BrandOrigin, product: Product, brands: int, products: int, href: string}>
     */
    private function collections(): array
    {
        $items = [];
        foreach (BrandOrigin::cases() as $origin) {
            $inOrigin = fn (Builder $q) => $q->whereHas('brand', fn (Builder $b) => $b->where('origin', $origin));
            $products = Product::visible()->where($inOrigin)->count();
            if ($products === 0) {
                continue;
            }

            $items[] = [
                'origin' => $origin,
                'product' => ProductCards::query()->where($inOrigin)->orderByDesc('sold_count')->first(),
                'brands' => Brand::where('origin', $origin)->whereHas('products', fn (Builder $q) => $q->visible())->count(),
                'products' => $products,
                'href' => route('produk.index', ['asal' => $origin->value]),
            ];
        }

        return $items;
    }
}
