<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Hadiah otomatis (aturan bisnis 6). Ambang dihitung dari subtotal produk yang memenuhi syarat
 * (jenis dan/atau daftar produk), satu hadiah per pesanan: tidak berlaku kelipatan.
 */
class GiftRule extends Model
{
    protected $fillable = ['name', 'description', 'gift_variant_id', 'min_subtotal', 'category_id', 'is_active'];

    protected function casts(): array
    {
        return [
            'min_subtotal' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function giftVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'gift_variant_id');
    }

    /** Kosong berarti semua jenis */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Kosong berarti berlaku untuk semua produk */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** SKU ini ikut dihitung untuk aturan ini */
    public function appliesTo(ProductVariant $variant): bool
    {
        return ($this->category_id === null || (int) $variant->product->category_id === (int) $this->category_id)
            && ($this->products->isEmpty() || $this->products->contains('id', $variant->product_id));
    }

    /**
     * Subtotal barang yang ikut dihitung (harga yang dibayar, termasuk harga flash sale).
     *
     * @param  Collection<int, CartItem>  $items
     */
    public function eligibleSubtotal(Collection $items): int
    {
        return $items->filter(fn (CartItem $i) => $this->appliesTo($i->variant))
            ->sum(fn (CartItem $i) => $i->variant->currentPrice() * $i->quantity);
    }

    /** "hadiah random", dari nama aturan tanpa keterangan dalam kurung */
    public function giftLabel(): string
    {
        return Str::lower(preg_replace('/\s*\(.*\)$/', '', $this->name));
    }

    /** "Belanja decant" bila dibatasi jenis, "Belanja" bila semua jenis */
    public function spendLabel(): string
    {
        return 'Belanja'.($this->category ? ' '.Str::lower($this->category->name) : '');
    }
}
