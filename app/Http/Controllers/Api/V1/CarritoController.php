<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ActualizarCantidadDTO;
use App\DTOs\AgregarAlCarritoDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarCantidadCarritoRequest;
use App\Http\Requests\AgregarAlCarritoRequest;
use App\Http\Resources\CarritoItemResource;
use App\Http\Resources\CarritoResource;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Services\CarritoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Carrito como recurso de la API (requisito 3).
 * El carrito de la petición actual llega resuelto por el middleware
 * "carrito" (IdentificarCarrito), en $request->attributes->get('carrito').
 */
class CarritoController extends Controller
{
    public function __construct(private readonly CarritoService $carritoService)
    {
    }

    /** GET /api/v1/carrito — ver el carrito actual con sus items. */
    public function show(Request $request): JsonResponse
    {
        $carrito = $this->carritoActual($request);
        $carrito->load('items.producto');

        return $this->exito(new CarritoResource($carrito));
    }

    /**
     * POST /api/v1/carrito/items — agregar un producto al carrito.
     * Body: { "producto_id": 1, "cantidad": 2 }
     */
    public function agregar(AgregarAlCarritoRequest $request): JsonResponse
    {
        $carrito = $this->carritoActual($request);

        $item = $this->carritoService->agregar(
            $carrito,
            AgregarAlCarritoDTO::fromArray($request->validated())
        );

        return $this->exito(new CarritoItemResource($item), 'Producto agregado al carrito.', 201);
    }

    /**
     * PUT /api/v1/carrito/items/{item} — actualizar la cantidad de un item.
     * Body: { "cantidad": 5 }
     */
    public function actualizarCantidad(ActualizarCantidadCarritoRequest $request, CarritoItem $item): JsonResponse
    {
        $this->verificarPertenece($request, $item);

        $item = $this->carritoService->actualizarCantidad(
            $item,
            ActualizarCantidadDTO::fromArray($request->validated())
        );

        return $this->exito(new CarritoItemResource($item), 'Cantidad actualizada.');
    }

    /** DELETE /api/v1/carrito/items/{item} — quitar un producto del carrito. */
    public function eliminar(Request $request, CarritoItem $item): JsonResponse
    {
        $this->verificarPertenece($request, $item);

        $this->carritoService->eliminar($item);

        return $this->exitoDatos(null, 'Producto quitado del carrito.');
    }

    /** DELETE /api/v1/carrito — vaciar el carrito completo. */
    public function vaciar(Request $request): JsonResponse
    {
        $carrito = $this->carritoActual($request);

        $this->carritoService->vaciar($carrito);

        return $this->exitoDatos(null, 'Carrito vaciado.');
    }

    /** GET /api/v1/carrito/resumen — subtotal, impuestos, envío y total (requisito 4). */
    public function resumen(Request $request): JsonResponse
    {
        $carrito = $this->carritoActual($request);

        $resumen = $this->carritoService->calcularResumen($carrito);

        return $this->exitoDatos($resumen->toArray());
    }

    /** Trae el carrito que el middleware IdentificarCarrito dejó guardado en la petición. */
    private function carritoActual(Request $request): Carrito
    {
        return $request->attributes->get('carrito');
    }

    /**
     * Evita que, con un token de carrito ajeno, alguien opere sobre el
     * item de otro carrito adivinando su id.
     */
    private function verificarPertenece(Request $request, CarritoItem $item): void
    {
        abort_unless($item->carrito_id === $this->carritoActual($request)->id, 404, 'Item no encontrado en tu carrito.');
    }
}
