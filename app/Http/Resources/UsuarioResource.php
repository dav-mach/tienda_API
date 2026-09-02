<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializa un User para las respuestas de la API. A propósito NO incluye
 * la contraseña (ni siquiera el hash): un dato así nunca debe salir en
 * una respuesta.
 *
 * @property \App\Models\User $resource
 */
class UsuarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $this->rol,
        ];
    }
}
