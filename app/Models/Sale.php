<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'sale_type',
        'customer_id',
        'customer_name',
        'customer_phone',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax',
        'grand_total',
        'paid_amount',
        'change_amount',
        'due_amount',
        'payment_method',
        'payment_status',
        'status',
        'note',
        'sold_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
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
        $prefix = "INV-{$year}-";

        $last = self::withTrashed()
            ->where('invoice_number', 'like', "{$prefix}%")
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    // ─── Computed ────────────────────────────────────

    public function getCustomerDisplayAttribute(): string
    {
        if ($this->customer) {
            return $this->customer->name;
        }
        return $this->customer_name ?: __('Walk-in Customer');
    }

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
            'completed' => 'badge-success',
            'draft' => 'badge-warning',
            'void' => 'badge-error',
            default => 'badge-ghost',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => __('Cash'),
            'bkash' => __('bKash'),
            'nagad' => __('Nagad'),
            'card' => __('Card'),
            'mixed' => __('Mixed'),
            default => ucfirst($this->payment_method),
        };
    }
}
