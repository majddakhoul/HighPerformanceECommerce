<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Http\Requests\CreateOrderRequest;
use App\Services\OrderService;
use App\Jobs\ProcessOrderJob;
use App\Jobs\ProcessOrderOptimisticJob;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderQueueController extends Controller
{
    use ApiResponse;

    public function __construct(protected OrderService $orderService) {}

    public function storeAsyncSafe(CreateOrderRequest $request)
    {
        try {
            $this->authorize('create', Order::class);

            DB::beginTransaction();
            try {
                $order = $this->orderService->createPendingOrder(auth()->id());

                ProcessOrderJob::dispatch(
                    orderId: $order->id,
                    items: $request->items,
                    userId: auth()->id(),
                    mode: 'safe'
                )->onQueue('orders');

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return $this->success(['order_id' => $order->id], 'Order queued for processing', 202);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function storeAsyncUnsafe(CreateOrderRequest $request)
    {
        try {
            $this->authorize('create', Order::class);

            DB::beginTransaction();
            try {
                $order = $this->orderService->createPendingOrder(auth()->id());

                ProcessOrderJob::dispatch(
                    orderId: $order->id,
                    items: $request->items,
                    userId: auth()->id(),
                    mode: 'unsafe'
                )->onQueue('orders');

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return $this->success(['order_id' => $order->id], 'Order queued for processing', 202);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
    public function storeAsyncOptimisticSafe(CreateOrderRequest $request)
    {
        try {
            $this->authorize('create', Order::class);

            DB::beginTransaction();
            try {
                $order = $this->orderService->createPendingOrder(auth()->id());

                ProcessOrderOptimisticJob::dispatch(
                    orderId: $order->id,
                    items: $request->items,
                    userId: auth()->id(),
                    mode: 'safe'
                )->onQueue('orders');

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return $this->success(['order_id' => $order->id], 'Order queued for optimistic processing', 202);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
