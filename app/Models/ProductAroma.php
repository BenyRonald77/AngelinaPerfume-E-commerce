<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ProductAroma extends Model
{
    protected $fillable = [
        'product_id', 'number', 'name', 'image', 'image_width', 'image_height',
        'notes_top', 'notes_middle', 'notes_base', 'notes_general', 'note', 'sort_order',
    ];

    public const NOTE_FIELDS = ['notes_top', 'notes_middle', 'notes_base', 'notes_general'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function hasNotes(): bool
    {
        return collect(self::NOTE_FIELDS)->contains(fn (string $field) => filled($this->{$field}));
    }

    public function imageUrl(int $width = 800): ?string
    {
        return $this->image ? Storage::disk('public')->url("{$this->image}-{$width}.webp") : null;
    }
}
