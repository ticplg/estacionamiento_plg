<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroEstacionamientoPago extends Model
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
        'price',
        'user_id',
        'user_name',
        'user_lastname',
        'fecha_pago',
        'hora_pago',
		'qr_transaction_id',
		'return_xml',
		'status_code'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'price' => 'double',
        'user_id' => 'integer',
        'fecha_pago' => 'date',
    ];
}
