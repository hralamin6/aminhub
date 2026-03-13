<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'delivery_date' => 'date',
        'confirmed_at' => 'datetime',
        'packed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'float',
        'grand_total' => 'float',
        'paid_amount' => 'float',
    ];

    public static function generateOrderNumber(): string
    {
        $year = now()->format('Y');
        $last = self::whereYear('created_at', $year)->max('id') ?? 0;
        return sprintf('ORD-%s-%04d', $year, $last + 1);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function address()
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class);
    }
}
