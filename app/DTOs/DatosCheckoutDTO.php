<?php

namespace App\DTOs;

/**
 * DTO de entrada: datos de envío y pago que el cliente registra al
 * iniciar el checkout (paso 2 del flujo: "registrar datos de envío/pago").
 */
class DatosCheckoutDTO
{
    public function __construct(
        public string $nombreCliente,
        public string $email,
        public string $direccionEnvio,
        public string $ciudad,
        public string $codigoPostal,
        public string $metodoPago,
    ) {
    }

    public static function fromArray(array $datos): self
    {
        return new self(
            nombreCliente: $datos['nombre_cliente'],
            email: $datos['email'],
            direccionEnvio: $datos['direccion_envio'],
            ciudad: $datos['ciudad'],
            codigoPostal: $datos['codigo_postal'],
            metodoPago: $datos['metodo_pago'],
        );
    }
}
