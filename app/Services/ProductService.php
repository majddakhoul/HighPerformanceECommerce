<?php

namespace App\Services;

use App\DTOs\CreateProductDTO;
use App\DTOs\UpdateProductDTO;
use App\DTOs\DeleteProductDTO;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\DTOs\OptimisticDecrementDTO;
use App\DTOs\OptimisticDecrementUnsafeDTO;
use Illuminate\Support\Facades\Cache;

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
    public function decrementStockUnsafe(OptimisticDecrementUnsafeDTO $dto): Product
    {
        $product = $this->productRepo->find($dto->productId);
        if (!$product) {
            throw new \Exception('Product not found', 404);
        }
        if ($product->stock < $dto->quantity) {
            throw new \Exception('Insufficient stock', 409);
        }

        $product->decrement('stock', $dto->quantity);
        return $product->fresh();
    }

    public function decrementStockOptimistic(OptimisticDecrementDTO $dto): Product
    {
        return DB::transaction(function () use ($dto) {
            $product = $this->productRepo->find($dto->productId);
            if (!$product) {
                throw new \Exception('Product not found', 404);
            }
            if ($product->stock < $dto->quantity) {
                throw new \Exception('Insufficient stock', 409);
            }

            $newStock = $product->stock - $dto->quantity;
            $currentVersion = $product->version;

            $affected = Product::where('id', $product->id)
                ->where('version', $currentVersion)
                ->update([
                    'stock'   => $newStock,
                    'version' => $currentVersion + 1,
                ]);

            if ($affected === 0) {
                throw new \Exception('Optimistic lock failure, please retry', 409);
            }

            return $product->fresh();
        });
    }
    public function updateProductWithCacheLock(UpdateProductDTO $dto): Product
    {
        $lock = Cache::lock('product_update_' . $dto->id, 10);

        try {
            return $lock->block(5, function () use ($dto) {

                return DB::transaction(function () use ($dto) {

                    $product = $this->productRepo->find($dto->id);
                    if (!$product) {
                        throw new \Exception('Product not found', 404);
                    }

                    $data = $dto->toArray();
                    $product = $this->productRepo->update($product, $data);

                    return $product;
                });
            });
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {

            throw new \Exception('Could not acquire lock, please try again later', 409);
        } finally {
            optional($lock)->release();
        }
    }
}
