<?php

namespace App\Console\Commands;

use App\Models\FlashSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Rupiah;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Contoh flash sale untuk tanggal kembar berikutnya (permintaan klien: "kasih dulu contohnya").
 * Produk terlaris, potongan 10-25% dibulatkan ke Rp500. Harga contoh ini BENAR-BENAR berlaku
 * pada tanggalnya bila tidak diubah, jadi pemilik toko perlu memeriksanya di admin (Promo → Flash sale).
 */
class CreateExampleFlashSale extends Command
{
    protected $signature = 'toko:contoh-flash-sale {--jumlah=10 : Jumlah produk}';

    protected $description = 'Membuat contoh flash sale tanggal kembar berikutnya dari produk terlaris';

    private const DISCOUNTS = [20, 15, 25, 10];

    public function handle(): int
    {
        $date = CarbonImmutable::parse(array_key_first(FlashSale::twinDates(1)));
        $name = "Flash Sale {$date->month}.{$date->day}";

        if (FlashSale::whereDate('starts_at', $date)->exists()) {
            $this->info("Sudah ada flash sale pada {$date->toDateString()}; tidak membuat contoh baru.");

            return self::SUCCESS;
        }

        $products = Product::visible()
            ->has('cover')
            ->where('sold_count', '>', 0)
            ->orderByDesc('sold_count')
            ->with(['variants' => fn ($q) => $q->where('is_active', true)->where('stock', '>', 0)->with('size')])
            ->limit(40)
            ->get();

        $items = [];
        foreach ($products as $product) {
            $variant = $this->pick($product);
            if (! $variant) {
                continue;
            }
            $percent = self::DISCOUNTS[count($items) % count(self::DISCOUNTS)];
            $salePrice = (int) (floor($variant->price * (100 - $percent) / 100 / 500) * 500);
            if ($salePrice <= 0 || $salePrice >= $variant->price) {
                continue;
            }
            $items[] = ['variant' => $variant, 'sale_price' => $salePrice];
            if (count($items) >= (int) $this->option('jumlah')) {
                break;
            }
        }

        if ($items === []) {
            $this->warn('Tidak ada produk terlaris yang punya stok; contoh tidak dibuat.');

            return self::FAILURE;
        }

        $sale = DB::transaction(function () use ($name, $date, $items) {
            $sale = FlashSale::create([
                'name' => $name,
                'starts_at' => $date->startOfDay(),
                'ends_at' => $date->setTime(23, 59),
                'is_active' => true,
                'show_teaser' => true,
            ]);
            foreach ($items as $i => $item) {
                $sale->items()->create(['product_variant_id' => $item['variant']->id, 'sale_price' => $item['sale_price'], 'sort_order' => $i + 1]);
            }

            return $sale;
        });

        $this->table(['SKU', 'Produk', 'Normal', 'Flash'], collect($items)->map(fn ($item) => [
            $item['variant']->sku, $item['variant']->product->name, Rupiah::format($item['variant']->price), Rupiah::format($item['sale_price']),
        ]));
        $this->info("{$sale->name} dibuat: {$sale->starts_at->format('d-m-Y H.i')} sampai {$sale->ends_at->format('H.i')}, ".count($items).' produk.');

        return self::SUCCESS;
    }

    /** Ukuran yang paling sering dibeli: decant 5-10 ml atau travel 15 ml; selain itu yang termurah */
    private function pick(Product $product): ?ProductVariant
    {
        $variants = $product->variants->filter(fn (ProductVariant $v) => $v->price >= 10000);

        return $variants->filter(fn (ProductVariant $v) => $v->size && $v->size->ml >= 5 && $v->size->ml <= 15)->sortByDesc(fn ($v) => $v->size->ml)->first()
            ?? $variants->sortBy('price')->first();
    }
}
