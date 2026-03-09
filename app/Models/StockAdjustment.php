<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    protected $fillable = [
        'adjustment_number',
        'product_variant_id',
        'type',
        'quantity',
        'reason',
        'note',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    // ─── Boot ────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->adjustment_number)) {
                $model->adjustment_number = self::generateNumber();
            }
        });
    }

    /**
     * Generate unique adjustment number: ADJ-YYYY-NNNN
     */
    public static function generateNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "ADJ-{$year}-";

        $last = self::where('adjustment_number', 'like', "{$prefix}%")
            ->orderBy('adjustment_number', 'desc')
            ->value('adjustment_number');

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = ((int) end($parts)) + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // ─── Relationships ───────────────────────────────

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Computed ────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'addition' ? __('Addition') : __('Subtraction');
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return $this->type === 'addition' ? 'badge-success' : 'badge-error';
    }
}
