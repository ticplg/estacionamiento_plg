<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReporteTicketsHotelExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles,
    WithColumnFormatting,
    WithEvents
{
    public function __construct(private Collection $rows) {}

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Identificador',
            'Nombre Huésped',
            'Hab.',
            'Fecha Check-in',
            'Hora Check-in',
            'Fecha Check-out',
            'Hora Check-out',
            'Monto en Check-out',
        ];
    }

    public function map($row): array
    {
        // Si tus fechas/horas vienen como strings, las pasamos tal cual.
        // PhpSpreadsheet aplicará formato visual (no cambia el valor original).
        return [
            $row->identificador,
            $row->nombre_huesped,
            $row->habitacion,
            $row->fecha_checkin,   // ej: 2025-10-08
            $row->hora_checkin,    // ej: 14:35:00
            $row->fecha_checkout,
            $row->hora_checkout,
            is_numeric($row->monto_en_checkout) ? (float) $row->monto_en_checkout : $row->monto_en_checkout,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezado: negrita, centrado, fondo color y texto blanco
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                // Color: un azul suave (podés cambiar el rgb)
                'startColor' => ['rgb' => '1F4E78'],
            ],
        ]);

        // Altura del header
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Alineación columnas puntuales
        $sheet->getStyle('C:C')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Hab.
        $sheet->getStyle('D:D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Fecha in
        $sheet->getStyle('E:E')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Hora in
        $sheet->getStyle('F:F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Fecha out
        $sheet->getStyle('G:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Hora out
        $sheet->getStyle('H:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);  // Monto

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_DATE_DDMMYYYY,   // Fecha Check-in
            'E' => NumberFormat::FORMAT_DATE_TIME3,      // Hora Check-in (hh:mm)
            'F' => NumberFormat::FORMAT_DATE_DDMMYYYY,   // Fecha Check-out
            'G' => NumberFormat::FORMAT_DATE_TIME3,      // Hora Check-out (hh:mm)
            // Monto: miles, sin decimales (Gs. normalmente), ajustá si querés decimales
            'H' => '#,##0',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // AutoFiltro en el encabezado
                $sheet->setAutoFilter("A1:H1");

                // Congelar encabezado (fila 1)
                $sheet->freezePane('A2');

                // Bordes en toda el área con datos
                $sheet->getStyle("A1:H{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D9D9D9'],
                        ],
                    ],
                ]);

                // Rayado suave (bandas) opcional: aplicamos un fill claro a filas pares
                for ($r = 2; $r <= $highestRow; $r++) {
                    if ($r % 2 === 0) {
                        $sheet->getStyle("A{$r}:H{$r}")->getFill()->setFillType(Fill::FILL_SOLID)
                              ->getStartColor()->setRGB('F7F9FC'); // gris muy claro
                    }
                }

                // Ajuste de texto para nombres largos (opcional)
                $sheet->getStyle("B2:B{$highestRow}")
                      ->getAlignment()->setWrapText(true);
            },
        ];
    }
}
