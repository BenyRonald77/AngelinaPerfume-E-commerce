<?php

namespace App\Models;

use App\Enums\BottleType;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    public $timestamps = false;

    protected $fillable = ['label', 'ml', 'bottle_type', 'sort_order'];

    protected function casts(): array
    {
        return [
            'ml' => 'float',
            'bottle_type' => BottleType::class,
        ];
    }

    /** Bagian ml untuk kode SKU: 0.5ML, 2ML, 100ML */
    public function skuSuffix(): string
    {
        $ml = $this->ml == floor($this->ml) ? (string) (int) $this->ml : (string) $this->ml;

        return $ml.'ML';
    }
}
