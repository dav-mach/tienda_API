<?php

namespace App\DTOs;

/**
 * DTO de salida: el "resumen de compra" (requisito 4) que se devuelve
 * tanto en GET /api/carrito/resumen como al armar un Pedido.
 * No representa un modelo Eloquent 1 a 1 (es un cálculo agregado),
 * por eso es una clase propia y no una API Resource.
 */
class ResumenCompraDTO
{
    public function __construct(
        public int $cantidadItems,
        public float $subtotal,
        public float $impuestos,
        public float $costoEnvio,
        public float $total,
    ) {
    }

    public function toArray(): array
    {
        return [
            'cantidad_items' => $this->cantidadItems,
            'subtotal' => round($this->subtotal, 2),
            'impuestos' => round($this->impuestos, 2),
            'costo_envio' => round($this->costoEnvio, 2),
            'total' => round($this->total, 2),
        ];
    }
}
