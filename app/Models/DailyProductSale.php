<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyProductSale extends Model
{
    protected $table = 'daily_product_sales';

    protected $fillable = [
        'date',
        'product_id',
        'total_quantity',
        'total_revenue',
        'total_cost',
        'profit',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}