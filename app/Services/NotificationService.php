<?php

namespace App\Services;

use App\Mail\OrderCreatedMail;
use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendOrderCreatedEmail(Order $order, Invoice $invoice): void
    {
        try {
            Mail::to($order->user->email)
                ->send(new OrderCreatedMail($order, $invoice));
        } catch (\Throwable $e) {
            Log::error('Sync email failed', [
                'order_id'   => $order->id,
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
