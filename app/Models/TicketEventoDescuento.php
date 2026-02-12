<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketEventoDescuento extends Model
{
    use HasFactory;

    public function evento()
    {
        return $this->belongsTo(EventoDescuento::Class, 'evento_id', 'id');
    }

}
