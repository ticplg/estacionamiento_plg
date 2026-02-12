<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteTicketsValidadosZonaFitExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
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
                $registro->fecha,
                $registro->zf_cliente_id,
                $registro->codigo_descuento,
                $registro->zf_cliente_cedula,
                strtoupper($registro->zf_cliente_nombre),
                strtoupper($registro->zf_cliente_apellido),
                $registro->zf_tipo_plan,
                $registro->identificador,
                $registro->duracion,
                //$registro->numero_factura,
                //$registro->monto_factura
            ];
        });
    }

    // Definir los encabezados de las columnas
    public function headings(): array
    {
        return [
            'Fecha',
            'Id Cliente Zona Fit',
            'Codigo Aplicado',
            'Numero Cedula',
            'Nombre',
            'Apellido',
            'Tipo Plan',
            'Identificador',
            'Duracion',
            //'Numero Factura',
            //'Monto Factura'
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
                    'startColor' => ['argb' => 'FF2196F3'], // Fondo azul
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
            // Ajustar alineación general
            'A' => ['alignment' => ['horizontal' => 'center']],
            'B' => ['alignment' => ['horizontal' => 'center']],
            'C' => ['alignment' => ['horizontal' => 'center']],
            'D' => ['alignment' => ['horizontal' => 'center']],
            'E' => ['alignment' => ['horizontal' => 'center']],
            'F' => ['alignment' => ['horizontal' => 'center']],
            'G' => ['alignment' => ['horizontal' => 'center']],
            'H' => ['alignment' => ['horizontal' => 'center']],
        ];
    }
}
