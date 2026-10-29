<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAroma;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Queries\ProductCards;
use App\Support\Promo;
use App\Support\WhatsApp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('produk.index');
    }

    public function show(Request $request, Product $product): View
    {
        abort_unless($product->is_visible && $product->category->slug !== Category::HADIAH, 404);

        $product->load([
            'brand', 'category', 'images',
            'aromas.variants' => fn ($q) => $q->where('is_active', true)->with('size'),
        ]);

        $variants = $product->aromas
            ->flatMap->variants
            ->sortBy(fn (ProductVariant $v) => [$v->size?->sort_order ?? 99, $v->note ?? ''])
            ->values();

        // Aroma tanpa SKU yang dijual tidak ditampilkan
        $aromas = $product->aromas->filter(fn (ProductAroma $a) => $a->variants->isNotEmpty())->values();
        abort_if($aromas->isEmpty(), 404);

        // Pilihan awal: SKU dari URL (?varian=), atau aroma pertama yang ada stoknya dengan ukuran terkecil yang tersedia
        $firstInStockAroma = $aromas->first(fn (ProductAroma $a) => $a->variants->contains(fn (ProductVariant $v) => $v->stock > 0));
        $initial = $variants->firstWhere('sku', $request->query('varian'))
            ?? $variants->first(fn (ProductVariant $v) => $v->stock > 0 && $v->product_aroma_id === $firstInStockAroma?->id)
            ?? $variants->first();

        $isDecant = $product->category->slug === Category::DECANT;

        return view('produk.show', [
            'product' => $product,
            'aromas' => $aromas,
            'variants' => $variants,
            'initial' => $initial,
            'images' => $product->images,
            'payload' => [
                'aromas' => $aromas->map(fn (ProductAroma $a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'note' => $a->note,
                    'image' => $a->image ? [
                        'src' => $a->imageUrl(800),
                        'srcset' => $a->imageUrl(800).' 800w, '.$a->imageUrl(1600).' 1600w',
                        'w' => $a->image_width, 'h' => $a->image_height,
                    ] : null,
                    'notes' => [
                        'top' => $a->notes_top, 'middle' => $a->notes_middle,
                        'base' => $a->notes_base, 'general' => $a->notes_general,
                    ],
                ])->values(),
                'variants' => $variants->map(fn (ProductVariant $v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'aroma' => $v->product_aroma_id,
                    'size' => $v->size?->label,
                    'kind' => $isDecant ? $v->size?->bottle_type->label() : null,
                    'note' => $v->note,
                    'price' => $v->price,
                    'stock' => $v->stock,
                    'limit' => $v->max_per_order,
                    'weight' => $v->weight_grams,
                ])->values(),
                'images' => $product->images->map(fn (ProductImage $i) => [
                    'src' => $i->url(800), 'srcset' => $i->url(800).' 800w, '.$i->url(1600).' 1600w', 'w' => $i->width, 'h' => $i->height,
                ])->values(),
                'initial' => ['aroma' => $initial->product_aroma_id, 'variant' => $initial->id],
                'lowStock' => Setting::int('low_stock_threshold'),
                'gift' => Promo::firstGiftThreshold(),
                'whatsapp' => WhatsApp::baseUrl(),
                'productName' => $product->name,
            ],
            'related' => $this->related($product),
            'isDecant' => $isDecant,
            'hasBpom' => Str::contains((string) $product->description, 'BPOM', ignoreCase: true),
        ]);
    }

    private function related(Product $product)
    {
        $sameBrand = ProductCards::query()
            ->whereKeyNot($product->id)
            ->when($product->brand_id, fn (Builder $q) => $q->where('brand_id', $product->brand_id))
            ->orderByDesc('sold_count')
            ->take(4)
            ->get();

        if ($sameBrand->count() >= 4 || ! $product->brand_id) {
            return $sameBrand;
        }

        // Dilengkapi produk terlaris dengan jenis yang sama
        return $sameBrand->concat(ProductCards::query()
            ->whereKeyNot([$product->id, ...$sameBrand->modelKeys()])
            ->where('category_id', $product->category_id)
            ->orderByDesc('sold_count')
            ->take(4 - $sameBrand->count())
            ->get());
    }
}
