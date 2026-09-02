<?php

namespace App\Exceptions;

use App\Models\Pedido;
use Exception;

/**
 * Se lanza si se intenta confirmar dos veces el mismo pedido
 * (evita descontar stock por duplicado).
 */
class PedidoYaConfirmadoException extends Exception
{
    public function __construct(public Pedido $pedido)
    {
        parent::__construct("El pedido #{$pedido->id} ya fue confirmado anteriormente.");
    }
}
