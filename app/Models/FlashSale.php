<?php

namespace App\Models;

use App\Support\FlashPrice;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Flash sale tanggal kembar (1.1, 2.2, ... 12.12). Harga flash berlaku di keranjang dan checkout
 * hanya selama aktif dan di antara starts_at dan ends_at (lihat App\Support\FlashPrice).
 */
class FlashSale extends Model
{
    protected $fillable = ['name', 'starts_at', 'ends_at', 'is_active', 'show_teaser'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'show_teaser' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => FlashPrice::flush());
        static::deleted(fn () => FlashPrice::flush());
    }

    public function items(): HasMany
    {
        return $this->hasMany(FlashSaleItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Sedang berlangsung: aktif dan sekarang di antara mulai dan berakhir */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }

    /** Yang tampil di Beranda: sedang berlangsung, atau akan datang dengan hitung mundur */
    public static function forHome(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->where('ends_at', '>', now())
            ->where(fn (Builder $q) => $q->where('starts_at', '<=', now())->orWhere('show_teaser', true))
            ->whereHas('items')
            ->orderBy('starts_at')
            ->first();
    }

    public function isRunning(): bool
    {
        return $this->is_active && $this->starts_at->isPast() && $this->ends_at->isFuture();
    }

    public function status(): string
    {
        return match (true) {
            ! $this->is_active => 'Nonaktif',
            $this->ends_at->isPast() => 'Selesai',
            $this->starts_at->isFuture() => 'Akan datang',
            default => 'Berlangsung',
        };
    }

    /**
     * Tanggal kembar berikutnya mulai hari ini, untuk isian cepat di admin.
     *
     * @return array<string, string> 'Y-m-d' => '12.12 · Sabtu, 12 Desember 2026'
     */
    public static function twinDates(int $count = 12): array
    {
        $today = CarbonImmutable::today();
        $dates = [];

        for ($year = $today->year; count($dates) < $count; $year++) {
            foreach (range(1, 12) as $month) {
                $date = CarbonImmutable::create($year, $month, $month);
                if ($date->lt($today) || count($dates) >= $count) {
                    continue;
                }
                $dates[$date->toDateString()] = "{$month}.{$month} · ".$date->locale('id')->translatedFormat('l, j F Y');
            }
        }

        return $dates;
    }
}
