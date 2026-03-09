<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_variant_id',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'initial_quantity',
        'note',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'initial_quantity' => 'decimal:4',
    ];

    // ─── Relationships ───────────────────────────────

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now());
    }

    // ─── Computed ────────────────────────────────────

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if (! $this->expiry_date) return false;
        return $this->expiry_date->isBetween(now(), now()->addDays(30));
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date) return null;
        return (int) now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Current stock in this batch = sum(in) - sum(out)
     */
    public function getCurrentStockAttribute(): float
    {
        $in = $this->stockMovements()->where('direction', 'in')->sum('quantity');
        $out = $this->stockMovements()->where('direction', 'out')->sum('quantity');
        return (float) ($in - $out);
    }
}
