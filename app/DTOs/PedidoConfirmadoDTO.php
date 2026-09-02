<?php

namespace App\DTOs;

use App\Models\Pedido;

/**
 * DTO de salida: la confirmación final del checkout (requisito 6,
 * ejemplo explícito de la consigna: "al confirmar una compra").
 */
class PedidoConfirmadoDTO
{
    /**
     * @param array<int, array{producto_id: ?int, nombre: string, cantidad: int, precio_unitario: float, subtotal: float}> $items
     */
    public function __construct(
        public int $pedidoId,
        public string $estado,
        public array $items,
        public float $subtotal,
        public float $impuestos,
        public float $costoEnvio,
        public float $total,
        public string $fecha,
    ) {
    }

    public static function fromPedido(Pedido $pedido): self
    {
        $pedido->loadMissing('items');

        return new self(
            pedidoId: $pedido->id,
            estado: $pedido->estado,
            items: $pedido->items->map(fn ($item) => [
                'producto_id' => $item->producto_id,
                'nombre' => $item->nombre_producto,
                'cantidad' => $item->cantidad,
                'precio_unitario' => (float) $item->precio_unitario,
                'subtotal' => (float) $item->subtotal,
            ])->all(),
            subtotal: (float) $pedido->subtotal,
            impuestos: (float) $pedido->impuestos,
            costoEnvio: (float) $pedido->costo_envio,
            total: (float) $pedido->total,
            fecha: $pedido->updated_at->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'pedido_id' => $this->pedidoId,
            'estado' => $this->estado,
            'items' => $this->items,
            'subtotal' => round($this->subtotal, 2),
            'impuestos' => round($this->impuestos, 2),
            'costo_envio' => round($this->costoEnvio, 2),
            'total' => round($this->total, 2),
            'fecha' => $this->fecha,
        ];
    }
}
