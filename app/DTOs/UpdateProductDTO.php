<?php

namespace App\DTOs;

use App\Http\Requests\AdminUpdateProductRequest;
use App\Http\Requests\UpdateProductRequest;

class UpdateProductDTO
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $description,
        public ?float $price,
        public ?int $stock,
        public float $cost,
        public ?string $category,
        public ?string $image
    ) {}

    public static function fromRequest(UpdateProductRequest $request): self
    {
        return new self(
            id: $request->id,
            name: $request->name,
            description: $request->description,
            price: $request->price,
            stock: $request->stock,
            cost: $request->cost,
            category: $request->category,
            image: $request->image
        );
    }
    public static function fromAdminRequest(AdminUpdateProductRequest $request, int $id): self
    {
        return new self(
            id: $id,
            name: $request->name,
            description: $request->description,
            price: $request->price,
            stock: $request->stock,
            cost: $request->cost,
            category: $request->category,
            image: $request->image
        );
    }
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'cost' => $this->cost,
            'category' => $this->category,
            'image' => $this->image,
        ], fn($value) => !is_null($value));
    }
}
