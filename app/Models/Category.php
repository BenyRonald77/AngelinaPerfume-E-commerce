<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public const DECANT = 'decant';

    public const FULL_SIZE = 'full-size';

    public const AKSESORI = 'aksesori';

    // Produk hadiah (GWP): tidak tampil di katalog
    public const HADIAH = 'hadiah';

    public const DEFAULTS = [
        self::DECANT => 'Decant',
        self::FULL_SIZE => 'Full Size',
        self::AKSESORI => 'Aksesori',
        self::HADIAH => 'Hadiah',
    ];

    protected $fillable = ['name', 'slug', 'sort_order'];

    public static function ensureDefaults(): void
    {
        foreach (array_keys(self::DEFAULTS) as $i => $slug) {
            self::firstOrCreate(['slug' => $slug], ['name' => self::DEFAULTS[$slug], 'sort_order' => $i + 1]);
        }
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
