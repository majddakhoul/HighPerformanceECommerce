<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Collection;

class OrderRepository implements OrderRepositoryInterface
{
    public function all(): Collection
    {
        return Order::with('items.product')->get();
    }

    public function find(int $id): ?Order
    {
        return Order::with('items.product')->find($id);
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);
        return $order;
    }

    public function delete(Order $order): bool
    {
        return $order->delete();
    }

    public function getUserOrders(int $userId): Collection
    {
        return Order::with('items.product')->where('user_id', $userId)->get();
    }
}