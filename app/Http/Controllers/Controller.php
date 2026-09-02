<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Controlador base. Todos los controladores de la API heredan de acá,
 * así que estos 3 métodos quedan disponibles en todos con $this->....
 *
 * Formato de respuesta usado en toda la API:
 *   éxito: { "success": true,  "message": string|null, "data": ... }
 *   error: { "success": false, "message": "...",        "errors": {...} }
 *
 * "message" y "data"/"errors" están SIEMPRE presentes (aunque sea con
 * valor null o un array vacío), para que toda respuesta tenga la misma
 * forma sin importar el endpoint.
 */
abstract class Controller
{
    protected function exito(JsonResource $resource, ?string $mensaje = null, int $status = 200): JsonResponse
    {
        return $resource->additional([
            'success' => true,
            'message' => $mensaje,
        ])->response()->setStatusCode($status);
    }

    protected function exitoDatos(mixed $datos = null, ?string $mensaje = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'data' => $datos,
        ], $status);
    }

    protected function error(string $mensaje, array $errores = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $mensaje,
            'errors' => $errores,
        ], $status);
    }
}
