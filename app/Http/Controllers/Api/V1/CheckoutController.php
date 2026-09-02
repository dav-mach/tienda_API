<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\DatosCheckoutDTO;
use App\DTOs\PedidoConfirmadoDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarDatosCheckoutRequest;
use App\Http\Resources\PedidoResource;
use App\Models\Pedido;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Flujo de checkout como 3 endpoints (requisito 5):
 *   1. revisar carrito           -> GET  /api/v1/carrito/resumen
 *   2. registrar datos de envío  -> POST /api/v1/checkout            (acá abajo)
 *   3. confirmar la compra       -> POST /api/v1/checkout/{pedido}/confirmar
 *
 * Separar "registrar" de "confirmar" permite mostrarle al cliente una
 * pantalla de revisión (Pedido en estado pendiente_confirmacion) antes
 * de descontar stock de verdad.
 */
class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkoutService)
    {
    }

    /** Paso 2: registra datos de envío/pago y arma el Pedido (sin confirmar todavía). */
    public function registrarDatos(RegistrarDatosCheckoutRequest $request): JsonResponse
    {
        $carrito = $request->attributes->get('carrito');

        $pedido = $this->checkoutService->registrarDatos(
            $carrito,
            DatosCheckoutDTO::fromArray($request->validated())
        );

        return $this->exito(
            new PedidoResource($pedido->load('items')),
            'Datos registrados. Confirma la compra para finalizarla.',
            201
        );
    }

    /** Paso 3: revalida stock, lo descuenta, confirma el pedido y vacía el carrito. */
    public function confirmar(Pedido $pedido): JsonResponse
    {
        $pedido = $this->checkoutService->confirmar($pedido);

        $dto = PedidoConfirmadoDTO::fromPedido($pedido);

        return $this->exitoDatos($dto->toArray(), 'Compra confirmada.');
    }

    /** Consulta el estado de un pedido puntual. */
    public function show(Pedido $pedido): JsonResponse
    {
        return $this->exito(new PedidoResource($pedido->load('items')));
    }
}
