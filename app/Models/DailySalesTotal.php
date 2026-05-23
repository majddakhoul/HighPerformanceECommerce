<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySalesTotal extends Model
{
    protected $table = 'daily_sales_totals';

    protected $fillable = [
        'date',
        'orders_count',
        'total_revenue',
        'total_cost',
        'total_profit',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}