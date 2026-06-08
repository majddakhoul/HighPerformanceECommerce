<?php

namespace App\DTOs;

use App\Http\Requests\OptimisticDecrementRequest;

class OptimisticDecrementDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly int $quantity
    ) {}

    public static function fromRequest(OptimisticDecrementRequest $request): self
    {
        return new self(
            productId: $request->product_id,
            quantity: $request->quantity
        );
    }
}
