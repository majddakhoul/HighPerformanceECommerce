<?php

namespace App\Console;

use App\Jobs\GenerateDailySalesTotalsReportJob;
use App\Jobs\GenerateMonthlySalesReportJob;
use App\Jobs\ProcessDailySalesJobWithChunk;
use App\Jobs\ProcessDailySalesJobWithoutChunk;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
       $schedule->job(new ProcessDailySalesJobWithChunk(now()->toDateString()), 'sales')
            //->dailyAt('02:00');
            //->everyMinute();
            ->everyTwoMinutes();
        $schedule->job(new ProcessDailySalesJobWithoutChunk(now()->toDateString()), 'sales')
            //->dailyAt('02:00');
            //->everyMinute();
            ->everyTwoMinutes();
        $schedule->job(new GenerateMonthlySalesReportJob(now()->year, now()->month), 'reports')
            //->lastDayOfMonth('23:00');
            ->everyThreeMinutes();
        $schedule->job(new GenerateDailySalesTotalsReportJob(now()->year, now()->month), 'reports')
            //->lastDayOfMonth('23:00');
            ->everyThreeMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
