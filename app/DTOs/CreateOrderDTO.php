<?php

namespace App\DTOs;

use App\Http\Requests\CreateOrderRequest;

class CreateOrderDTO
{
    public function __construct(
        public array $items
    ) {}

    public static function fromRequest(CreateOrderRequest $request): self
    {
        return new self(
            items: $request->items
        );
    }
}
