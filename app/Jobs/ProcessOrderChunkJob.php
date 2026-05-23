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

class ProcessOrderChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public array $orderIds, public string $date)
    {
        $this->queue = 'sales';
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        $orders = Order::whereIn('id', $this->orderIds)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', $this->date)
            ->whereNull('sales_processed_at')
            ->with('items')
            ->get();

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if (!$product) continue;

                $qty    = $item->quantity;
                $rev    = $qty * $item->price;
                $cost   = $qty * $product->cost;
                $profit = $rev - $cost;

                DB::table('daily_product_sales')->updateOrInsert(
                    ['date' => $this->date, 'product_id' => $item->product_id],
                    [
                        'total_quantity' => DB::raw("total_quantity + {$qty}"),
                        'total_revenue'  => DB::raw("total_revenue + {$rev}"),
                        'total_cost'     => DB::raw("total_cost + {$cost}"),
                        'profit'         => DB::raw("profit + {$profit}"),
                        'updated_at'     => now(),
                    ]
                );
            }

            $order->sales_processed_at = now();
            $order->save();
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessOrderChunkJob failed', [
            'order_ids' => $this->orderIds,
            'date'      => $this->date,
            'error'     => $e->getMessage(),
        ]);
    }
}