<?php

namespace App\Jobs;

use App\Exports\DailySalesTotalsExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class GenerateDailySalesTotalsReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public int $year,
        public int $month
    ) {
        $this->queue = 'reports';
    }

    public function handle(): void
    {
        $fileName = "daily_totals_{$this->year}_{$this->month}.xlsx";
        Excel::store(new DailySalesTotalsExport($this->year, $this->month), 'reports/' . $fileName, 'local');

        Log::info("Daily totals report generated: {$fileName}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('DailySalesTotalsReportJob failed', [
            'year'  => $this->year,
            'month' => $this->month,
            'error' => $e->getMessage(),
        ]);
    }
}