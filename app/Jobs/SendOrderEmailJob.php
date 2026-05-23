<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Invoice;
use App\Mail\OrderCreatedMail;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOrderEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(public int $orderId, public int $invoiceId) {}

    public function backoff(): array
    {
        return [5, 10, 20];
    }

    public function handle(OrderService $orderService): void
    {
        $order = Order::with('user')->findOrFail($this->orderId);
        $invoice = Invoice::findOrFail($this->invoiceId);

        Mail::to($order->user->email)->send(new OrderCreatedMail($order, $invoice));

        $orderService->updateStatus($order, 'preparing');
    }

    public function failed(\Throwable $e): void
    {
        Log::critical('SendOrderEmailJob failed', [
            'order_id'   => $this->orderId,
            'invoice_id' => $this->invoiceId,
            'error'      => $e->getMessage(),
        ]);
    }
}