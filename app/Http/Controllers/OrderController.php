<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Services\OrderService;
use App\Services\OrderProcessingService;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\DeleteOrderRequest;
use App\DTOs\CreateOrderDTO;
use App\DTOs\UpdateOrderDTO;
use App\DTOs\DeleteOrderDTO;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OrderService $orderService,
        protected OrderProcessingService $processingService
    ) {}

    public function index()
    {
        $this->authorize('viewAny', Order::class);
        $orders = $this->orderService->getAllOrders();
        return $this->success(OrderResource::collection($orders));
    }

    public function myOrders()
    {
        $orders = $this->orderService->getUserOrders(Auth::id());
        return $this->success(OrderResource::collection($orders));
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
}
