<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOrderChunkJob;
use App\Models\Order;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class DispatchDailySalesBatch extends Command
{
    protected $signature = 'sales:dispatch-batch {date? : Date in Y-m-d format}';
    protected $description = 'Divide delivered orders into chunks and dispatch them for batch processing';

    public function handle(): int
    {
        $date = $this->argument('date') ?? now()->toDateString();

        $orderIds = Order::whereDate('delivered_at', $date)
            ->where('status', 'delivered')
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            $this->info("No delivered orders found for {$date}.");
            return 0;
        }

        $chunks = $orderIds->chunk(100);
        $jobs = $chunks->map(fn($chunk) => new ProcessOrderChunkJob($chunk->toArray(), $date));

        Bus::batch($jobs)
            ->onQueue('sales')
            ->then(function (Batch $batch) use ($date) {
                Log::info("All sales chunks processed for {$date}");
            })
            ->catch(function (Batch $batch, \Throwable $e) use ($date) {
                Log::error("Sales batch failed for {$date}", [
                    'error'      => $e->getMessage(),
                    'failed_jobs' => $batch->failedJobs,
                ]);
            })
            ->dispatch();

        $this->info("Dispatched {$chunks->count()} chunks for {$date}.");
        return 0;
    }
}
