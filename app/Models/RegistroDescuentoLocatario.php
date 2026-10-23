<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroDescuentoLocatario extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'registro_descuento_locatarios';
    protected $fillable = ['usuario_id', 'fecha_registro', 'identificador', 'codigo_descuento', 'nombre', 'apellido',
        'nro_factura', 'monto_ticket', 'monto_descuento', 'monto_final', 'response_status', 'response_body'];
    protected $casts = ['fecha_registro' => 'datetime'];
    protected $hidden = ['response_body'];

    public function usuario()
    {
        return $this->belongsTo(UsuarioLocatario::class, 'usuario_id');
    }
}
