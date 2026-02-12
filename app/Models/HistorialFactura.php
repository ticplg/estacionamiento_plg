<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialFactura extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $appends = ['nombre_factura', 'razon_social_convert'];

    public function getNombreFacturaAttribute()
    {
        return $this->documento .' '. strtoupper($this->razon_social);
    }

    public function getRazonSocialConvertAttribute()
    {
        return strtoupper($this->razon_social);
    }

    public function descargar_pdf()
    {
        if($this->estado_factura != 'Rechazado')
        {
            return '<a href="https://parkingplg.paseolagaleria.com.py/' . $this->path_factura . '" target="_blank" class="btn btn-sm btn-link"><i class="la la-download"></i><span> Descargar Factura</span></a>';
        }
        return;
    }

    public function descargar_xml()
    {
        return '<a href="https://parkingplg.paseolagaleria.com.py' . $this->path_xml . '" download target="_blank" class="btn btn-sm btn-link"><i class="la la-download"></i><span> Descargar XML</span></a>';
    }

    public function volver_enviar()
    {
        if ($this->estado_factura == 'Rechazado') {
            $url = route('factura.volver_enviar', $this->id); // Ruta Laravel

            return '<a href="#" onclick="if(confirm(\'¿Estás seguro que deseas volver a enviar esta factura?\')) { window.location.href=\'' . $url . '\'; }" class="btn btn-sm btn-link">
                        <i class="la la-sync"></i><span> Volver a Enviar Megaprint</span>
                    </a>';
        }
    }

}
