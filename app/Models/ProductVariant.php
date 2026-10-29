<?php

namespace App\Models;

use App\Enums\StockReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'product_aroma_id', 'size_id', 'sku', 'shopee_variation_id', 'shopee_variation_name',
        'price', 'weight_grams', 'stock', 'max_per_order', 'note', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'weight_grams' => 'integer',
            'stock' => 'integer',
            'max_per_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function aroma(): BelongsTo
    {
        return $this->belongsTo(ProductAroma::class, 'product_aroma_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** AP-0012-03-2ML; tanpa ukuran (aksesori, hadiah) AP-0012-03; bentrok diberi akhiran -2 */
    public static function generateSku(Product $product, ProductAroma $aroma, ?Size $size): string
    {
        $base = $product->skuPrefix().sprintf('-%02d', $aroma->number).($size ? '-'.$size->skuSuffix() : '');
        $sku = $base;

        for ($i = 2; self::where('sku', $sku)->exists(); $i++) {
            $sku = "{$base}-{$i}";
        }

        return $sku;
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->stock > 0;
    }

    /**
     * Mengubah stok dan mencatatnya di riwayat. Stok tidak boleh minus.
     */
    public function adjustStock(int $change, StockReason $reason, ?User $by = null, ?string $note = null, ?int $orderId = null): StockMovement
    {
        return DB::transaction(function () use ($change, $reason, $by, $note, $orderId) {
            $variant = self::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $newStock = $variant->stock + $change;

            if ($newStock < 0) {
                throw new InvalidArgumentException("Stok {$variant->sku} tidak cukup: tersisa {$variant->stock}.");
            }

            $variant->forceFill(['stock' => $newStock])->save();
            $this->stock = $newStock;

            return $variant->stockMovements()->create([
                'change' => $change,
                'stock_after' => $newStock,
                'reason' => $reason,
                'user_id' => $by?->getKey(),
                'order_id' => $orderId,
                'note' => $note,
            ]);
        });
    }
}
