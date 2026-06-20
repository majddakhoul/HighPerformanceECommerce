<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\TopProductsServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class TopProductsService implements TopProductsServiceInterface
{
    private const SORTED_SET = 'popular_products';
    private const LIST_CACHE_KEY = 'top_products_list';
    private const LIST_CACHE_TTL = 3600;

    public function increment(int $productId): void
    {
        Redis::zincrby(self::SORTED_SET, 1, $productId);
        Cache::forget(self::LIST_CACHE_KEY);
    }

    public function decrement(int $productId): void
    {
        $current = Redis::zscore(self::SORTED_SET, $productId);
        if ($current && $current > 0) {
            Redis::zincrby(self::SORTED_SET, -1, $productId);
            Cache::forget(self::LIST_CACHE_KEY);
        }
    }
    /*
    public function getTopProducts(int $limit = 20): array
    {
        return Cache::remember(
            "top_products_list_{$limit}",
            self::LIST_CACHE_TTL,
            function () use ($limit) {

                $top = Redis::zrevrange(
                    self::SORTED_SET,
                    0,
                    $limit - 1,
                    ['WITHSCORES' => true]
                );

                if (empty($top)) {
                    return [];
                }

                $productIds = array_map(
                    'intval',
                    array_keys($top)
                );

                $products = Product::whereIn('id', $productIds)
                    ->get()
                    ->keyBy('id');

                $result = [];

                foreach ($top as $productId => $score) {

                    $product = $products->get((int)$productId);

                    if (!$product) {
                        continue;
                    }

                    $result[] = [
                        'product_id' => $product->id,
                        'name'       => $product->name,
                        'price'      => $product->price,
                        'score'      => (int)$score,
                    ];
                }

                return $result;
            }
        );
    }*/
    public function getTopProducts(int $limit = 20): array
    {
        return Cache::remember(self::LIST_CACHE_KEY, self::LIST_CACHE_TTL, function () use ($limit) {
            $top = Redis::zrevrange(self::SORTED_SET, 0, $limit - 1, ['WITHSCORES' => true]);
            $result = [];
            foreach ($top as $productId => $score) {
                $productId = (int) $productId;
                if ($productId <= 0) continue;

                $product = Product::find($productId);
                if (!$product) continue;

                $result[] = [
                    'product_id' => $productId,
                    'name'       => $product->name,
                    'price'      => $product->price,
                    'score'      => (int) $score,
                ];
            }
            return $result;
        });
    }
}
