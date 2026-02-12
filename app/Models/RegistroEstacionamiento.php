<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroEstacionamiento extends Model
{
    use CrudTrait;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identificador',
        'user_app_id',
        'user_first_name',
        'user_last_name',
        'fecha_lectura',
        'hora_lectua',
        'price',
        'parking_duration',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'user_app_id' => 'integer',
        'fecha_lectura' => 'date',
        'price' => 'double',
    ];

    public function getUsuarioAppAttribute()
    {
        return $this->user_app_id .' - '.strtoupper($this->user_first_name).' '.strtoupper($this->user_last_name) ;
    }
}
