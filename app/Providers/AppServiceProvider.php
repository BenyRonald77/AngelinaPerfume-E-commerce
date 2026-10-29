<?php

namespace App\Providers;

use App\Services\CartService;
use App\Shipping\ManualRateProvider;
use App\Shipping\ShippingProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Driver ongkir; ganti di sini bila pindah ke API kurir
        $this->app->bind(ShippingProvider::class, ManualRateProvider::class);
        $this->app->scoped(CartService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Keranjang tamu digabung ke akun saat masuk (F3)
        Event::listen(Login::class, fn (Login $event) => app(CartService::class)->mergeGuestCart($event->user));
    }
}
