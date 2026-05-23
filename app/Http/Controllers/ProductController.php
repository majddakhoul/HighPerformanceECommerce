<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Services\ProductService;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\DeleteProductRequest;
use App\DTOs\CreateProductDTO;
use App\DTOs\UpdateProductDTO;
use App\DTOs\DeleteProductDTO;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(protected ProductService $productService) {}

    public function index()
    {
        $this->authorize('viewAny', Product::class);
        $products = $this->productService->getAllProducts();
        return $this->success(ProductResource::collection($products));
    }

    public function show(int $id)
    {
        try {
            $product = $this->productService->getProductById($id);
            $this->authorize('view', $product);
            return $this->success(new ProductResource($product));
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            if ($code < 100 || $code > 599) {
                $code = 404;
            }
            return $this->error($e->getMessage(), $code);
        }
    }

    public function store(CreateProductRequest $request)
    {
        try {
            $dto = CreateProductDTO::fromRequest($request);
            $product = $this->productService->createProduct($dto);
            return $this->success(new ProductResource($product), 'Product created', 201);
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            if ($code < 100 || $code > 599) {
                $code = 403;
            }
            return $this->error($e->getMessage(), $code);
        }
    }

    public function update(UpdateProductRequest $request)
    {
        try {
            $dto = UpdateProductDTO::fromRequest($request);
            $product = $this->productService->updateProduct($dto);
            return $this->success(new ProductResource($product), 'Product updated');
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            if ($code < 100 || $code > 599) {
                $code = 403;
            }
            return $this->error($e->getMessage(), $code);
        }
    }

    public function destroy(DeleteProductRequest $request)
    {
        try {
            $dto = DeleteProductDTO::fromRequest($request);
            $this->productService->deleteProduct($dto);
            return $this->success(null, 'Product deleted');
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            if ($code < 100 || $code > 599) {
                $code = 403;
            }
            return $this->error($e->getMessage(), $code);
        }
    }
}