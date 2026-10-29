<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\ProductVariants\ProductVariantResource;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Support\Rupiah;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Pekerjaan admin hari ini: apa yang perlu dibayar, dikemas, dan diisi ulang stoknya */
class OrderWorkload extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $paid = [OrderStatus::Lunas, OrderStatus::Diproses, OrderStatus::Dikirim, OrderStatus::Selesai];
        $toPack = Order::whereIn('status', [OrderStatus::Lunas, OrderStatus::Diproses])->count();
        $awaiting = Order::where('status', OrderStatus::MenungguPembayaran)->count();
        $monthSales = (int) Order::whereIn('status', $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('total');
        $lowStock = ProductVariant::where('is_active', true)
            ->whereBetween('stock', [1, Setting::int('low_stock_threshold')])
            ->count();

        return [
            Stat::make('Perlu dikemas', $toPack)
                ->description('Lunas dan diproses')
                ->color($toPack ? 'warning' : 'gray')
                ->url(OrderResource::getUrl('index', ['tab' => 'kemas'])),
            Stat::make('Menunggu pembayaran', $awaiting)
                ->description('Batal otomatis setelah '.Setting::int('payment_window_hours').' jam')
                ->url(OrderResource::getUrl('index', ['tab' => 'bayar'])),
            Stat::make('Penjualan bulan ini', Rupiah::format($monthSales))
                ->description('Pesanan dibayar sejak '.now()->startOfMonth()->translatedFormat('j F')),
            Stat::make('SKU stok menipis', $lowStock)
                ->description('Stok 1–'.Setting::int('low_stock_threshold').' botol')
                ->url(ProductVariantResource::getUrl('index')),
        ];
    }
}
