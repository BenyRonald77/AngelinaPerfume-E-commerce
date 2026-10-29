<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use App\Queries\ProductCards;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Grid produk dengan pencarian dan filter (F2). Filter tersimpan di URL supaya bisa dibagikan.
 * Di Beranda dipakai dengan $limit (tanpa paginasi), di /produk dengan paginasi.
 */
class Catalog extends Component
{
    use WithPagination;

    public const PER_PAGE = 24;

    public const PRICE_RANGES = [
        'di-bawah-25rb' => ['label' => 'Di bawah Rp25.000', 'min' => 0, 'max' => 24999],
        '25-50rb' => ['label' => 'Rp25.000 – Rp50.000', 'min' => 25000, 'max' => 50000],
        '50-150rb' => ['label' => 'Rp50.001 – Rp150.000', 'min' => 50001, 'max' => 150000],
        '150-300rb' => ['label' => 'Rp150.001 – Rp300.000', 'min' => 150001, 'max' => 300000],
        'di-atas-300rb' => ['label' => 'Di atas Rp300.000', 'min' => 300001, 'max' => PHP_INT_MAX],
    ];

    public const SORTS = [
        'terlaris' => 'Terlaris',
        'termurah' => 'Harga termurah',
        'termahal' => 'Harga termahal',
        'nama' => 'Nama A–Z',
    ];

    /** 0 = halaman katalog dengan paginasi */
    #[Locked]
    public int $limit = 0;

    #[Url(as: 'cari', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $jenis = '';

    #[Url(except: [])]
    public array $merek = [];

    #[Url(except: [])]
    public array $ukuran = [];

    #[Url(except: '')]
    public string $harga = '';

    #[Url(except: true)]
    public bool $tersedia = true;

    #[Url(except: 'terlaris')]
    public string $urut = 'terlaris';

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'jenis', 'merek', 'ukuran', 'harga', 'urut', 'tersedia']);
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return Category::where('slug', '!=', Category::HADIAH)
            ->whereHas('products', fn (Builder $q) => $q->where('is_visible', true))
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function brands()
    {
        return Brand::whereHas('products', fn (Builder $q) => $q->visible())->orderBy('name')->get(['name', 'slug']);
    }

    #[Computed]
    public function sizes()
    {
        return Size::orderBy('sort_order')->get();
    }

    public function activeFilterCount(): int
    {
        return (int) ($this->jenis !== '') + (int) ($this->harga !== '') + count($this->merek) + count($this->ukuran) + (int) ($this->search !== '');
    }

    public function render()
    {
        $query = $this->filteredQuery();
        $total = (clone $query)->count();

        $this->applySort($query);

        $products = $this->limit > 0
            ? $query->limit($this->limit)->get()
            : $query->paginate(self::PER_PAGE);

        return view('livewire.catalog', [
            'products' => $products,
            'total' => $total,
            'bestsellerIds' => ProductCards::bestsellerIds(),
            'allVisible' => $this->limit > 0 ? Product::visible()->count() : null,
        ]);
    }

    private function filteredQuery(): Builder
    {
        $query = ProductCards::query();

        foreach (preg_split('/\s+/', trim($this->search), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';
            $query->where(fn (Builder $q) => $q
                ->where('products.name', 'like', $like)
                ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', $like))
                ->orWhereHas('aromas', fn (Builder $a) => $a->where(fn (Builder $w) => $w
                    ->where('name', 'like', $like)
                    ->orWhere('notes_top', 'like', $like)
                    ->orWhere('notes_middle', 'like', $like)
                    ->orWhere('notes_base', 'like', $like)
                    ->orWhere('notes_general', 'like', $like))));
        }

        // Jenis yang belum punya produk tetap disaring (hasil kosong), bukan diabaikan
        if ($this->jenis !== '' && $this->jenis !== Category::HADIAH && array_key_exists($this->jenis, Category::DEFAULTS)) {
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', $this->jenis));
        }

        $brands = array_values(array_filter($this->merek, 'is_string'));
        if ($brands !== []) {
            $query->whereHas('brand', fn (Builder $q) => $q->whereIn('slug', $brands));
        }

        $sizes = array_values(array_filter(array_map('floatval', $this->ukuran)));
        $range = self::PRICE_RANGES[$this->harga] ?? null;

        // Ukuran, harga, dan stok harus cocok di SKU yang sama
        if ($sizes !== [] || $range || $this->tersedia) {
            $query->whereHas('variants', function (Builder $v) use ($sizes, $range) {
                $v->where('is_active', true);
                if ($this->tersedia) {
                    $v->where('stock', '>', 0);
                }
                if ($sizes !== []) {
                    $v->whereHas('size', fn (Builder $s) => $s->whereIn('ml', $sizes));
                }
                if ($range) {
                    $v->whereBetween('price', [$range['min'], $range['max']]);
                }
            });
        }

        return $query;
    }

    private function applySort(Builder $query): void
    {
        $price = 'coalesce(min_available_price, min_price)';

        match ($this->urut) {
            'termurah' => $query->orderByRaw("{$price} is null")->orderByRaw("{$price} asc"),
            'termahal' => $query->orderByRaw("{$price} is null")->orderByRaw("{$price} desc"),
            'nama' => $query->orderBy('products.name'),
            default => $query->orderByDesc('sold_count')->orderBy('products.name'),
        };
    }
}
