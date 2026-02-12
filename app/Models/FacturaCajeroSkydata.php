<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturaCajeroSkydata extends Model
{
    use CrudTrait;
    use HasFactory;

    public function descargar_pdf()
    {
        if($this->estado_factura == 'Aprobado')
        {
            return '<a href="https://parkingplg.paseolagaleria.com.py/' . $this->path_factura . '" target="_blank" class="btn btn-sm btn-link"><i class="la la-download"></i><span> Descargar Factura</span></a>';

        }
        else
        {
            return;
        }
    }
    
}
