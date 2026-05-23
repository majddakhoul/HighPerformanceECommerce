<?php

namespace App\Services;

use App\DTOs\CreateOrderDTO;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderProcessingService
{
    public function __construct(
        protected OrderService $orderService,
        protected InvoiceService $invoiceService,
        protected NotificationService $notificationService
    ) {}

    public function processSync(CreateOrderDTO $dto, int $userId, string $mode = 'safe'): Order
    {
        $order = $this->orderService->createPendingOrder($userId);

        DB::beginTransaction();
        try {
            if ($mode === 'unsafe') {
                $order = $this->orderService->confirmOrderUnsafe($order, $dto->items);
            } else {
                $order = $this->orderService->confirmOrderSafe($order, $dto->items);
            }

            $invoice = $this->invoiceService->createFromOrder($order);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->notificationService->sendOrderCreatedEmail($order, $invoice);
        $this->orderService->updateStatus($order, 'preparing');

        return $order->load('invoice');
    }
}