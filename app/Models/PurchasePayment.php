<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{
    protected $fillable = [
        'purchase_id',
        'amount',
        'payment_method',
        'payment_date',
        'reference',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => __('Cash'),
            'bank_transfer' => __('Bank Transfer'),
            'bkash' => __('bKash'),
            'check' => __('Check'),
            'other' => __('Other'),
            default => ucfirst($this->payment_method),
        };
    }
}
