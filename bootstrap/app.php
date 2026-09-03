<?php

use App\Exceptions\CarritoVacioException;
use App\Exceptions\PedidoYaConfirmadoException;
use App\Exceptions\StockInsuficienteException;
use App\Http\Middleware\IdentificarCarrito;
use App\Http\Middleware\JwtAutenticado;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt' => JwtAutenticado::class,          // exige un JWT válido (Entrega 4)
            'carrito' => IdentificarCarrito::class,  // asigna el carrito del usuario autenticado
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Cualquier error dentro de /api/* siempre responde en JSON (requisito 7),
        // nunca con la página de error HTML de Laravel.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Errores de validación (Form Requests): 422 + detalle por campo.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $e->errors(),
            ], 422);
        });

        // Route-model binding que no encuentra el registro (ej: GET /api/productos/9999): 404.
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            $modelo = class_basename($e->getModel());

            return response()->json([
                'success' => false,
                'message' => "{$modelo} no encontrado.",
                'errors' => [],
            ], 404);
        });

        // Las 3 excepciones propias del carrito/checkout (requisito 9):
        // cada una sabe su propio mensaje, acá solo decidimos el código
        // HTTP y qué datos extra mandar en "errors".
        $exceptions->render(fn (StockInsuficienteException $e) => response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => [
                'producto_id' => $e->producto->id,
                'stock_disponible' => $e->producto->stock,
                'cantidad_solicitada' => $e->cantidadSolicitada,
            ],
        ], 409));

        $exceptions->render(fn (CarritoVacioException $e) => response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => [],
        ], 422));

        $exceptions->render(fn (PedidoYaConfirmadoException $e) => response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => ['pedido_id' => $e->pedido->id, 'estado_actual' => $e->pedido->estado],
        ], 409));

        // Cualquier otra excepción sobre /api/*: mismo sobre estandarizado,
        // respetando el código HTTP real si la excepción ya lo trae
        // (ruta inexistente -> 404, verbo no permitido -> 405, etc).
        $exceptions->render(function (Throwable $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException || $e instanceof ModelNotFoundException) {
                return null; // ya resueltos arriba
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            return response()->json([
                'success' => false,
                'message' => ($status === 500 && !config('app.debug'))
                    ? 'Ocurrió un error inesperado.'
                    : $e->getMessage(),
                'errors' => [],
            ], $status);    
        });
    })->create();
