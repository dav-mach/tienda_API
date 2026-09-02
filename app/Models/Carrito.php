<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Carrito: identificado por un token (no por sesión de PHP), para que
 * tanto un cliente logueado como uno anónimo (Postman, una app) puedan
 * mantener su carrito entre peticiones. La lógica de negocio (agregar,
 * actualizar cantidades, calcular resumen) vive en App\Services\CarritoService.
 */
class Carrito extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'usuario_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Carrito $carrito) {
            $carrito->token ??= (string) Str::uuid();
        });
    }

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
