<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id',
    'code',
    'slug',
    'title',
    'author',
    'publisher',
    'isbn',
    'description',
    'specifications',
    'stock',
    'unit_price',
    'wholesale_price',
    'wholesale_minimum',
    'is_published',
    'is_featured',
    'is_bestseller',
    'published_at',
    'meta_title',
    'meta_description',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->whereHas('category', fn(Builder $query) => $query->where('is_published', true));
    }

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'stock' => 'integer',
            'unit_price' => 'integer',
            'wholesale_price' => 'integer',
            'wholesale_minimum' => 'integer',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
