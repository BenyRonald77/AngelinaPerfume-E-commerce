<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    // Nilai awal; admin bisa mengubahnya di panel
    public const DEFAULTS = [
        'packaging_fee' => '1700',
        'packaging_weight_grams' => '20',
        'low_stock_threshold' => '5',
        'payment_window_hours' => '24',
    ];

    /**
     * Pop-up promo di tepi kiri (contoh SOCO dari klien). Isi awal sengaja "segera hadir": situs sudah live
     * dan checkout belum punya voucher, jadi jangan menjanjikan potongan yang belum ada (aturan bisnis 9).
     */
    public const PROMO_DEFAULTS = [
        'promo_aktif' => '1',
        'promo_label' => 'Promo untukmu',
        'promo_judul' => 'Voucher spesial segera hadir',
        'promo_teks' => 'Nantikan voucher dan promo dari Angelia Perfume. Sambil menunggu, coba aroma favoritmu mulai 0,5 ml.',
        'promo_catatan' => '*S&K berlaku',
        'promo_tombol' => 'Lihat katalog',
        'promo_tautan' => '/produk',
    ];

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = Cache::rememberForever('settings', fn () => self::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? self::DEFAULTS[$key] ?? self::PROMO_DEFAULTS[$key] ?? null;
    }

    public static function put(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings');
    }

    public static function int(string $key): int
    {
        return (int) self::get($key);
    }
}
