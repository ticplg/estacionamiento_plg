<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RucActivo extends Model
{
    use HasFactory;

    protected $fillable = ['ruc', 'codigo', 'nombre', 'tipo', 'identificador', 'estado'];

    protected $hidden = ['codigo', 'tipo', 'identificador', 'estado', 'created_at', 'updated_at'];


}
