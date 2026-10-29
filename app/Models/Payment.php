<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const EXPIRED = 'expired';

    protected $fillable = ['order_id', 'xendit_invoice_id', 'invoice_url', 'method', 'amount', 'status', 'paid_at', 'note'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
