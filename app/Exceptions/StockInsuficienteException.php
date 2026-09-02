<?php

namespace App\Exceptions;

use App\Models\Producto;
use Exception;

/**
 * Se lanza cuando se pide agregar/actualizar/confirmar más unidades de
 * un producto de las que hay en stock (requisito 9: manejo de inventario).
 */
class StockInsuficienteException extends Exception
{
    public function __construct(
        public Producto $producto,
        public int $cantidadSolicitada,
    ) {
        parent::__construct(
            "Stock insuficiente para \"{$producto->nombre}\": pediste {$cantidadSolicitada}, hay {$producto->stock} disponibles."
        );
    }
}
