<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReporteTablaCompletaExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected Collection $rows;
    protected array $headings;

    public function __construct(Collection $rows, array $headings)
    {
        $this->rows = $rows;
        $this->headings = $headings;
    }

    public function collection()
    {
        return $this->rows->map(function ($row) {
            return (array) $row;
        });
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
