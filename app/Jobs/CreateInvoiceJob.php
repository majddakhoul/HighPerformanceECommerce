<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(public int $orderId) {}

    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function handle(InvoiceService $invoiceService): void
    {
        $order = Order::findOrFail($this->orderId);
        $invoice = $invoiceService->createFromOrder($order);

        SendOrderEmailJob::dispatch($order->id, $invoice->id)->onQueue('notifications');
    }

    public function failed(\Throwable $e): void
    {
        Log::critical('CreateInvoiceJob failed', [
            'order_id' => $this->orderId,
            'error'    => $e->getMessage(),
        ]);
    }
}