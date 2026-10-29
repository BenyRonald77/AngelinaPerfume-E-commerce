<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'label', 'recipient_name', 'phone', 'address_line', 'district', 'city', 'province',
        'postal_code', 'area_code', 'is_primary',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Satu baris untuk ringkasan: "Jl. Mawar 1, Depok, Sleman, DI Yogyakarta 55281" */
    public function oneLine(): string
    {
        return "{$this->address_line}, {$this->district}, {$this->city}, {$this->province} {$this->postal_code}";
    }

    /** Salinan untuk pesanan; perubahan alamat nanti tidak mengubah pesanan lama */
    public function snapshot(): array
    {
        return $this->only(['recipient_name', 'phone', 'address_line', 'district', 'city', 'province', 'postal_code']);
    }
}
