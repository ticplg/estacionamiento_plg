<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteTicketsPagadosExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
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
                $registro->fecha_factura,
                $registro->numero_factura,
                $registro->monto_factura,
                ltrim($registro->ticket_number, '0'),
                $registro->razon_social,
                $registro->documento,
                $registro->forma_pago,
                $registro->tipo_tarjeta,
                $registro->cdc,
                $registro->punto_venta,
                $registro->estado_factura
            ];
        });
    }

    // Definir los encabezados de las columnas
    public function headings(): array
    {
        return [
            'Identificador',
            'Fecha Factura',
            'Numero Factura',
            'Monto Factura',
            'Numero Comprobante Bancard',
            'Nombre/Razon Social',
            'Documento',
            'Forma Pago',
            'Tipo Tarjeta',
            'CDC',
            'Punto de Venta',
            'Estado Factura'
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
            'A' => ['alignment' => ['horizontal' => 'left']],
            'B' => ['alignment' => ['horizontal' => 'left']],
            'C' => ['alignment' => ['horizontal' => 'left']],
            'D' => ['alignment' => ['horizontal' => 'left']],
            'E' => ['alignment' => ['horizontal' => 'left']],
            'F' => ['alignment' => ['horizontal' => 'left']],
            'G' => ['alignment' => ['horizontal' => 'left']],
            'H' => ['alignment' => ['horizontal' => 'left']],
            'I' => ['alignment' => ['horizontal' => 'left']],
            'J' => ['alignment' => ['horizontal' => 'left']],
            'K' => ['alignment' => ['horizontal' => 'left']],
            'L' => ['alignment' => ['horizontal' => 'left']],
            'M' => ['alignment' => ['horizontal' => 'left']],
        ];
    }
}
