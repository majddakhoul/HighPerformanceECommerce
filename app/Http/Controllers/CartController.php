<?php

namespace App\Http\Controllers;

use App\DTOs\AddToCartDTO;
use App\DTOs\UpdateCartItemDTO;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Repositories\Contracts\CartServiceInterface;
use App\Repositories\Contracts\TopProductsServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CartServiceInterface $cartService,
        private TopProductsServiceInterface $topProducts
    ) {}

    public function index()
    {
        return $this->success($this->cartService->getCart(Auth::id()));
    }

    public function add(AddToCartRequest $request)
    {
        $dto = AddToCartDTO::fromRequest($request);
        try {
            $this->cartService->addItem(Auth::id(), $dto->productId, $dto->quantity);
            return $this->success(null, 'Item added');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function remove(UpdateCartItemRequest $request)
    {
        $dto = UpdateCartItemDTO::fromRequest($request);
        $this->cartService->removeItem(Auth::id(), $dto->productId);
        return $this->success(null, 'Item removed');
    }

    public function update(UpdateCartItemRequest $request)
    {
        $dto = UpdateCartItemDTO::fromRequest($request);
        try {
            $this->cartService->updateQuantity(Auth::id(), $dto->productId, $dto->quantity);
            return $this->success(null, 'Cart updated');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function clear()
    {
        $this->cartService->clearCart(Auth::id());
        return $this->success(null, 'Cart cleared');
    }

    public function checkout()
    {
        try {
            $result = $this->cartService->checkout(Auth::id());
            return $this->success($result, 'Order placed', 201);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function checkoutOptimistic()
    {
        try {
            $result = $this->cartService->checkoutOptimistic(Auth::id());
            return $this->success($result, 'Order placed (optimistic)', 201);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function topProducts()
    {
        return $this->success($this->topProducts->getTopProducts());
    }
}