<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'slug',
        'price',
        'price_cents',
        'currency',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'price_cents' => 'integer',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function cover(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')
            ->where('collection_name', 'cover')
            ->where('file_type', 'image');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')
            ->where('collection_name', 'documents')
            ->where('file_type', 'document')
            ->orderBy('sort_order');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->price_cents / 100,
            set: fn ($value) => ['price_cents' => (int) round(((float) $value) * 100)],
        );
    }

    public function title(string $locale = 'en'): string
    {
        return $this->translations->firstWhere('locale', $locale)?->title
            ?? $this->translations->first()?->title
            ?? 'Untitled product';
    }
}
