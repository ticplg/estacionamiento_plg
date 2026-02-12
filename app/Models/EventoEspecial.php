<?php

namespace App\Models;

use Carbon\Carbon;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoEspecial extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'evento_especials';

    protected $fillable = 
    [
        'nombre_evento',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'locatario',
        'moneda_cierre',
        'nombre_exonerador',
        'monto_por_ticket',
        'cantidad_tickets',
        'cantidad_validaciones_hechas',
        'fecha_hora_fin_exoneracion'
    ];
    
    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->fecha_hora_fin) {
                $model->fecha_hora_fin_exoneracion = Carbon::parse($model->fecha_hora_fin)->addHour();
            }
        });
    }

}
