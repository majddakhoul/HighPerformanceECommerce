<?php

namespace App\DTOs;

use App\Http\Requests\CancelOrderRequest;

class CancelOrderDTO
{
    public function __construct(public readonly int $id) {}

    public static function fromRequest(CancelOrderRequest $request): self
    {
        return new self(id: $request->id);
    }
}