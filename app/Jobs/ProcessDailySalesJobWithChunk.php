<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDailySalesJobWithChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(public string $date)
    {
        $this->queue = 'sales';
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        $totalOrders = 0;
        $totalRevenue = 0;
        $totalCost = 0;

        Order::whereDate('delivered_at', $this->date)
            ->where('status', 'delivered')
            ->whereNull('sales_processed_at')
            ->with('items')
            ->chunkById(200, function ($orders) use (&$totalOrders, &$totalRevenue, &$totalCost) {

                foreach ($orders as $order) {
                    $totalOrders++;

                    foreach ($order->items as $item) {
                        $product = Product::find($item->product_id);

                        if (!$product) {
                            continue;
                        }

                        $quantity = $item->quantity;
                        $price = $item->price;
                        $cost = $product->cost;
                        $revenue = $quantity * $price;
                        $itemCost = $quantity * $cost;
                        $profit = $revenue - $itemCost;

                        DB::table('daily_product_sales')->updateOrInsert(
                            [
                                'date' => $this->date,
                                'product_id' => $item->product_id,
                            ],
                            [
                                'total_quantity' => DB::raw("total_quantity + $quantity"),
                                'total_revenue' => DB::raw("total_revenue + $revenue"),
                                'total_cost' => DB::raw("total_cost + $itemCost"),
                                'profit' => DB::raw("profit + $profit"),
                                'updated_at' => now(),
                            ]
                        );

                        $totalRevenue += $revenue;
                        $totalCost += $itemCost;
                    }

                    $order->sales_processed_at = now();
                    $order->save();
                }

                unset($orders);
            });

        $summary = DB::table('daily_product_sales')
            ->where('date', $this->date)
            ->selectRaw('
                SUM(total_quantity) as total_qty,
                SUM(total_revenue) as total_rev,
                SUM(total_cost) as total_cost,
                SUM(profit) as total_profit
            ')
            ->first();

        if ($summary && $summary->total_rev) {
            DB::table('daily_sales_totals')->updateOrInsert(
                ['date' => $this->date],
                [
                    'orders_count' => $totalOrders,
                    'total_revenue' => $summary->total_rev,
                    'total_cost' => $summary->total_cost,
                    'total_profit' => $summary->total_profit,
                    'updated_at' => now(),
                ]
            );
        }

        Log::info('Daily sales processed', [
            'date' => $this->date,
            'orders' => $totalOrders,
            'profit' => $summary->total_profit ?? 0,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessDailySalesJob failed', [
            'date' => $this->date,
            'error' => $e->getMessage(),
        ]);
    }
}
