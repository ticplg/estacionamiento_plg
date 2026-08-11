<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroDescuentoAtc extends Model
{
    protected $table = 'registro_descuento_atc';

    protected $fillable = [
        'identificador',
        'documento',
        'comentario',
        'status_code',
		'created_at',
		'updated_at'
    ];

    /*protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];*/
}