<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Carrito: pertenece a un usuario autenticado (Entrega 4). Se identifica
 * por su usuario_id, que sale del JWT en cada petición — ya no hay token
 * anónimo. La lógica de negocio (agregar, actualizar cantidades, calcular
 * resumen) vive en App\Services\CarritoService.
 */
class Carrito extends Model
{
    use HasFactory;

    protected $fillable = [
        'usuario_id',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CarritoItem::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function estaVacio(): bool
    {
        return $this->items()->doesntExist();
    }
}
