<?php

namespace App\Http\Middleware;

use App\Models\Carrito;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifica el carrito de la petición actual a partir del usuario
 * autenticado (Entrega 4). Ya no hay token anónimo: cada usuario tiene
 * su carrito, y si todavía no tiene uno, se lo crea al vuelo.
 *
 * Se aplica DESPUÉS del middleware "jwt", así que acá siempre hay un
 * usuario autenticado disponible en Auth::user().
 */
class IdentificarCarrito
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::guard('api')->user();

        // firstOrCreate: si el usuario ya tiene carrito lo trae, si no lo crea.
        $carrito = Carrito::firstOrCreate(['usuario_id' => $usuario->id]);

        // Disponible en los controladores vía $request->attributes->get('carrito')
        $request->attributes->set('carrito', $carrito);

        return $next($request);
    }
}
