<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FacturaCajeroSkydataExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Fecha Emisión',
            'RUC Receptor',
            'Nombre Receptor',
            'Total',
            'Timbrado',
            'Número Factura',
            'Cajero',
            'CDC',
            'Respuesta Servicio Factura',
            'Respuesta Servicio Pago',
            'Tipo de Pago',
            'Número Boleta',
        ];
    }
}
