<?php

namespace App\Shipping;

use App\Models\ShippingRate;

/** Tarif dari tabel shipping_rates yang diisi admin (kilo pertama + kilo berikutnya) */
class ManualRateProvider implements ShippingProvider
{
    public function options(string $province, int $weightGrams): array
    {
        return ShippingRate::where('province', $province)
            ->where('is_active', true)
            ->orderBy('first_kg_price')
            ->get()
            ->map(fn (ShippingRate $rate) => new ShippingOption(
                key: 'manual-'.$rate->id,
                courier: $rate->courier,
                service: null,
                cost: $rate->costFor($weightGrams),
                estimate: $rate->estimate,
            ))
            ->all();
    }
}
