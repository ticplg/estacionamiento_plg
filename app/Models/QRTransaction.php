<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QRTransaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';
    
    public $timestamps = false;

    // Definir los campos que pueden ser asignados de forma masiva
    protected $fillable = [
        'hook_alias',
        'status',
        'response_code',
        'response_description',
        'amount',
        'currency',
        'installment_number',
        'description',
        'date_time',
        'ticket_number',
        'authorization_code',
        'commerce_name',
        'branch_name',
        'created_at',
        'bin',
        'merchant_code',
        'name',
        'lastname',
        'card_last_numbers',
        'account_type',
        'identificador',
        'nombre_cliente',
        'ruc_cliente',
        'salida_ejecutada',
        'fullname',
        'email',
        'ci',
		'updated_at',
		'status_code',
    ];
}
