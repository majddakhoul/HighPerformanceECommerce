<?php

namespace App\DTOs;

use App\Http\Requests\CreateProductRequest;

class CreateProductDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
        public float $price,
        public int $stock,        
        public float $cost,
        public ?string $category,
        public ?string $image
    ) {}

    public static function fromRequest(CreateProductRequest $request): self
    {
        return new self(
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
        return [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'cost' => $this->cost,
            'category' => $this->category,
            'image' => $this->image,
        ];
    }
}
