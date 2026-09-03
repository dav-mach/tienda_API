<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware personalizado que protege una ruta exigiendo un JWT válido
 * (requisito 4). Se aplica con el alias "jwt" (ver bootstrap/app.php).
 *
 * A diferencia del middleware "auth" genérico de Laravel, este devuelve
 * mensajes en JSON, en español, distinguiendo los 3 casos de fallo:
 * token ausente, token vencido y token inválido. Todos responden 401.
 */
class JwtAutenticado
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Lee el token del header Authorization: Bearer <token>,
            // lo valida (firma + expiración) y carga el usuario dueño.
            $usuario = JWTAuth::parseToken()->authenticate();

            if (!$usuario) {
                return $this->noAutorizado('Usuario no encontrado para este token.');
            }
        } catch (TokenExpiredException $e) {
            return $this->noAutorizado('El token expiró, iniciá sesión de nuevo.');
        } catch (TokenInvalidException $e) {
            return $this->noAutorizado('El token no es válido.');
        } catch (\Throwable $e) {
            // Incluye el caso más común: no se mandó ningún token.
            return $this->noAutorizado('Falta el token de autenticación.');
        }

        return $next($request);
    }

    private function noAutorizado(string $mensaje): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $mensaje,
            'errors' => [],
        ], 401);
    }
}
