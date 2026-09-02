<?php

use App\Http\Controllers\Api\V1\CarritoController;
use App\Http\Controllers\Api\V1\CategoriaController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\ProductoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Tienda de Negocios
|--------------------------------------------------------------------------
| Todo lo de acá vive bajo /api/v1 (el prefijo "v1" es a propósito:
| versionar la API desde el arranque permite el día de mañana sacar una
| v2 con cambios que rompan compatibilidad, sin tocar ni un cliente que
| ya esté usando /api/v1). Responde siempre en JSON: ver withExceptions
| en bootstrap/app.php.
*/

Route::prefix('v1')->group(function () {

    // Productos y Categorías: CRUD completo (requisito 2)
    Route::apiResource('productos', ProductoController::class);
    Route::apiResource('categorias', CategoriaController::class);

    // Carrito y Checkout: requieren identificar el carrito de la
    // petición (middleware "carrito" -> App\Http\Middleware\IdentificarCarrito).
    // El cliente manda su token en el header X-Cart-Token; si no lo manda,
    // se crea un carrito nuevo y el token vuelve en la respuesta (mismo header).
    Route::middleware('carrito')->group(function () {
        // Carrito (requisito 3)
        Route::get('/carrito', [CarritoController::class, 'show']);
        Route::get('/carrito/resumen', [CarritoController::class, 'resumen']); // requisito 4
        Route::post('/carrito/items', [CarritoController::class, 'agregar']);
        Route::put('/carrito/items/{item}', [CarritoController::class, 'actualizarCantidad']);
        Route::delete('/carrito/items/{item}', [CarritoController::class, 'eliminar']);
        Route::delete('/carrito', [CarritoController::class, 'vaciar']);

        // Checkout, paso 2: registrar datos de envío/pago (requisito 5)
        Route::post('/checkout', [CheckoutController::class, 'registrarDatos']);
    });

    // Checkout, paso 3: confirmar la compra — ya no depende del carrito de
    // la petición (opera directo sobre el Pedido ya creado), así que queda
    // fuera del grupo de arriba para no crear un carrito vacío innecesario.
    Route::post('/checkout/{pedido}/confirmar', [CheckoutController::class, 'confirmar']);
    Route::get('/checkout/{pedido}', [CheckoutController::class, 'show']);

});
