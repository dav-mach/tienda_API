<?php

namespace App\Services;

use App\DTOs\ActualizarCantidadDTO;
use App\DTOs\AgregarAlCarritoDTO;
use App\DTOs\ResumenCompraDTO;
use App\Exceptions\StockInsuficienteException;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Producto;

/**
 * Concentra toda la lógica de negocio del carrito, para que los
 * controladores de la API se limiten a: recibir la petición, llamar
 * al servicio, devolver la respuesta. Nada de reglas de stock ni de
 * cálculos de totales vive en el Controlador.
 */
class CarritoService
{
    private const PORCENTAJE_IVA = 0.21;
    private const COSTO_ENVIO_FIJO = 5000.0;
    private const ENVIO_GRATIS_DESDE = 100000.0;

    /**
     * Agrega un producto al carrito. Si ya estaba, suma la cantidad
     * (en vez de crear una fila duplicada).
     *
     * @throws StockInsuficienteException
     */
    public function agregar(Carrito $carrito, AgregarAlCarritoDTO $datos): CarritoItem
    {
        $producto = Producto::findOrFail($datos->productoId);

        $itemExistente = $carrito->items()->where('producto_id', $producto->id)->first();
        $cantidadFinal = $datos->cantidad + ($itemExistente->cantidad ?? 0);

        if (!$producto->hayStockDisponible($cantidadFinal)) {
            throw new StockInsuficienteException($producto, $cantidadFinal);
        }

        if ($itemExistente) {
            $itemExistente->update(['cantidad' => $cantidadFinal]);

            return $itemExistente->fresh('producto');
        }

        return $carrito->items()->create([
            'producto_id' => $producto->id,
            'cantidad' => $datos->cantidad,
        ])->fresh('producto');
    }

    /**
     * @throws StockInsuficienteException
     */
    public function actualizarCantidad(CarritoItem $item, ActualizarCantidadDTO $datos): CarritoItem
    {
        if (!$item->producto->hayStockDisponible($datos->cantidad)) {
            throw new StockInsuficienteException($item->producto, $datos->cantidad);
        }

        $item->update(['cantidad' => $datos->cantidad]);

        return $item->fresh('producto');
    }

    public function eliminar(CarritoItem $item): void
    {
        $item->delete();
    }

    public function vaciar(Carrito $carrito): void
    {
        $carrito->items()->delete();
    }

    /**
     * Calcula el resumen de compra (requisito 4): subtotal, impuestos,
     * costo de envío y total. Envío gratis a partir de cierto monto.
     */
    public function calcularResumen(Carrito $carrito): ResumenCompraDTO
    {
        $items = $carrito->items()->with('producto')->get();

        $subtotal = (float) $items->sum(fn (CarritoItem $item) => $item->subtotal());
        $impuestos = round($subtotal * self::PORCENTAJE_IVA, 2);
        $costoEnvio = $items->isEmpty() || $subtotal >= self::ENVIO_GRATIS_DESDE
            ? 0.0
            : self::COSTO_ENVIO_FIJO;

        return new ResumenCompraDTO(
            cantidadItems: (int) $items->sum('cantidad'),
            subtotal: $subtotal,
            impuestos: $impuestos,
            costoEnvio: $costoEnvio,
            total: $subtotal + $impuestos + $costoEnvio,
        );
    }
}
