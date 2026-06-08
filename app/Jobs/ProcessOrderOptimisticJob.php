<?php

namespace App\Jobs;

use App\Models\Order;
use App\Repositories\Contracts\TopProductsServiceInterface;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderOptimisticJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 60;

    public function __construct(
        public int $orderId,
        public array $items,
        public int $userId,
        public string $mode = 'safe'
    ) {}

    public function backoff(): array
    {
        return [5, 10, 30, 60, 120];
    }

    public function handle(OrderService $orderService, TopProductsServiceInterface $topProducts): void
    {
        $order = Order::findOrFail($this->orderId);

        if ($this->mode === 'unsafe') {
            $order = $orderService->confirmOrderUnsafe($order, $this->items);
        } else {
            $order = $orderService->confirmOrderOptimistic($order, $this->items);
        }

        foreach ($this->items as $item) {
            $topProducts->increment($item['product_id']);
        }

        CreateInvoiceJob::dispatch($order->id)->onQueue('invoices');
    }

    public function failed(\Throwable $e): void
    {
        Log::critical('ProcessOrderOptimisticJob failed', [
            'order_id' => $this->orderId,
            'error'    => $e->getMessage(),
        ]);
    }
}