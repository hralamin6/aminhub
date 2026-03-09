<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_variant_id',
        'type',
        'direction',
        'quantity',
        'unit_id',
        'original_quantity',
        'reference_type',
        'reference_id',
        'batch_id',
        'note',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'original_quantity' => 'decimal:4',
    ];

    // ─── Relationships ───────────────────────────────

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeStockIn($query)
    {
        return $query->where('direction', 'in');
    }

    public function scopeStockOut($query)
    {
        return $query->where('direction', 'out');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }

    public function scopeInDateRange($query, $from, $to)
    {
        return $query->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to));
    }

    // ─── Computed ────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'purchase' => __('Purchase'),
            'sale' => __('Sale'),
            'adjustment' => __('Adjustment'),
            'return_in' => __('Return In'),
            'return_out' => __('Return Out'),
            'transfer' => __('Transfer'),
            default => ucfirst($this->type),
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            'purchase' => 'badge-success',
            'sale' => 'badge-info',
            'adjustment' => 'badge-warning',
            'return_in' => 'badge-primary',
            'return_out' => 'badge-error',
            'transfer' => 'badge-secondary',
            default => 'badge-ghost',
        };
    }

    public function getDirectionIconAttribute(): string
    {
        return $this->direction === 'in' ? 'o-arrow-down-tray' : 'o-arrow-up-tray';
    }
}
