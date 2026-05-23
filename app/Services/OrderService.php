<?php

namespace App\Services;

use App\DTOs\CreateOrderDTO;
use App\DTOs\UpdateOrderDTO;
use App\DTOs\DeleteOrderDTO;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepo,
        protected ProductRepositoryInterface $productRepo
    ) {}

    public function getAllOrders()
    {
        return $this->orderRepo->all();
    }

    public function getUserOrders(int $userId)
    {
        return $this->orderRepo->getUserOrders($userId);
    }

    public function getOrderById(int $id): Order
    {
        $order = $this->orderRepo->find($id);

        if (!$order) {
            throw new \Exception('Order not found', 404);
        }

        return $order;
    }
    public function createPendingOrder(int $userId): Order
    {
        return $this->orderRepo->create([
            'user_id'     => $userId,
            'total_price' => 0,
            'status'      => 'pending',
        ]);
    }

    public function confirmOrderUnsafe(Order $order, array $items): Order
    {
        $total = 0;
        $orderItems = [];

        foreach ($items as $item) {
            $product = $this->productRepo->find($item['product_id']);

            if (!$product) {
                throw new \RuntimeException('Product not found', 404);
            }

            if ($product->stock < $item['quantity']) {
                throw new \RuntimeException('Insufficient stock', 409);
            }

            $total += $product->price * $item['quantity'];

            $orderItems[] = [
                'product_id' => $product->id,
                'quantity'   => $item['quantity'],
                'price'      => $product->price,
            ];

            $product->decrement('stock', $item['quantity']);
        }

        $this->orderRepo->update($order, [
            'total_price' => $total,
            'status'      => 'confirmed',
        ]);

        foreach ($orderItems as $oi) {
            $order->items()->create($oi);
        }

        return $order->load('items.product');
    }

    public function confirmOrderSafe(Order $order, array $items): Order
    {
        return DB::transaction(function () use ($order, $items) {
            $total = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $product = $this->productRepo->findAndLockForUpdate($item['product_id']);

                if (!$product) {
                    throw new \RuntimeException('Product not found', 404);
                }

                if ($product->stock < $item['quantity']) {
                    throw new \RuntimeException('Insufficient stock', 409);
                }

                $total += $product->price * $item['quantity'];

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                ];

                $product->decrement('stock', $item['quantity']);
            }

            $this->orderRepo->update($order, [
                'total_price' => $total,
                'status'      => 'confirmed',
            ]);

            foreach ($orderItems as $oi) {
                $order->items()->create($oi);
            }

            return $order->load('items.product');
        }, 5);
    }

    public function updateStatus(Order $order, string $status): Order
    {
        return $this->orderRepo->update($order, ['status' => $status]);
    }

    public function updateOrder(Order $order, string $status): Order
    {
        $order = $this->orderRepo->update($order, ['status' => $status]);

        if ($invoice = $order->invoice) {
            $newInvoiceStatus = match ($status) {
                'delivered' => 'paid',
                'cancelled' => 'cancelled',
                default     => $invoice->status,
            };

            if ($newInvoiceStatus !== $invoice->status) {
                $invoice->update(['status' => $newInvoiceStatus]);
            }
        }

        return $order;
    }
    public function deleteOrder(DeleteOrderDTO $dto): bool
    {
        $order = $this->orderRepo->find($dto->id);

        if (!$order) {
            throw new \Exception('Order not found', 404);
        }

        return DB::transaction(function () use ($order) {

            foreach ($order->items as $item) {
                $product = $item->product;

                $product->increment('stock', $item->quantity);
            }

            return $this->orderRepo->delete($order);
        }, 5);
    }
}
