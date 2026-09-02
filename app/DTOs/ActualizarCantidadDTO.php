<?php

namespace App\DTOs;

class ActualizarCantidadDTO
{
    public function __construct(
        public int $cantidad,
    ) {
    }

    public static function fromArray(array $datos): self
    {
        return new self(cantidad: (int) $datos['cantidad']);
    }
}
