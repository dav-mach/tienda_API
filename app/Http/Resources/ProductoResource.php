<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'precio' => (float) $this->precio,
            'stock' => $this->stock,
            'categoria_id' => $this->categoria_id,
            'categoria' => new CategoriaResource($this->whenLoaded('categoria')),
            'creado_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
