<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarDatosCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_cliente' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'direccion_envio' => ['required', 'string', 'max:255'],
            'ciudad' => ['required', 'string', 'max:255'],
            'codigo_postal' => ['required', 'string', 'max:20'],
            'metodo_pago' => ['required', 'string', 'in:tarjeta,efectivo,transferencia'],
        ];
    }

    public function messages(): array
    {
        return [
            'metodo_pago.in' => 'El método de pago tiene que ser: tarjeta, efectivo o transferencia.',
        ];
    }
}
