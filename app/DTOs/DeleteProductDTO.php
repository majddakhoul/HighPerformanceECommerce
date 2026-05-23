<?php

namespace App\DTOs;

use App\Http\Requests\DeleteProductRequest;

class DeleteProductDTO
{
    public function __construct(public int $id) {}

    public static function fromRequest(DeleteProductRequest $request): self
    {
        return new self(id: $request->id);
    }
}
