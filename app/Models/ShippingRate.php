<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    protected $fillable = ['province', 'courier', 'first_kg_price', 'next_kg_price', 'estimate', 'is_active'];

    protected function casts(): array
    {
        return [
            'first_kg_price' => 'integer',
            'next_kg_price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** Tarif kilo pertama + kilo berikutnya; berat dibulatkan ke atas per kg */
    public function costFor(int $weightGrams): int
    {
        $kg = max(1, (int) ceil($weightGrams / 1000));

        return $this->first_kg_price + ($kg - 1) * $this->next_kg_price;
    }
}
