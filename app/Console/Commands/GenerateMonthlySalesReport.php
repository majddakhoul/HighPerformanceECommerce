<?php

namespace App\Console\Commands;

use App\Exports\MonthlySalesExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class GenerateMonthlySalesReport extends Command
{
    protected $signature = 'report:monthly-sales {date? : Month in Y-m format (e.g., 2026-05)}';
    protected $description = 'Generate monthly sales Excel report with chunking';

    public function handle(): int
    {
        $date = $this->argument('date')
            ? Carbon::createFromFormat('Y-m', $this->argument('date'))
            : now()->subMonth();

        $year = $date->year;
        $month = $date->month;
        $fileName = "monthly_sales_{$year}_{$month}.xlsx";

        Excel::store(new MonthlySalesExport($year, $month), 'reports/' . $fileName, 'local');

        $this->info("Report generated: storage/app/reports/{$fileName}");

        return self::SUCCESS;
    }
}
