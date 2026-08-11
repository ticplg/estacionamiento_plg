<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturaCajeroSkydata extends Model
{
    use CrudTrait;
    use HasFactory;

    /**
     * Botón para descargar PDF.
     */
    public function descargar_pdf()
    {
        if (!empty($this->path_factura)) {
            $path = ltrim($this->path_factura, '/');
            $url = 'https://parkingplg.paseolagaleria.com.py/' . $path;

            return '<a href="' . $url . '" target="_blank" class="btn btn-sm btn-link">
                        <i class="la la-download"></i><span> Descargar Factura</span>
                    </a>';
        }
        
        return '';
    }

    /**
     * Botón para descargar XML.
     * Genera la ruta basándose en el path del PDF (cambiando .pdf por .xml)
     */
    public function descargar_xml()
    {
        if (!empty($this->path_factura)) {
            // Reemplazamos .pdf por .xml en la ruta
            $path_xml = str_replace('.pdf', '.xml', $this->path_factura);
            $path = ltrim($path_xml, '/');
            $url = 'https://parkingplg.paseolagaleria.com.py/' . $path;

            return '<a href="' . $url . '" download target="_blank" class="btn btn-sm btn-link">
                        <i class="la la-download"></i><span> Descargar XML</span>
                    </a>';
        }
        
        return '';
    }
}