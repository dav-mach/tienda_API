<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los datos de login. No verifica si la contraseña es correcta
 * (eso lo hace el AuthController contra la base): solo chequea que los
 * campos vengan y tengan el formato correcto.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email no tiene un formato válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }
}
