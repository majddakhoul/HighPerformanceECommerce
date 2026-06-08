<?php

namespace App\Http\Controllers;

use App\DTOs\CancelOrderDTO;
use App\DTOs\CreateOrderDTO;
use App\DTOs\DeleteOrderDTO;
use App\DTOs\UpdateOrderDTO;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\DeleteOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Jobs\SendOrderEmailJob;
use App\Models\Order;
use App\Repositories\Contracts\TopProductsServiceInterface;
use App\Services\InvoiceService;
use App\Services\OrderProcessingService;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OrderService $orderService,
        protected OrderProcessingService $processingService,
        protected InvoiceService $invoiceService
    ) {}

    public function index()
    {
        try {
            $this->authorize('viewAny', Order::class);
            $orders = $this->orderService->getAllOrders();
            return $this->success(OrderResource::collection($orders));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 403);
        }
    }

    public function myOrders()
    {
        try {
            $orders = $this->orderService->getUserOrders(Auth::id());
            return $this->success(OrderResource::collection($orders));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function show(int $id)
    {
        try {
            $order = $this->orderService->getOrderById($id);
            $this->authorize('view', $order);
            return $this->success(new OrderResource($order));
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function storeSyncSafe(CreateOrderRequest $request)
    {
        try {
            $this->authorize('create', Order::class);
            $dto = CreateOrderDTO::fromRequest($request);
            $order = $this->processingService->processSync($dto, auth()->id(), 'safe');
            return $this->success($order, 'Order processed successfully', 201);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function storeSyncUnsafe(CreateOrderRequest $request)
    {
        try {
            $this->authorize('create', Order::class);
            $dto = CreateOrderDTO::fromRequest($request);
            $order = $this->processingService->processSync($dto, auth()->id(), 'unsafe');
            return $this->success($order, 'Order processed successfully', 201);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function update(UpdateOrderRequest $request)
    {
        try {
            $dto = UpdateOrderDTO::fromRequest($request);
            $order = $this->orderService->getOrderById($dto->id);
            $this->authorize('update', $order);
            $order = $this->orderService->updateOrder($order, $dto->status);
            return $this->success(new OrderResource($order));
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 403);
        }
    }

    public function destroy(DeleteOrderRequest $request)
    {
        try {
            $dto = DeleteOrderDTO::fromRequest($request);
            $order = $this->orderService->getOrderById($dto->id);
            $this->authorize('delete', $order);
            $this->orderService->deleteOrder($dto);
            return $this->success(null, 'Order deleted');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 403);
        }
    }

    public function cancel(CancelOrderRequest $request, TopProductsServiceInterface $topProducts)
    {
        try {
            $dto = CancelOrderDTO::fromRequest($request);
            $order = $this->orderService->cancelOrder($dto, Auth::id(), $topProducts);
            return $this->success(new OrderResource($order), 'Order cancelled');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 403);
        }
    }
    public function storeSyncOptimisticSafe(CreateOrderRequest $request)
    {
        try {
            $this->authorize('create', Order::class);
            $dto = CreateOrderDTO::fromRequest($request);

            $order = $this->orderService->createPendingOrder(auth()->id());

            DB::beginTransaction();
            try {
                $order = $this->orderService->confirmOrderOptimistic($order, $dto->items);
                $invoice = $this->invoiceService->createFromOrder($order);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            SendOrderEmailJob::dispatch($order->id, $invoice->id)->onQueue('notifications');

            return $this->success($order->load('invoice'), 'Order processed successfully (optimistic)', 201);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
    public function testTransactionFailure(CreateOrderRequest $request)
    {
        try {
            $this->authorize('create', Order::class);
            $dto = CreateOrderDTO::fromRequest($request);

            $this->processingService->processSyncWithIntentionalFailure($dto, auth()->id());
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
