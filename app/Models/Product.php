<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity, Searchable, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'category_id',
        'brand_id',
        'base_unit_id',
        'product_type',
        'description',
        'min_stock',
        'is_active',
        'is_featured',
        'show_in_ecommerce',
        'barcode',
        'tax_rate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'show_in_ecommerce' => 'boolean',
        'min_stock' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = self::generateSku();
            }
        });
    }

    public static function generateSku(): string
    {
        $prefix = 'PRD';
        $last = self::withTrashed()->max('id') ?? 0;

        return sprintf('%s-%05d', $prefix, $last + 1);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product-images')
            ->registerMediaConversions(function (?Media $media = null) {
                $this->addMediaConversion('thumb')
                    ->width(300)
                    ->height(300)
                    ->quality(80)
                    ->nonQueued();

                $this->addMediaConversion('medium')
                    ->width(600)
                    ->height(600)
                    ->quality(85)
                    ->nonQueued();
            });
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('product-images');

        if ($media) {
            $path = $media->getPath('thumb');
            if (file_exists($path)) {
                return $media->getUrl('thumb');
            }
        }

        return null;
    }

    // ─── Relationships ───────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function unitConversions(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    /**
     * Alias for unitConversions (used by CartService & shop components).
     */
    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    // ─── Scout / Search ─────────────────────────────────

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'is_active' => $this->is_active ? 1 : 0,
            'category_id' => $this->category_id,
        ];
    }

    public function searchableAs(): string
    {
        return 'products';
    }

    // ─── Scopes ──────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeEcommerce($query)
    {
        return $query->where('show_in_ecommerce', true);
    }

    // ─── Computed Attributes ─────────────────────────────

    public function getDefaultRetailPriceAttribute(): float
    {
        return $this->variants()->first()?->retail_price ?? 0;
    }

    public function getVariantCountAttribute(): int
    {
        return $this->variants()->count();
    }
}
