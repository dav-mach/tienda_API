<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarritoItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'nombre_producto' => $this->whenLoaded('producto', fn () => $this->producto->nombre),
            'precio_unitario' => $this->whenLoaded('producto', fn () => (float) $this->producto->precio),
            'cantidad' => $this->cantidad,
            'subtotal' => $this->whenLoaded('producto', fn () => (float) $this->subtotal()),
        ];
    }
}
