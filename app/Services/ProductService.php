<?php

namespace App\Services;

use App\DTOs\CreateProductDTO;
use App\DTOs\UpdateProductDTO;
use App\DTOs\DeleteProductDTO;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(protected ProductRepositoryInterface $productRepo) {}

    public function getAllProducts(): \Illuminate\Support\Collection
    {
        return $this->productRepo->all();
    }

    public function getProductById(int $id): ?Product
    {
        $product = $this->productRepo->find($id);
        if (!$product) throw new \Exception('Product not found', 404);
        return $product;
    }

    public function createProduct(CreateProductDTO $dto): Product
    {
        Gate::authorize('create', Product::class);
        return $this->productRepo->create($dto->toArray());
    }

    public function updateProduct(UpdateProductDTO $dto): Product
    {
        return DB::transaction(function () use ($dto) {
            $product = $this->productRepo->findAndLockForUpdate($dto->id);
            if (!$product) {
                throw new \Exception('Product not found', 404);
            }
            Gate::authorize('update', $product);

            $data = $dto->toArray();
            $product = $this->productRepo->update($product, $data);
            return $product;
        });
    }

    public function deleteProduct(DeleteProductDTO $dto): bool
    {
        $product = $this->productRepo->find($dto->id);
        if (!$product) throw new \Exception('Product not found', 404);
        Gate::authorize('delete', $product);
        return $this->productRepo->delete($product);
    }
}
