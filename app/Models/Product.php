<?php

namespace App\Models;

use App\Enums\Gender;
use App\Support\Rupiah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'number', 'name', 'slug', 'brand_id', 'category_id', 'concentration', 'gender',
        'description', 'shopee_description', 'is_visible', 'sold_count', 'source_folder',
    ];

    // Jumlah produk teratas (menurut penjualan Shopee) yang diberi label "Terlaris"
    public const BESTSELLER_COUNT = 10;

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'is_visible' => 'boolean',
            'sold_count' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function shopeeListings(): HasMany
    {
        return $this->hasMany(ProductShopeeListing::class);
    }

    public function aromas(): HasMany
    {
        return $this->hasMany(ProductAroma::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function cover(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany('sort_order', 'min');
    }

    /** Produk yang tampil di katalog: status tampil dan bukan hadiah */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)
            ->whereHas('category', fn (Builder $q) => $q->where('slug', '!=', Category::HADIAH));
    }

    public function isDecant(): bool
    {
        return $this->category?->slug === Category::DECANT;
    }

    public function skuPrefix(): string
    {
        return sprintf('AP-%04d', $this->number);
    }

    /**
     * Baris keterangan kartu, mis. "Decant · 4 aroma · 0,5–5 ml".
     * Butuh kolom agregat dari App\Queries\ProductCards.
     */
    public function cardMeta(): string
    {
        $parts = [$this->category->name];

        if ($this->aromas_count > 1) {
            $parts[] = $this->category->slug === Category::AKSESORI ? "{$this->aromas_count} pilihan" : "{$this->aromas_count} aroma";
        }

        if ($this->min_ml !== null) {
            $min = Rupiah::ml((float) $this->min_ml);
            $max = Rupiah::ml((float) $this->max_ml);
            // Spasi tak terputus: "ml" tidak terpisah dari angkanya saat teks turun baris
            $parts[] = $min === $max ? "{$min}\u{00A0}ml" : "{$min}–{$max}\u{00A0}ml";
        }

        return implode(' · ', $parts);
    }

    /** Harga kartu: "mulai" hanya bila ada lebih dari satu SKU */
    public function cardPrice(): ?array
    {
        if ($this->min_available_price === null) {
            return null;
        }

        return [
            'prefix' => $this->variant_count > 1 ? 'mulai' : null,
            'amount' => Rupiah::format((int) $this->min_available_price),
        ];
    }
}
