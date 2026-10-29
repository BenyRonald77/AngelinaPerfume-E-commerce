<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = ['order_id', 'courier', 'service', 'cost', 'tracking_number', 'tracking_status', 'shipped_at'];

    protected function casts(): array
    {
        return [
            'cost' => 'integer',
            'shipped_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Tautan lacak umum per kurir. Kurir yang tidak dikenal memakai cekresi.com,
     * yang bisa mencari resi lintas kurir.
     */
    public function trackingUrl(): ?string
    {
        if (blank($this->tracking_number)) {
            return null;
        }

        $resi = urlencode($this->tracking_number);

        return match (true) {
            str_contains(strtolower($this->courier), 'j&t'), str_contains(strtolower($this->courier), 'jnt') => "https://jet.co.id/track?awb={$resi}",
            str_contains(strtolower($this->courier), 'jne') => "https://www.jne.co.id/tracking-package?resi={$resi}",
            default => "https://cekresi.com/?noresi={$resi}",
        };
    }
}
