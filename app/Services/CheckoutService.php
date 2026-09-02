<?php

namespace App\Services;

use App\DTOs\DatosCheckoutDTO;
use App\Exceptions\CarritoVacioException;
use App\Exceptions\PedidoYaConfirmadoException;
use App\Exceptions\StockInsuficienteException;
use App\Models\Carrito;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

/**
 * Orquesta el flujo de checkout (requisito 5):
 *   1. revisar carrito           -> CarritoService::calcularResumen()
 *   2. registrar datos de envío  -> self::registrarDatos()
 *   3. confirmar la compra       -> self::confirmar()
 */
class CheckoutService
{
    public function __construct(
        private readonly CarritoService $carritoService,
    ) {
    }

    /**
     * Paso 2: crea un Pedido en estado "pendiente_confirmacion" con una
     * foto del carrito actual (items + totales). Todavía NO descuenta
     * stock: eso pasa recién en confirmar().
     *
     * @throws CarritoVacioException
     * @throws StockInsuficienteException
     */
    public function registrarDatos(Carrito $carrito, DatosCheckoutDTO $datos): Pedido
    {
        $items = $carrito->items()->with('producto')->get();

        if ($items->isEmpty()) {
            throw new CarritoVacioException();
        }

        // Revalidamos stock: pudo haber cambiado desde que se agregó al carrito.
        foreach ($items as $item) {
            if (!$item->producto->hayStockDisponible($item->cantidad)) {
                throw new StockInsuficienteException($item->producto, $item->cantidad);
            }
        }

        $resumen = $this->carritoService->calcularResumen($carrito);

        return DB::transaction(function () use ($carrito, $datos, $items, $resumen) {
            $pedido = Pedido::create([
                'carrito_token' => $carrito->token,
                'usuario_id' => $carrito->usuario_id,
                'nombre_cliente' => $datos->nombreCliente,
                'email' => $datos->email,
                'direccion_envio' => $datos->direccionEnvio,
                'ciudad' => $datos->ciudad,
                'codigo_postal' => $datos->codigoPostal,
                'metodo_pago' => $datos->metodoPago,
                'subtotal' => $resumen->subtotal,
                'impuestos' => $resumen->impuestos,
                'costo_envio' => $resumen->costoEnvio,
                'total' => $resumen->total,
                'estado' => Pedido::ESTADO_PENDIENTE,
            ]);

            foreach ($items as $item) {
                $pedido->items()->create([
                    'producto_id' => $item->producto_id,
                    'nombre_producto' => $item->producto->nombre,
                    'precio_unitario' => $item->producto->precio,
                    'cantidad' => $item->cantidad,
                    'subtotal' => $item->subtotal(),
                ]);
            }

            return $pedido;
        });
    }

    /**
     * Paso 3: revalida el stock una última vez (pudo cambiar desde que
     * se armó el pedido) y recién ahí lo descuenta de verdad y vacía
     * el carrito.
     *
     * @throws PedidoYaConfirmadoException
     * @throws StockInsuficienteException
     */
    public function confirmar(Pedido $pedido): Pedido
    {
        if ($pedido->estaConfirmado()) {
            throw new PedidoYaConfirmadoException($pedido);
        }

        return DB::transaction(function () use ($pedido) {
            $pedido->load('items');

            // 1) Primero se valida TODO el pedido. Si algo no tiene stock,
            // no se descuenta nada (ni de los items que sí estaban bien).
            foreach ($pedido->items as $item) {
                if (!$item->producto_id) {
                    continue; // el producto fue eliminado después de armar el pedido
                }

                $producto = Producto::find($item->producto_id);

                if (!$producto || !$producto->hayStockDisponible($item->cantidad)) {
                    throw new StockInsuficienteException($producto ?? $item->producto, $item->cantidad);
                }
            }

            // 2) Recién acá, con todo validado, se descuenta el stock.
            foreach ($pedido->items as $item) {
                if ($item->producto_id) {
                    Producto::whereKey($item->producto_id)->decrement('stock', $item->cantidad);
                }
            }

            $pedido->update(['estado' => Pedido::ESTADO_CONFIRMADO]);

            Carrito::where('token', $pedido->carrito_token)->first()?->items()->delete();

            return $pedido->fresh('items');
        });
    }
}
