<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteEstacionamientoExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $registros;

    public function __construct($registros)
    {
        $this->registros = $registros;
    }

    // Método para obtener los datos
    public function collection()
    {
        return $this->registros->map(function ($registro) {
            return [
                $registro->identificador,
                $registro->user_app_id,
                strtoupper($registro->user_first_name),
                strtoupper($registro->user_last_name),
                $registro->created_at,
                $registro->hora_lectua,
                $registro->price,
                $registro->parking_duration,
            ];
        });
    }

    // Definir los encabezados de las columnas
    public function headings(): array
    {
        return [
            'Identificador',
            'ID Usuario',
            'Nombre',
            'Apellido',
            'Fecha Lectura',
            'Hora Lectura',
            'Precio',
            'Duración Estacionamiento',
        ];
    }

    // Aplicar estilos a la hoja de cálculo
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la cabecera
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'], // Texto blanco
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['argb' => 'FF4CAF50'], // Fondo verde
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
            // Aplicar alineación general para todas las celdas
            'A' => ['alignment' => ['horizontal' => 'left']],
            'B' => ['alignment' => ['horizontal' => 'center']],
            'C' => ['alignment' => ['horizontal' => 'left']],
            'D' => ['alignment' => ['horizontal' => 'left']],
            'E' => ['alignment' => ['horizontal' => 'center']],
            'F' => ['alignment' => ['horizontal' => 'center']],
            'G' => ['alignment' => ['horizontal' => 'right']],
            'H' => ['alignment' => ['horizontal' => 'right']],
        ];
    }
}

