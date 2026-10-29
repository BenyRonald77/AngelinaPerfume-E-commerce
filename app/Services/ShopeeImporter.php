<?php

namespace App\Services;

use App\Enums\StockReason;
use App\Models\Brand;
use App\Models\Category;
use App\Models\GiftRule;
use App\Models\Product;
use App\Models\ProductAroma;
use App\Models\ProductImage;
use App\Models\ProductShopeeListing;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Size;
use App\Support\Images\WebpConverter;
use App\Support\Shopee\BrandMatcher;
use App\Support\Shopee\DescriptionParser;
use App\Support\Shopee\ImportReport;
use App\Support\Shopee\InfoFile;
use App\Support\Shopee\ProductNameParser;
use App\Support\Shopee\SalesInfoReader;
use App\Support\Shopee\VariationParser;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Impor katalog dari folder ekspor Shopee (shopee-media).
 *
 * Aman dijalankan ulang: produk dicocokkan lewat nama folder, varian lewat kode variasi
 * Shopee. Nomor produk, nomor aroma, dan kode SKU tidak berubah. Harga, stok, berat, dan
 * batas beli selalu diperbarui; nama, merek, dan deskripsi hanya diisi saat produk baru
 * (atau dengan opsi timpa), supaya perubahan admin tidak hilang.
 */
class ShopeeImporter
{
    private ImportReport $report;

    private BrandMatcher $brands;

    private array $corrections = [];

    private array $aromaNotes = [];

    /** @var array<string, int> */
    private array $maxPurchase = [];

    /** @var array<string, Size> ml => ukuran */
    private array $sizes = [];

    public function __construct(
        private ProductNameParser $names,
        private VariationParser $variations,
        private DescriptionParser $descriptions,
        private WebpConverter $converter,
        private SalesInfoReader $salesInfo,
    ) {}

    /**
     * @param  list<string>  $onlyFolders  kosong = semua folder
     */
    public function import(string $basePath, bool $withImages = true, bool $overwrite = false, array $onlyFolders = [], ?Closure $onProgress = null): ImportReport
    {
        $this->report = new ImportReport;
        $csv = $basePath.DIRECTORY_SEPARATOR.'harga-stok-semua.csv';

        if (! is_file($csv)) {
            throw new RuntimeException("File {$csv} tidak ditemukan.");
        }

        $data = config('toko.import_data_path');
        $this->brands = BrandMatcher::fromDataFile("{$data}/merek.php");
        $this->corrections = require "{$data}/koreksi-shopee.php";
        $this->aromaNotes = require "{$data}/notes-aroma.php";
        $this->sizes = Size::all()->keyBy(fn (Size $s) => $this->mlKey($s->ml))->all();
        $this->maxPurchase = $this->readMaxPurchase($basePath);
        Category::ensureDefaults();

        $groups = $this->readCsv($csv);
        $folders = array_keys($groups);
        sort($folders, SORT_STRING);

        if ($onlyFolders !== []) {
            $folders = array_values(array_intersect($folders, $onlyFolders));
        }

        foreach ($folders as $folder) {
            try {
                DB::transaction(fn () => $this->importFolder($basePath, $folder, $groups[$folder], $withImages, $overwrite));
            } catch (Throwable $e) {
                $this->report->warn($folder, 'gagal diimpor: '.$e->getMessage());
            }

            $onProgress?->__invoke($folder);
        }

        $this->ensureGiftRules();
        $this->shareNotesWithinBrand();
        $this->importBestsellers($basePath);

        return $this->report;
    }

    /**
     * produk-terlaris.csv (rekap pesanan Shopee) memakai nama listing tanpa label;
     * listing PART ditulis "MYKONOS (decant, semua PART)".
     */
    private function importBestsellers(string $basePath): void
    {
        $path = $basePath.DIRECTORY_SEPARATOR.'produk-terlaris.csv';
        if (! is_file($path)) {
            return;
        }

        $normalize = function (string $name): string {
            $name = preg_replace('/\[[^\]]*\]/u', ' ', $name);
            $name = explode('|', $name)[0];
            $name = preg_replace('/angelia\s+perfume/i', ' ', $name);

            return trim(preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($name)));
        };

