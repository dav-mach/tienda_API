<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedidoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado' => $this->estado,
            'nombre_cliente' => $this->nombre_cliente,
            'email' => $this->email,
            'direccion_envio' => $this->direccion_envio,
            'ciudad' => $this->ciudad,
            'codigo_postal' => $this->codigo_postal,
            'metodo_pago' => $this->metodo_pago,
            'items' => PedidoItemResource::collection($this->whenLoaded('items')),
            'subtotal' => (float) $this->subtotal,
            'impuestos' => (float) $this->impuestos,
            'costo_envio' => (float) $this->costo_envio,
            'total' => (float) $this->total,
            'creado_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
