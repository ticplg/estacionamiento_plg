<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsuarioLocatarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->get('id');

        return [
            'usuario' => ['required', 'email', 'max:255', Rule::unique('usuarios_locatarios', 'usuario')->ignore($id)],
            'password' => [$id ? 'nullable' : 'required', 'string', 'min:6'],
            'local' => 'required|string|max:255',
            'codigo_descuento' => 'required|alpha_num|max:20',
            'estado' => 'required|in:A,I',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'usuario' => 'usuario',
            'password' => 'contraseña',
            'codigo_descuento' => 'código de descuento',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            //
        ];
    }
}
