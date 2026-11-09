<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkydataRucCorreo extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'skydata_ruc_correos';

    protected $fillable = ['ruc', 'razon_social', 'email', 'estado'];

    public function setRucAttribute($value)
    {
        $this->attributes['ruc'] = trim((string) $value);
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = mb_strtolower(trim((string) $value));
    }
}
