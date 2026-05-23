<?php

namespace App\Jobs;

use App\Exports\MonthlySalesExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class GenerateMonthlySalesReportJob implements ShouldQueue
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
        $fileName = "monthly_sales_{$this->year}_{$this->month}.xlsx";
        Excel::store(new MonthlySalesExport($this->year, $this->month), 'reports/' . $fileName, 'local');

        Log::info("Monthly report stored: {$fileName}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Monthly report generation failed', [
            'year'  => $this->year,
            'month' => $this->month,
            'error' => $e->getMessage(),
        ]);
    }
}