        $byName = [];
        foreach (ProductShopeeListing::with('product')->get() as $listing) {
            $byName[$normalize($listing->original_name)] = $listing->product;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle, escape: '\\');
        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            [$name, $sold] = [$row[0] ?? '', (int) ($row[1] ?? 0)];

            $product = preg_match('/^(\w+) \(decant, semua part\)$/i', $name, $m)
                ? Product::where('source_folder', 'decant-'.strtolower($m[1]).'-semua-varian')->first()
                : ($byName[$normalize($name)] ?? null);

            if ($product) {
                $product->update(['sold_count' => $sold]);
            } elseif ($name !== '') {
                $this->report->note('produk-terlaris.csv', "\"{$name}\" tidak cocok dengan produk mana pun.");
            }
        }
        fclose($handle);
    }

    /**
     * Aroma yang sama dari merek yang sama memakai notes yang sama: decant MYKONOS Penthouse
     * mendapat notes dari deskripsi full size MYKONOS Penthouse. Isian yang sudah ada tidak ditimpa.
     */
    private function shareNotesWithinBrand(): void
    {
        $key = fn (ProductAroma $a) => $a->product->brand_id.'|'.trim(preg_replace(
            ['/\([^)]*\)/', '/\b(edp|edt|extrait)\b/i', '/[^a-z0-9]+/'],
            ['', '', ' '],
            mb_strtolower($a->name),
        ));

        $aromas = ProductAroma::with('product')
            ->whereHas('product', fn ($q) => $q->whereNotNull('brand_id'))
            ->get();

        $withNotes = $aromas->filter->hasNotes()->keyBy($key);
        $filled = 0;

        foreach ($aromas->reject->hasNotes() as $aroma) {
            if ($source = $withNotes->get($key($aroma))) {
                $aroma->update($source->only(ProductAroma::NOTE_FIELDS));
                $filled++;
            }
        }

        if ($filled > 0) {
            $this->report->note('notes', "{$filled} aroma mendapat notes dari aroma bernama sama di merek yang sama.");
        }
    }

    /**
     * Listing GWP Shopee menjadi aturan hadiah otomatis (aturan bisnis 6).
     * Dibuat tidak aktif: produk yang ikut promo dan hadiahnya masih ditanyakan ke toko.
     */
    private function ensureGiftRules(): void
    {
        $rules = [
            'gwp-freebies-mystery-gift-for-minimum-purchase-100k-angelia-24008815732' => ['Free decant (min. belanja Rp100.000)', 100000],
            'freebies-random-official-tester-minimum-purchase-400k-29750302047' => ['Free tester random (min. belanja Rp400.000)', 400000],
        ];

        foreach ($rules as $folder => [$name, $min]) {
            $variant = ProductVariant::whereHas('product', fn ($q) => $q->where('source_folder', $folder))->first();

            if ($variant && ! GiftRule::where('min_subtotal', $min)->exists()) {
                GiftRule::create(['name' => $name, 'min_subtotal' => $min, 'gift_variant_id' => $variant->id, 'is_active' => false]);
            }
        }
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function importFolder(string $basePath, string $folder, array $rows, bool $withImages, bool $overwrite): void
    {
        $dir = $basePath.DIRECTORY_SEPARATOR.$folder;
        $info = is_file("{$dir}/info.txt") ? InfoFile::read("{$dir}/info.txt") : new InfoFile([], []);
        $listings = $info->listings ?: [['id' => $rows[0]['kode_produk'], 'parent_sku' => null, 'name' => $rows[0]['nama_produk']]];
        $parsed = $this->names->parse($listings[0]['name'] ?: $rows[0]['nama_produk']);

        if ($parsed->category === ProductNameParser::PACKAGING) {
            Setting::put('packaging_fee', (string) (int) $rows[0]['harga']);
            Setting::put('packaging_weight_grams', (string) (int) $rows[0]['berat_gram']);
            $this->report->note($folder, 'dijadikan opsi kemasan tambahan di checkout, bukan produk katalog.');

            return;
        }

        $fix = $this->corrections['produk'][$folder] ?? [];
        $brandName = array_key_exists('brand', $fix)
            ? $fix['brand']
            : ($this->brands->match($parsed->name) ?? $this->brands->match($listings[0]['name']));
        $name = $fix['name'] ?? $this->tidyName($parsed->name, $brandName);
        $description = is_file("{$dir}/deskripsi.txt") ? trim(file_get_contents("{$dir}/deskripsi.txt")) : '';

        if ($brandName === null && in_array($parsed->category, [Category::DECANT, Category::FULL_SIZE], true)) {
            $this->report->warn($folder, "merek tidak dikenali dari nama \"{$parsed->name}\".");
        }

        $product = Product::firstOrNew(['source_folder' => $folder]);
        $isNew = ! $product->exists;

        if ($isNew) {
            $product->number = (int) Product::max('number') + 1;
            $this->report->productsCreated++;
        } else {
            $this->report->productsUpdated++;
        }

        if ($isNew || $overwrite) {
            $product->fill([
                'name' => $name,
                'slug' => $this->uniqueSlug($name, $product),
                'brand_id' => $brandName ? Brand::firstOrCreate(['slug' => Str::slug($brandName)], ['name' => $brandName])->id : null,
                'category_id' => Category::where('slug', $parsed->category)->value('id'),
                'concentration' => array_key_exists('concentration', $fix) ? $fix['concentration'] : $parsed->concentration,
                // Deskripsi decant hanya aturan toko yang diulang: tampil di halaman Bantuan (aturan bisnis 10)
                'description' => $parsed->category === Category::DECANT ? null : $this->descriptions->displayText($description),
                'is_visible' => $parsed->category !== Category::HADIAH,
            ]);
        }

        $product->shopee_description = $description ?: null;
        $product->save();

        foreach ($listings as $listing) {
            ProductShopeeListing::updateOrCreate(
                ['shopee_product_id' => $listing['id']],
                ['product_id' => $product->id, 'parent_sku' => $listing['parent_sku'], 'original_name' => $listing['name'] ?: $rows[0]['nama_produk']],
            );
        }

        $defaultAroma = $this->perfumeName($name, $brandName);
        $parsedRows = $this->parseRows($folder, $rows, $parsed->category, $defaultAroma, $parsed->sizesMl, $parsed->packagingNote, $fix);
        $variantFiles = $this->variantImageFiles($dir);
        $aromas = $this->syncAromas($product, $folder, $parsedRows, $variantFiles, $description, $parsed->category, $withImages);
        $this->syncVariants($product, $folder, $parsedRows, $aromas);
        $this->syncProductImages($product, $dir, $info, $withImages);
    }

    /**
     * @return list<array{row: array<string, string>, aroma: string, option: string, ml: ?float, aromaNote: ?string, variantNote: ?string}>
     */
    private function parseRows(string $folder, array $rows, string $category, string $defaultAroma, array $nameSizes, ?string $packagingNote, array $fix): array
    {
        $result = [];

        foreach ($rows as $row) {
            $variation = $this->variations->parse($row['aroma'], $row['ukuran'], $category, $defaultAroma, $nameSizes);

            if ($variation === null) {
                $this->report->warn($folder, "variasi tidak dikenali dan dilewati: aroma \"{$row['aroma']}\", ukuran \"{$row['ukuran']}\" (kode variasi {$row['kode_variasi']}).");

                continue;
            }

            $aroma = $fix['aroma'] ?? $this->corrections['aroma'][$variation->aroma] ?? $variation->aroma;
            // Tanggal kedaluwarsa melekat ke aroma; keterangan lain (versi, kemasan) ke varian
            $isExpiry = $variation->note !== null && preg_match('/^(Exp )?\d{1,2}\/\d{2}$/', $variation->note);

            $result[] = [
                'row' => $row,
                'aroma' => $aroma,
                // Pilihan tingkat pertama di Shopee: dasar nama file foto varian
                'option' => $row['aroma'] !== '' ? $row['aroma'] : $row['ukuran'],
                'ml' => $variation->ml,
                'aromaNote' => $isExpiry ? $variation->note : null,
                'variantNote' => $isExpiry ? null : ($variation->note ?? $packagingNote),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, ProductAroma> nama aroma => model
     */
    private function syncAromas(Product $product, string $folder, array $parsedRows, array $variantFiles, string $description, string $category, bool $withImages): array
    {
        // Urutan aroma mengikuti nomor foto varian di Shopee, lalu urutan di CSV.
        // Full size satu aroma memakai foto pilihan ukuran pertama yang ada.
        $order = [];
        foreach ($parsedRows as $i => $p) {
            $file = $variantFiles[$this->photoSlug($p['option'])] ?? null;
            $current = $order[$p['aroma']] ?? null;

            if ($current === null) {
                $order[$p['aroma']] = ['file' => $file, 'rank' => $file ? $file['number'] : 1000 + $i, 'note' => $p['aromaNote']];
            } elseif ($file && (! $current['file'] || $file['number'] < $current['file']['number'])) {
                $order[$p['aroma']]['file'] = $file;
                $order[$p['aroma']]['rank'] = $file['number'];
            }
        }
        uasort($order, fn ($a, $b) => $a['rank'] <=> $b['rank']);

        $singleAroma = count($order) === 1;
        $nextNumber = (int) ProductAroma::where('product_id', $product->id)->max('number') + 1;
        $models = [];
        $position = 0;

        foreach ($order as $name => $meta) {
            $aroma = ProductAroma::firstOrNew(['product_id' => $product->id, 'name' => $name]);
            if (! $aroma->exists) {
                $aroma->number = $nextNumber++;
                $this->report->aromas++;
            }

            $aroma->sort_order = ++$position;
            $aroma->note = $meta['note'];

            if (! $meta['file'] && $variantFiles !== [] && ! $singleAroma) {
                $this->report->note($folder, "aroma \"{$name}\" tidak punya foto varian; galeri memakai foto produk.");
            }

            if ($meta['file']) {
                $dest = $this->storagePath($product, $meta['file']['base']);
                if ($dims = $this->image($meta['file']['path'], $dest, $withImages)) {
                    $aroma->fill(['image' => $dest, 'image_width' => $dims['width'], 'image_height' => $dims['height']]);
                }
            }

            if (! $aroma->hasNotes()) {
                $notes = $this->aromaNotes[$folder][$name]
                    ?? ($singleAroma && $category === Category::FULL_SIZE ? $this->descriptions->notes($description) : []);
                foreach (['top', 'middle', 'base', 'general'] as $layer) {
                    $aroma->{"notes_{$layer}"} = $notes[$layer] ?? null;
                }
            }

            $aroma->save();
            $models[$name] = $aroma;
        }

        foreach (array_diff(array_keys($this->aromaNotes[$folder] ?? []), array_keys($models)) as $unknown) {
            $this->report->warn($folder, "notes-aroma.php berisi aroma \"{$unknown}\" yang tidak ada di ekspor.");
        }

        return $models;
    }

    /**
     * @param  array<string, ProductAroma>  $aromas
     */
    private function syncVariants(Product $product, string $folder, array $parsedRows, array $aromas): void
    {
        $kept = [];
        $seen = [];

        foreach ($parsedRows as $p) {
            $row = $p['row'];
            $aroma = $aromas[$p['aroma']];
            $size = $p['ml'] !== null ? $this->sizes[$this->mlKey($p['ml'])] : null;

            $combo = $aroma->id.'|'.($size?->id ?? '-').'|'.$p['variantNote'];
            if (isset($seen[$combo])) {
                $this->report->warn($folder, "aroma \"{$aroma->name}\" ukuran ".($size?->label ?? '-').' muncul dua kali; keduanya disimpan dengan SKU berbeda.');
            }
            $seen[$combo] = true;

            $variant = ProductVariant::firstOrNew(['shopee_variation_id' => $row['kode_variasi']]);
            $oldStock = $variant->exists ? $variant->stock : 0;

            if (! $variant->exists) {
                $variant->sku = ProductVariant::generateSku($product, $aroma, $size);
            }

            $stock = max(0, (int) $row['stok']);
            $variant->fill([
                'product_id' => $product->id,
                'product_aroma_id' => $aroma->id,
                'size_id' => $size?->id,
                'shopee_variation_name' => trim($row['aroma'].','.$row['ukuran'], ','),
                'price' => (int) $row['harga'],
                'weight_grams' => (int) $row['berat_gram'],
                'max_per_order' => $this->maxPurchase[$row['kode_variasi']] ?? null,
                'note' => $p['variantNote'],
                'is_active' => ! $this->isExpired($aroma->note),
            ]);
            $variant->stock = $stock;
            $variant->save();
            $this->report->variants++;

            if ($stock !== $oldStock) {
                $variant->stockMovements()->create([
                    'change' => $stock - $oldStock,
                    'stock_after' => $stock,
                    'reason' => StockReason::Impor,
                    'note' => 'Ekspor Informasi Penjualan Shopee',
                ]);
            }

            if (! $variant->is_active) {
                $this->report->note($folder, "{$variant->sku} ({$aroma->name}, {$aroma->note}) sudah lewat tanggal kedaluwarsa, tidak dijual.");
            }

            $kept[] = $variant->id;
        }

        $this->report->variantsDeactivated += ProductVariant::where('product_id', $product->id)
            ->whereNotIn('id', $kept)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    private function syncProductImages(Product $product, string $dir, InfoFile $info, bool $withImages): void
    {
        $names = array_values(array_filter($info->photos, fn ($n) => ! str_starts_with($n, 'varian-')));

        if ($names === []) {
            $names = collect(glob("{$dir}/*.{jpg,jpeg,png,webp}", GLOB_BRACE))
                ->map(fn ($f) => pathinfo($f, PATHINFO_FILENAME))
                ->reject(fn ($n) => str_starts_with($n, 'varian-'))
                ->sort()->values()->all();
        }

        $keep = [];
        foreach ($names as $i => $name) {
            $source = $this->findImage($dir, $name);
            if ($source === null) {
                continue;
            }

            $dest = $this->storagePath($product, $name);
            if ($dims = $this->image($source, $dest, $withImages)) {
                ProductImage::updateOrCreate(
                    ['product_id' => $product->id, 'path' => $dest],
                    ['width' => $dims['width'], 'height' => $dims['height'], 'sort_order' => $i, 'alt' => $i === 0 ? $product->name : null],
                );
                $keep[] = $dest;
            }
        }

        if ($keep !== []) {
            ProductImage::where('product_id', $product->id)->whereNotIn('path', $keep)->delete();
        }
    }

    /**
     * Mengubah foto ke WebP, atau tanpa konversi memakai file WebP yang sudah ada.
     *
     * @return array{width: int, height: int}|null
     */
    private function image(string $source, string $dest, bool $convert): ?array
    {
        $disk = Storage::disk('public');

        if ($convert) {
            $result = $this->converter->convert($source, $disk->path($dest));
            $result['converted'] ? $this->report->imagesConverted++ : $this->report->imagesSkipped++;

            return $result;
        }

        if ($disk->exists("{$dest}-800.webp")) {
            [$width, $height] = getimagesize($disk->path("{$dest}-800.webp"));

            return ['width' => $width, 'height' => $height];
        }

        return null;
    }

    /**
     * @return array<string, array{number: int, base: string, path: string}> slug aroma => file foto varian
     */
    private function variantImageFiles(string $dir): array
    {
        $files = [];
        foreach (glob("{$dir}/varian-*.{jpg,jpeg,png,webp}", GLOB_BRACE) as $path) {
            $base = pathinfo($path, PATHINFO_FILENAME);
            if (preg_match('/^varian-(\d+)-(.+)$/', $base, $m)) {
                $files[$m[2]] ??= ['number' => (int) $m[1], 'base' => $base, 'path' => $path];
            }
        }

        return $files;
    }

    /** Sama dengan slug(option, 40) di unduh_foto_shopee.py */
    private function photoSlug(string $text): string
    {
        $text = preg_replace('/\[[^\]]*\]/', '', $text);
        $text = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));

        return trim(substr($text, 0, 40), '-');
    }

    private function findImage(string $dir, string $name): ?string
    {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            if (is_file("{$dir}/{$name}.{$ext}")) {
                return "{$dir}/{$name}.{$ext}";
            }
        }

        return null;
    }

    private function storagePath(Product $product, string $name): string
    {
        return sprintf('produk/%04d/%s', $product->number, $name);
    }

    private function isExpired(?string $note): bool
    {
        if ($note === null || ! preg_match('/^Exp (\d{1,2})\/(\d{2})$/', $note, $m)) {
            return false;
        }

        return Carbon::create(2000 + (int) $m[2], (int) $m[1], 1)->endOfMonth()->isPast();
    }

    /** "MYKONOS CONQUER" menjadi "MYKONOS Conquer"; nama merek tetap seperti di daftar merek */
    private function tidyName(string $name, ?string $brand): string
    {
        if ($brand === null || mb_stripos($name, $brand) !== 0) {
            return $name;
        }

        $rest = trim(mb_substr($name, mb_strlen($brand)));
        // "ONIX .id" tetap menempel: ONIX.id
        $glue = str_starts_with($rest, '.') ? '' : ' ';

        return trim($brand.$glue.$this->softenCaps($rest));
    }

    /** Nama parfum tanpa merek, untuk listing full size yang hanya punya satu aroma */
    private function perfumeName(string $name, ?string $brand): string
    {
        if ($brand !== null && mb_stripos($name, $brand) === 0) {
            $rest = trim(mb_substr($name, mb_strlen($brand)));

            return $rest !== '' ? $rest : $name;
        }

        return $name;
    }

    /** Kata kapital 5 huruf atau lebih dijadikan huruf awal kapital; singkatan pendek (BNIB, SOI) tetap */
    private function softenCaps(string $text): string
    {
        return preg_replace_callback('/\b\p{Lu}{5,}\b/u', fn ($m) => mb_convert_case($m[0], MB_CASE_TITLE), $text);
    }

    private function uniqueSlug(string $name, Product $product): string
    {
        $slug = Str::slug($name) ?: 'produk';

        if ($product->slug && Str::startsWith($product->slug, $slug)) {
            return $product->slug;
        }

        $candidate = $slug;
        for ($i = 2; Product::where('slug', $candidate)->whereKeyNot($product->id)->exists(); $i++) {
            $candidate = "{$slug}-{$i}";
        }

        return $candidate;
    }

    private function mlKey(float $ml): string
    {
        return rtrim(rtrim(number_format($ml, 1, '.', ''), '0'), '.');
    }

    /**
     * @return array<string, int>
     */
    private function readMaxPurchase(string $basePath): array
    {
        $files = glob($basePath.'/ekspor/mass_update_sales_info_*.xlsx') ?: [];
        sort($files);

        if ($files === []) {
            $this->report->warn('ekspor', 'file Informasi Penjualan (xlsx) tidak ada; batas beli per SKU tidak diimpor.');

            return [];
        }

        return $this->salesInfo->maxPurchase(end($files));
    }

    /**
     * @return array<string, list<array<string, string>>> folder => baris CSV
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '\\');
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $groups = [];

        while (($line = fgetcsv($handle, escape: '\\')) !== false) {
            if ($line === [null]) {
                continue;
            }
            $row = array_combine($header, $line);
            $groups[$row['folder']][] = $row;
        }

        fclose($handle);

        return $groups;
    }
}
