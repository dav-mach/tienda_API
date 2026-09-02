<?php

namespace App\DTOs;

/**
 * DTO de entrada: datos necesarios para agregar un producto al carrito.
 * Se arma a partir de los datos ya validados por un Form Request, y es
 * lo que realmente recibe App\Services\CarritoService — así el servicio
 * no depende de la clase Request de Laravel, solo de datos simples.
 */
class AgregarAlCarritoDTO
{
    public function __construct(
        public int $productoId,
        public int $cantidad,
    ) {
    }

    public static function fromArray(array $datos): self
    {
        return new self(
            productoId: (int) $datos['producto_id'],
            cantidad: (int) $datos['cantidad'],
        );
    }
}
