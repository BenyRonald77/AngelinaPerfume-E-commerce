<?php

namespace App\Models;

use App\Enums\StockReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = ['product_variant_id', 'change', 'stock_after', 'reason', 'order_id', 'user_id', 'note'];

    protected function casts(): array
    {
        return [
            'change' => 'integer',
            'stock_after' => 'integer',
            'reason' => StockReason::class,
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
