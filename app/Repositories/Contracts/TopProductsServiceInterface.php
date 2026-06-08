<?php

namespace App\Repositories\Contracts;

interface TopProductsServiceInterface
{
    public function increment(int $productId): void;
    public function decrement(int $productId): void;
    public function getTopProducts(int $limit = 20): array;
}