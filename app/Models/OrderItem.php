<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_variant_id', 'sku', 'product_name', 'aroma_name', 'size_label',
        'price', 'quantity', 'weight_grams', 'is_gift',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quantity' => 'integer',
            'weight_grams' => 'integer',
            'is_gift' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function lineTotal(): int
    {
        return $this->price * $this->quantity;
    }
}
