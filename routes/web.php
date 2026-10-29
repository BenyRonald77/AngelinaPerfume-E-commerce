<?php

use App\Http\Controllers\AccountOrderController;
use App\Http\Controllers\Admin\PrintOrderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\XenditWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/produk', [ProductController::class, 'index'])->name('produk.index');
Route::get('/produk/{product:slug}', [ProductController::class, 'show'])->name('produk.show');

// Keranjang boleh diisi tamu (F3); checkout wajib akun terverifikasi (aturan bisnis 5)
Route::get('/keranjang', [CartController::class, 'index'])->name('keranjang');
Route::post('/keranjang', [CartController::class, 'store'])->name('keranjang.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/checkout', 'checkout')->name('checkout');
    Route::get('/pesanan/{order}/bayar', [PaymentController::class, 'show'])->name('pesanan.bayar');
    Route::post('/pesanan/{order}/bayar', [PaymentController::class, 'pay'])->name('pesanan.bayar.mulai');

    Route::get('/akun/pesanan', [AccountOrderController::class, 'index'])->name('akun.pesanan');
    Route::get('/akun/pesanan/{order}', [AccountOrderController::class, 'show'])->name('akun.pesanan.show');
    Route::post('/akun/pesanan/{order}/diterima', [AccountOrderController::class, 'received'])->name('akun.pesanan.diterima');
    Route::post('/akun/pesanan/{order}/beli-lagi', [AccountOrderController::class, 'reorder'])->name('akun.pesanan.beli-lagi');
    Route::view('/akun/alamat', 'akun.alamat')->name('akun.alamat');
});

Route::middleware('auth')->group(function () {
    Route::get('/akun', [ProfileController::class, 'edit'])->name('akun');
    Route::patch('/akun', [ProfileController::class, 'update'])->name('profile.update');

    // Label pengiriman untuk admin (tombol "Cetak label" di panel)
    Route::get('/admin/cetak/pesanan/{order}', PrintOrderController::class)->name('admin.pesanan.cetak');
});

Route::post('/webhooks/xendit', XenditWebhookController::class)->name('webhooks.xendit');

require __DIR__.'/auth.php';
