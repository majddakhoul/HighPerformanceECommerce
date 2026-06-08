<?php

namespace App\DTOs;

use App\Http\Requests\OptimisticDecrementUnsafeRequest;

class OptimisticDecrementUnsafeDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly int $quantity
    ) {}

    public static function fromRequest(OptimisticDecrementUnsafeRequest $request): self
    {
        return new self(
            productId: $request->product_id,
            quantity: $request->quantity
        );
    }
}
