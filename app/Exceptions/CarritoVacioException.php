<?php

namespace App\Exceptions;

use Exception;

/**
 * Se lanza al intentar iniciar un checkout con el carrito vacío.
 * Cómo se convierte esto en una respuesta JSON está en bootstrap/app.php.
 */
class CarritoVacioException extends Exception
{
    public function __construct()
    {
        parent::__construct('El carrito esta vacio: agrega productos antes de iniciar el checkout.');
    }
}