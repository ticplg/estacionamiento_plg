<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteTicketsEventoDescuentoExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $registros;

    public function __construct($registros)
    {
        $this->registros = $registros;
    }

    public function collection()
    {
        return $this->registros->map(function ($registro) {
            return [
                $registro->nombre_evento,
                $registro->tipo_evento,
                $registro->fecha_hora_inicio,
                $registro->fecha_hora_fin,
                $registro->identificador,
                $registro->fecha_hora_validacion,
                $registro->cantidad,
                $registro->es_tarifado,
                $registro->monto_ticket,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nombre del Evento',
            'Tipo de Evento',
            'Fecha y Hora de Inicio',
            'Fecha y Hora de Fin',
            'Identificador',
            'Fecha y Hora de Validación',
            'Cantidad Solicitada',
            'Tarifado',
            'Monto Ticket',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['argb' => 'FF2196F3'],
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
            'A' => ['alignment' => ['horizontal' => 'center']],
            'B' => ['alignment' => ['horizontal' => 'center']],
            'C' => ['alignment' => ['horizontal' => 'center']],
            'D' => ['alignment' => ['horizontal' => 'center']],
            'E' => ['alignment' => ['horizontal' => 'center']],
            'F' => ['alignment' => ['horizontal' => 'center']],
            'G' => ['alignment' => ['horizontal' => 'center']],
            'H' => ['alignment' => ['horizontal' => 'center']],
            'I' => ['alignment' => ['horizontal' => 'center']],
        ];
    }
}
