<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class EventoDescuentoRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        return [
            'nombre_evento' => 'required|string|max:255',
            'tipo_evento' => 'required|in:jornada_completa,media_jornada',
            'fecha_hora_inicio' => ['required', 'date', function ($attribute, $value, $fail) {
                if (Carbon::parse($value)->toDateString() < now()->toDateString()) {
                    $fail('La fecha de inicio no puede ser anterior a hoy.');
                }
            }],
            'fecha_hora_fin' => ['required', 'date', function ($attribute, $value, $fail) {
                if (Carbon::parse($value)->toDateString() < now()->toDateString()) {
                    $fail('La fecha de fin no puede ser anterior a hoy.');
                }
            }],
            'cantidad_validaciones_disponibles' => 'required|integer|min:1',
        ];
    }

    public function attributes()
    {
        return [
            'nombre_evento' => 'nombre del evento',
            'tipo_evento' => 'tipo de evento',
            'fecha_hora_inicio' => 'fecha y hora de inicio',
            'fecha_hora_fin' => 'fecha y hora de fin',
            'cantidad_validaciones_disponibles' => 'cantidad de validaciones disponibles',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser un texto.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'in' => 'El valor seleccionado para :attribute no es válido.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'min' => 'El campo :attribute debe tener al menos :min.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $fechaDesde = Carbon::parse($this->input('fecha_hora_inicio'));
            $fechaHasta = Carbon::parse($this->input('fecha_hora_fin'));

            // Validar que fecha_hora_fin no sea menor que fecha_hora_inicio (considerando fecha y hora)
            if ($fechaHasta->lt($fechaDesde)) {
                $validator->errors()->add('fecha_hora_fin', 'La fecha y hora de fin no puede ser anterior a la fecha y hora de inicio.');
            }

            // Validaciones de que no sean fechas pasadas (ya están en rules(), pero por si se omiten)
            if ($fechaDesde->toDateString() < now()->toDateString()) {
                $validator->errors()->add('fecha_hora_inicio', 'La fecha de inicio no puede ser anterior a hoy.');
            }

            if ($fechaHasta->toDateString() < now()->toDateString()) {
                $validator->errors()->add('fecha_hora_fin', 'La fecha de fin no puede ser anterior a hoy.');
            }
        });
    }
}
