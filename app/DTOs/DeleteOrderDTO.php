<?php

namespace App\DTOs;

use App\Http\Requests\DeleteOrderRequest;

class DeleteOrderDTO
{
    public function __construct(public int $id) {}

    public static function fromRequest(DeleteOrderRequest $request): self
    {
        return new self(id: $request->id);
    }
}
