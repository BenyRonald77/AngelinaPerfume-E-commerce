<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Queries\ProductCards;
use App\Support\Promo;
use App\Support\Rupiah;
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
            'collections' => $this->collections($travel),
            'brandCount' => Brand::whereHas('products', fn (Builder $q) => $q->visible())->count(),
            'giftRules' => Promo::giftRules(),
        ]);
    }

    /**
     * Lingkaran Koleksi: foto dari produk terlaris di kelompok itu, rentang ukuran dari SKU yang dijual.
     */
    private function collections(?Product $travel): array
    {
        $top = fn (callable $constraint) => ProductCards::query()->where($constraint)->orderByDesc('sold_count')->first();
        $inCategory = fn (string $slug) => fn (Builder $q) => $q->whereHas('category', fn (Builder $c) => $c->where('slug', $slug));
        $mist = fn (Builder $q) => $q->where('products.name', 'like', '%mist%');

        $items = [
            [
                'name' => 'Decant',
                'sub' => $this->sizeRange(fn (Builder $v) => $v->whereHas('product.category', fn (Builder $c) => $c->where('slug', Category::DECANT))),
                'product' => $top($inCategory(Category::DECANT)),
                'href' => route('produk.index', ['jenis' => Category::DECANT]),
            ],
            [
                'name' => 'Travel size',
                'sub' => '15 ml',
                'product' => $travel,
                'href' => route('produk.index', ['ukuran' => ['15']]),
            ],
            [
                'name' => 'Full size',
                'sub' => $this->sizeRange(fn (Builder $v) => $v->whereHas('product.category', fn (Builder $c) => $c->where('slug', Category::FULL_SIZE))->whereHas('size', fn (Builder $s) => $s->where('ml', '>', 15))),
                'product' => $top(fn (Builder $q) => $inCategory(Category::FULL_SIZE)($q)->when($travel, fn (Builder $w) => $w->whereKeyNot($travel->id))),
                'href' => route('produk.index', ['jenis' => Category::FULL_SIZE]),
            ],
            [
                'name' => 'Hair & body mist',
                'sub' => $this->sizeRange(fn (Builder $v) => $v->whereHas('product', $mist)),
                'product' => $top($mist),
                'href' => route('produk.index', ['cari' => 'mist']),
            ],
            [
                'name' => 'Aksesori',
                'sub' => 'Atomizer, kotak, vial',
                'product' => $top($inCategory(Category::AKSESORI)),
                'href' => route('produk.index', ['jenis' => Category::AKSESORI]),
            ],
        ];

        return array_values(array_filter($items, fn (array $item) => $item['product'] !== null));
    }

    /** "0,5–10 ml" dari SKU aktif yang cocok */
    private function sizeRange(callable $constraint): ?string
    {
        $query = ProductVariant::query()
            ->join('sizes', 'sizes.id', '=', 'product_variants.size_id')
            ->where('product_variants.is_active', true)
            ->whereHas('product', fn (Builder $q) => $q->visible())
            ->where($constraint);

        $min = (clone $query)->min('sizes.ml');
        $max = (clone $query)->max('sizes.ml');

        if ($min === null) {
            return null;
        }

        return $min == $max ? Rupiah::ml((float) $min).' ml' : Rupiah::ml((float) $min).'–'.Rupiah::ml((float) $max).' ml';
    }
}
