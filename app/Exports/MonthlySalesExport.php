<?php

namespace App\Exports;

use App\Models\DailyProductSale; // إذا أنشأت نموذجًا، وإلا استخدم DB
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlySalesExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading, ShouldAutoSize, WithStyles
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
        return DailyProductSale::query()
            ->whereYear('date', $this->year)
            ->whereMonth('date', $this->month)
            ->orderBy('date')
            ->orderBy('product_id');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Product ID',
            'Total Quantity',
            'Total Revenue',
            'Total Cost',
            'Total Profit',
        ];
    }
    public function map($row): array
    {
        return [
            $row->date,
            $row->product_id,
            $row->total_quantity,
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
