<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CarritoController;
use App\Http\Controllers\Api\V1\CategoriaController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\ProductoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Tienda de Negocios (v1)
|--------------------------------------------------------------------------
| Rutas públicas:  auth (register/login), y la lectura de productos y
|                  categorías (cualquiera puede mirar el catálogo).
| Rutas protegidas (middleware "jwt"): todo lo que modifica datos o toca
|                  el carrito y el checkout — solo con un JWT válido.
*/

Route::prefix('v1')->group(function () {

    /*
    |----------------------------------------------------------------------
    | Autenticación (requisito 2)
    |----------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        // Públicas: para conseguir un token.
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        // Protegidas: necesitan un token válido.
        Route::middleware('jwt')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    /*
    |----------------------------------------------------------------------
    | Catálogo
    |----------------------------------------------------------------------
    | Leer (GET) es público: mirar el catálogo no requiere estar logueado.
    | Crear/editar/borrar sí requiere token (son acciones de administración).
    */
    Route::get('productos', [ProductoController::class, 'index']);
    Route::get('productos/{producto}', [ProductoController::class, 'show']);
    Route::get('categorias', [CategoriaController::class, 'index']);
    Route::get('categorias/{categoria}', [CategoriaController::class, 'show']);

    Route::middleware('jwt')->group(function () {
        Route::post('productos', [ProductoController::class, 'store']);
        Route::put('productos/{producto}', [ProductoController::class, 'update']);
        Route::delete('productos/{producto}', [ProductoController::class, 'destroy']);

        Route::post('categorias', [CategoriaController::class, 'store']);
        Route::put('categorias/{categoria}', [CategoriaController::class, 'update']);
        Route::delete('categorias/{categoria}', [CategoriaController::class, 'destroy']);
    });

    /*
    |----------------------------------------------------------------------
    | Carrito y Checkout (requisito 4: rutas protegidas)
    |----------------------------------------------------------------------
    | Doble middleware:
    |   "jwt"      -> exige un token válido (si no, corta con 401).
    |   "carrito"  -> ya con el usuario identificado, le asigna su carrito.
    | El orden importa: primero autenticar, después buscar el carrito.
    */
    Route::middleware(['jwt', 'carrito'])->group(function () {
        // Carrito
        Route::get('/carrito', [CarritoController::class, 'show']);
        Route::get('/carrito/resumen', [CarritoController::class, 'resumen']);
        Route::post('/carrito/items', [CarritoController::class, 'agregar']);
        Route::put('/carrito/items/{item}', [CarritoController::class, 'actualizarCantidad']);
        Route::delete('/carrito/items/{item}', [CarritoController::class, 'eliminar']);
        Route::delete('/carrito', [CarritoController::class, 'vaciar']);

        // Checkout, paso 2: registrar datos de envío/pago
        Route::post('/checkout', [CheckoutController::class, 'registrarDatos']);
    });

    // Checkout pasos 3 y consulta: exigen token, pero no dependen del
    // carrito de la petición (operan sobre un Pedido ya creado).
    Route::middleware('jwt')->group(function () {
        Route::post('/checkout/{pedido}/confirmar', [CheckoutController::class, 'confirmar']);
        Route::get('/checkout/{pedido}', [CheckoutController::class, 'show']);
    });

});
