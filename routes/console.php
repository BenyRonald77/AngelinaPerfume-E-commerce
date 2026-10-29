<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Dijalankan oleh `php artisan schedule:run` (cron di hosting) atau `schedule:work` (container di VPS).
// Schedule::call berjalan di proses yang sama; Schedule::command butuh proc_open yang sering dimatikan di shared hosting.
Schedule::call(fn () => Artisan::call('toko:batalkan-kedaluwarsa'))
    ->name('batalkan-pesanan-kedaluwarsa')
    ->everyFiveMinutes()
    ->withoutOverlapping();
