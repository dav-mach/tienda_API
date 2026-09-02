<?php

namespace App\Http\Middleware;

use App\Models\Carrito;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifica el carrito de la petición actual (requisito 8: persistir
 * el carrito en base de datos para que el usuario no lo pierda entre
 * peticiones).
 *
 * El cliente manda su token en el header X-Cart-Token. Si no lo manda
 * (primera vez), se crea un carrito nuevo y el token vuelve en ese mismo
 * header de la respuesta, para usarlo en las próximas peticiones.
 */
class IdentificarCarrito
{
    public const HEADER = 'X-Cart-Token';

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header(self::HEADER);

        $carrito = $token ? Carrito::where('token', $token)->first() : null;

        if (!$carrito) {
            $carrito = Carrito::create([]);
        }

        // Disponible en los controladores vía $request->attributes->get('carrito')
        $request->attributes->set('carrito', $carrito);

        $response = $next($request);
        $response->headers->set(self::HEADER, $carrito->token);

        return $response;
    }
}
