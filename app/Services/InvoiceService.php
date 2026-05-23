<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;

class InvoiceService
{
    public function createFromOrder(Order $order): Invoice
    {
        return Invoice::create([
            'order_id' => $order->id,
            'user_id'  => $order->user_id,
            'total'    => $order->total_price,
            'status'   => 'unpaid',
        ]);
    }
}