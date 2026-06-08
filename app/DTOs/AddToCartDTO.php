<?php

namespace App\DTOs;

use App\Http\Requests\AddToCartRequest;

class AddToCartDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly int $quantity
    ) {}

    public static function fromRequest(AddToCartRequest $request): self
    {
        return new self(
            productId: $request->product_id,
            quantity: $request->quantity
        );
    }
}