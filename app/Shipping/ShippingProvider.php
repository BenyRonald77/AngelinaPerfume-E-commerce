<?php

namespace App\Shipping;

/**
 * Sumber tarif ongkir. Implementasi awal: tabel manual per provinsi (ManualRateProvider).
 * Penggantinya (Biteship, API J&T/JNE) cukup mengimplementasikan interface ini.
 */
interface ShippingProvider
{
    /**
     * @return list<ShippingOption> kosong bila tujuan belum bisa dikirim
     */
    public function options(string $province, int $weightGrams): array;
}
