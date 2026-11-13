<?php

namespace App\Models;

use App\Enums\BrandOrigin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'origin', 'logo'];

    protected function casts(): array
    {
        return ['origin' => BrandOrigin::class];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
