<?php

namespace App\DTOs;

use App\Http\Requests\UpdateOrderRequest;

class UpdateOrderDTO
{
    public function __construct(
        public int $id,
        public ?string $status
    ) {}

    public static function fromRequest(UpdateOrderRequest $request): self
    {
        return new self(
            id: $request->id,
            status: $request->status
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'status' => $this->status,
        ]);
    }
}
