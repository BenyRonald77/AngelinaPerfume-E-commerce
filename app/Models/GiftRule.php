<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GiftRule extends Model
{
    protected $fillable = ['name', 'gift_variant_id', 'min_subtotal', 'is_active'];

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

    /** Kosong berarti berlaku untuk semua produk */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
