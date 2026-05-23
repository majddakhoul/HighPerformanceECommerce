<?php

namespace App\Console\Commands;

use App\Exports\DailySalesTotalsExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class GenerateDailySalesTotalsReport extends Command
{
    protected $signature = 'report:daily-totals {date? : Month in Y-m format}';
    protected $description = 'Generate monthly Excel report from daily_sales_totals table';

    public function handle(): int
    {
        $date = $this->argument('date')
            ? Carbon::createFromFormat('Y-m', $this->argument('date'))
            : now()->subMonth();

        $year  = $date->year;
        $month = $date->month;
        $fileName = "daily_totals_{$year}_{$month}.xlsx";

        Excel::store(new DailySalesTotalsExport($year, $month), 'reports/' . $fileName, 'local');

        $this->info("Report stored: storage/app/reports/{$fileName}");

        return self::SUCCESS;
    }
}