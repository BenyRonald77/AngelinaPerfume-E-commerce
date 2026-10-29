<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class Waktu
{
    /** "Kamis, 30 Oktober 2026, 14.05 WIB" (zona waktu aplikasi: Asia/Jakarta) */
    public static function lengkap(CarbonInterface $time): string
    {
        return $time->copy()->setTimezone(config('app.timezone'))->translatedFormat('l, j F Y, H.i').' WIB';
    }

    /** "30 Okt 2026" */
    public static function tanggal(CarbonInterface $time): string
    {
        return $time->copy()->setTimezone(config('app.timezone'))->translatedFormat('j M Y');
    }
}
