<?php

namespace App\Support;

final class Rupiah
{
    /** 125000 menjadi "Rp125.000" */
    public static function format(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }

    /** 0.5 menjadi "0,5" dan 10.0 menjadi "10" */
    public static function ml(float $ml): string
    {
        return rtrim(rtrim(number_format($ml, 1, ',', ''), '0'), ',');
    }
}
