<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{

    protected $fillable = [
        'invoice_number',
        'provider_id',
        'purchase_date',
        'subtotal',
        'discount',
        'tax',
        'shipping_cost',
        'grand_total',
        'paid_amount',
        'due_amount',
        'payment_status',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    // ─── Boot ────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->invoice_number)) {
                $model->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "PUR-{$year}-";

        $last = self::where('invoice_number', 'like', "{$prefix}%")
            ->orderBy('invoice_number', 'desc')
            ->value('invoice_number');

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = ((int) end($parts)) + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // ─── Relationships ───────────────────────────────

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    // ─── Methods ─────────────────────────────────────

    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('subtotal');
        $this->grand_total = $this->subtotal - $this->discount + $this->tax + $this->shipping_cost;
        $this->paid_amount = $this->payments()->sum('amount');
        $this->due_amount = max(0, $this->grand_total - $this->paid_amount);
        $this->payment_status = match (true) {
            $this->due_amount <= 0 => 'paid',
            $this->paid_amount > 0 => 'partial',
            default => 'unpaid',
        };
        $this->save();
    }

    // ─── Computed ────────────────────────────────────

    public function getPaymentStatusBadgeAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'badge-success',
            'partial' => 'badge-warning',
            'unpaid' => 'badge-error',
            default => 'badge-ghost',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'received' => 'badge-success',
            'draft' => 'badge-warning',
            'returned' => 'badge-error',
            default => 'badge-ghost',
        };
    }
}
