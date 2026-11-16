<?php

namespace App\Models;

use App\Support\FlashPrice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleItem extends Model
{
    protected $fillable = ['flash_sale_id', 'product_variant_id', 'sale_price', 'sort_order'];

    protected function casts(): array
    {
        return ['sale_price' => 'integer', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => FlashPrice::flush());
        static::deleted(fn () => FlashPrice::flush());
    }

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Potongan dalam persen dari harga normal SKU, dibulatkan ke bawah supaya tidak melebih-lebihkan */
    public function discountPercent(): int
    {
        $normal = $this->variant?->price ?? 0;

        // Hitungan bilangan bulat: 1 - 80000/100000 di floating point menjadi 19,999...%
        return $normal > 0 ? intdiv(($normal - $this->sale_price) * 100, $normal) : 0;
    }
}
