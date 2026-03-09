<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'barcode',
        'purchase_price',
        'retail_price',
        'online_price',
        'wholesale_price',
        'weight',
        'is_active',
        'reserved_stock',
        'sort_order',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'online_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'reserved_stock' => 'decimal:4',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ─── Relationships ───────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_variant_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class, 'product_variant_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'product_variant_id');
    }

    // ─── Computed ────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return "{$this->product->name} — {$this->name}";
    }

    public function getCurrentStockAttribute(): float
    {
        $in = $this->stockMovements()->where('direction', 'in')->sum('quantity');
        $out = $this->stockMovements()->where('direction', 'out')->sum('quantity');
        return (float) ($in - $out);
    }

    public function getAvailableStockAttribute(): float
    {
        return max(0, $this->current_stock - (float) $this->reserved_stock);
    }
}
