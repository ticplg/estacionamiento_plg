<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventoEspecialRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        return [
            'nombre_evento'          => 'required|string|max:255',
            'fecha_hora_inicio'      => 'required|date',
            'fecha_hora_fin'         => 'required|date|after_or_equal:fecha_hora_inicio',
            'locatario'              => 'required|string|max:255',
            'moneda_cierre'          => 'required|in:dolares,guaranies',
            'nombre_exonerador'      => 'required|string|max:255',
            'monto_por_ticket'       => 'required|numeric|min:0',
            'cantidad_tickets'       => 'required|integer|min:1',
        ];
    }

    public function attributes()
    {
        return [
            'nombre_evento'      => 'Nombre del evento',
            'fecha_desde'        => 'Fecha desde',
            'fecha_hasta'        => 'Fecha hasta',
            'locatario'          => 'Locatario',
            'moneda_cierre'      => 'Moneda de cierre',
            'nombre_exonerador'  => 'Nombre del exonerador',
            'monto_por_ticket'   => 'Monto por ticket',
            'cantidad_tickets'   => 'Cantidad de tickets',
        ];
    }

    public function messages()
    {
        return [
            'fecha_hasta.after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
            '*.required'                 => 'El campo :attribute es obligatorio.',
            '*.in'                       => 'El campo :attribute debe ser uno de los valores permitidos.',
            '*.numeric'                  => 'El campo :attribute debe ser un número.',
            '*.integer'                  => 'El campo :attribute debe ser un número entero.',
            '*.min'                      => 'El campo :attribute debe ser al menos :min.',
            '*.max'                      => 'El campo :attribute no debe exceder :max caracteres.',
        ];
    }
}
