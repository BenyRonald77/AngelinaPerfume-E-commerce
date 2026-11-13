<?php

namespace App\Models;

use App\Enums\BrandOrigin;
use App\Support\BrandMenu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'origin', 'logo'];

    protected function casts(): array
    {
        return ['origin' => BrandOrigin::class];
    }

    protected static function booted(): void
    {
        // Menu merek di header memakai cache; perubahan nama/asal langsung terlihat
        static::saved(fn () => BrandMenu::forget());
        static::deleted(fn () => BrandMenu::forget());
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Logo resmi merek: unggahan admin didahulukan, lalu logo bawaan di public/images/merek/<slug>.webp
     * (diambil dari situs resmi merek atau direktori brand Female Daily). Null bila belum ada.
     */
    public function logoUrl(): ?string
    {
        if ($this->logo) {
            return Storage::disk('public')->url($this->logo);
        }

        $bundled = "images/merek/{$this->slug}.webp";

        return is_file(public_path($bundled)) ? asset($bundled) : null;
    }
}
