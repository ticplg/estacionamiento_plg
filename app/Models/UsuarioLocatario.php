<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class UsuarioLocatario extends Model
{
    use CrudTrait;
    use HasFactory;

    const ESTADO_ACTIVO = 'A';
    const ESTADO_INACTIVO = 'I';

    protected $table = 'usuarios_locatarios';
    protected $fillable = ['usuario', 'password', 'local', 'codigo_descuento', 'estado'];
    protected $hidden = ['password', 'api_token'];

    // Columna que Backpack muestra en relaciones; evita que la adivine leyendo el esquema de la tabla
    public function identifiableAttribute()
    {
        return 'usuario';
    }

    public function descuentos()
    {
        return $this->hasMany(RegistroDescuentoLocatario::class, 'usuario_id');
    }

    public function estaActivo()
    {
        return $this->estado == self::ESTADO_ACTIVO;
    }

    // Un password vacío (edición en el admin sin cambiarlo) deja el actual
    public function setPasswordAttribute($value)
    {
        if (filled($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }
}
