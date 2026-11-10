<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Dijalankan oleh `php artisan schedule:run` (cron di hosting) atau `schedule:work` (container di VPS).
// Schedule::call berjalan di proses yang sama; Schedule::command butuh proc_open yang sering dimatikan di shared hosting.
// everyMinute = setiap kali cron memanggil schedule:run. Cron DomaiNesia paling sering tiap 6 menit, jadi jadwal
// "tiap 5 menit" hanya akan bertemu di menit 0 dan 30; dengan everyMinute pengecekan ikut irama cron.
Schedule::call(fn () => Artisan::call('toko:batalkan-kedaluwarsa'))
    ->name('batalkan-pesanan-kedaluwarsa')
    ->everyMinute()
    ->withoutOverlapping();
