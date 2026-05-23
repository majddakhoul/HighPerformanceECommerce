<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id', 'total_price', 'status', 'delivered_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
    protected static function booted()
    {
        static::updating(function ($order) {
            if ($order->isDirty('status') && $order->status === 'delivered') {
                $order->delivered_at = now();
            }
        });
    }
}
