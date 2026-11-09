<?php

namespace App\Http\Requests;

use App\Services\ConsultaRucService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SkydataRucCorreoRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'ruc' => trim((string) $this->ruc),
            'email' => mb_strtolower(trim((string) $this->email)),
        ]);
    }

    public function rules()
    {
        return [
            'ruc' => [
                'required',
                'max:20',
                'regex:/^\d+-\d$/',
                Rule::unique('skydata_ruc_correos', 'ruc')->ignore($this->id),
                function ($attribute, $value, $fail) {
                    if (! preg_match('/^(\d+)-(\d)$/', $value, $partes)) {
                        return;
                    }

                    $dv = ConsultaRucService::calcularDv($partes[1]);
                    if ((string) $dv !== $partes[2]) {
                        $fail("El dígito verificador no es correcto (debería ser {$partes[1]}-{$dv}).");
                    }
                },
            ],
            'razon_social' => 'nullable|max:255',
            'email' => 'required|email|max:255',
            'estado' => 'required|in:A,I',
        ];
    }

    public function attributes()
    {
        return [
            'ruc' => 'RUC',
            'razon_social' => 'razón social',
            'email' => 'correo electrónico',
        ];
    }

    public function messages()
    {
        return [
            'ruc.regex' => 'El RUC debe tener el formato número-DV (ej. 80012345-6).',
            'ruc.unique' => 'Ese RUC ya está cargado.',
        ];
    }
}
