<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'code', 'user_id', 'recipient_name', 'phone', 'address_line', 'district', 'city', 'province', 'postal_code',
        'subtotal', 'packaging_fee', 'shipping_cost', 'total', 'total_weight_grams', 'status', 'payment_due_at',
        'paid_at', 'processed_at', 'shipped_at', 'completed_at', 'cancelled_at', 'cancel_reason',
        'customer_note', 'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'packaging_fee' => 'integer',
            'shipping_cost' => 'integer',
            'total' => 'integer',
            'total_weight_grams' => 'integer',
            'payment_due_at' => 'datetime',
            'paid_at' => 'datetime',
            'processed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /** Kode pesanan yang mudah dibacakan pembeli, tanpa huruf yang mirip angka (0/O, 1/I): AP-261029-7K3Q */
    public static function newCode(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        do {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = 'AP-'.now()->format('ymd').'-'.$suffix;
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class)->latestOfMany();
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === OrderStatus::MenungguPembayaran;
    }

    public function addressOneLine(): string
    {
        return "{$this->address_line}, {$this->district}, {$this->city}, {$this->province} {$this->postal_code}";
    }
}
