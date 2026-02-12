<?php

namespace App\Models;

use Carbon\Carbon;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoDescuento extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'evento_descuentos';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];

    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->fecha_hora_fin) {
                $model->fecha_hora_fin_exoneracion = Carbon::parse($model->fecha_hora_fin)->addHour();
            }
        });
    }
}
