<?php

namespace App\Exports;

use App\Models\DailySalesTotal;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class MonthlySalesTotalsExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading, ShouldAutoSize, WithStyles
{
    protected int $year;
    protected int $month;

    public function __construct(int $year, int $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    public function query()
    {
        return DailySalesTotal::query()
            ->whereYear('date', $this->year)
            ->whereMonth('date', $this->month)
            ->orderBy('date');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return [
            'Day',
            'Orders Count',
            'Total Revenue',
            'Total Cost',
            'Total Profit',
        ];
    }

    public function map($row): array
    {
        return [
            Carbon::parse($row->date)->day,
            $row->orders_count,
            number_format($row->total_revenue, 2),
            number_format($row->total_cost, 2),
            number_format($row->total_profit, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
